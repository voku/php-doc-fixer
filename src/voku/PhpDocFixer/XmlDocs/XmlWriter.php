<?php

declare(strict_types=1);

namespace voku\PhpDocFixer\XmlDocs;

final class XmlWriter
{
    private string $xml_file_path;

    public function __construct(string $xml_file_path)
    {
        $this->xml_file_path = $xml_file_path;
    }

    /**
     * @param array $newTypes
     * @param string $functionNameOrMethodName The function or method synopsis name to update.
     *
     * @return bool
     *
     * @phpstan-param array{return: string, params?: array<string, string>} $newTypes
     */
    public function fix(array $newTypes, string $functionNameOrMethodName): bool
    {
        return $this->fixMany([$functionNameOrMethodName => $newTypes]) > 0;
    }

    /**
     * @param array<string, array{return: string, params?: array<string, string>}> $newTypesByFunctionName Updates keyed by function or method synopsis name.
     */
    public function fixMany(array $newTypesByFunctionName): int
    {
        $content = \file_get_contents($this->xml_file_path);
        if ($content === false) {
            throw new \InvalidArgumentException('Could not read the file: ' . $this->xml_file_path);
        }
        $contentOrig = $content;

        [$content, $updatedEntries] = $this->replaceTypesInContent($content, $newTypesByFunctionName);

        if ($updatedEntries > 0 && $contentOrig !== $content) {
            \file_put_contents($this->xml_file_path, $content);
        }

        return $updatedEntries;
    }

    /**
     * @param array<string, array{return: string, params?: array<string, string>}> $newTypesByFunctionName
     *
     * @return array{0: string, 1: int}
     */
    private function replaceTypesInContent(string $content, array $newTypesByFunctionName): array
    {
        $xmlParser = new \voku\helper\XmlDomParser();
        $xmlParser->autoRemoveXPathNamespaces();

        $methodSynopses = $this->findSynopsisXml($content, 'methodsynopsis');
        $constructorSynopses = $this->findSynopsisXml($content, 'constructorsynopsis');

        if ($methodSynopses === [] && $constructorSynopses === []) {
            return [$content, 0];
        }

        $updatedEntries = [];
        foreach ($methodSynopses as $methodSynopsisXml) {
            $methodsynopsis = $xmlParser->loadXml($methodSynopsisXml);
            $functionNameOrMethodName = $methodsynopsis->findOne('methodname')->text();
            if (!isset($newTypesByFunctionName[$functionNameOrMethodName])) {
                continue;
            }

            $newTypesXml = $this->replaceTypes($methodsynopsis, $newTypesByFunctionName[$functionNameOrMethodName]);
            $contentBefore = $content;
            $content = \str_replace($methodSynopsisXml, $newTypesXml, $content);
            $content = \str_replace('</methodsynopsis>' . "\n\n", '</methodsynopsis>' . "\n", $content);
            if ($content !== $contentBefore) {
                $updatedEntries[$functionNameOrMethodName] = true;
            }
        }

        foreach ($constructorSynopses as $constructorSynopsisXml) {
            $constructorsynopsis = $xmlParser->loadXml($constructorSynopsisXml);
            $functionNameOrMethodName = $constructorsynopsis->findOne('methodname')->text();
            if (!isset($newTypesByFunctionName[$functionNameOrMethodName])) {
                continue;
            }

            $newTypesXml = $this->replaceTypes($constructorsynopsis, $newTypesByFunctionName[$functionNameOrMethodName]);
            $contentBefore = $content;
            $content = \str_replace($constructorSynopsisXml, $newTypesXml, $content);
            $content = \str_replace('</constructorsynopsis>' . "\n\n", '</constructorsynopsis>' . "\n", $content);
            if ($content !== $contentBefore) {
                $updatedEntries[$functionNameOrMethodName] = true;
            }
        }

        return [$content, \count($updatedEntries)];
    }

    /**
     * @return array<int, string>
     */
    private function findSynopsisXml(string $content, string $xmlElement): array
    {
        \preg_match_all(
            '#<' . $xmlElement . '(?:\s[^>]*)?>.*</' . $xmlElement . '>#Uis',
            $content,
            $matches
        );

        return $matches[0] ?? [];
    }

    /**
     * @param \voku\helper\XmlDomParser $xmlParser
     * @param array                     $newTypes
     *
     * @return string
     *
     * @phpstan-param array{return: string, params?: array<string, string>} $newTypes
     */
    private function replaceTypes(
        \voku\helper\XmlDomParser $xmlParser,
        array $newTypes
    ): string {
        $xml = $xmlParser->xml();

        if ($newTypes['return']) {
            $returnUnionNew = (array) \explode('|', $newTypes['return'] ?? []);
        } else {
            $returnUnionNew = [];
        }
        $returnUnionNewCount = \count($returnUnionNew);

        if ($returnUnionNewCount === 0) {
            // TODO: error in stubs?
        } elseif ($this->extractNormalizedSynopsisType($xmlParser) !== $newTypes['return']) {
            $returnUnionTypeFound = $xmlParser->findOneOrFalse('type.union') !== false;

            if ($returnUnionTypeFound) {
                $xml = (string) \preg_replace('#<type class="union">.*</type><methodname>#Usi', '###NEW_TYPE###<methodname>', $xml);
            } elseif ($xmlParser->findOneOrFalse('type') !== false) {
                $xml = (string) \preg_replace('#<type>.*</type><methodname>#Usi', '###NEW_TYPE###<methodname>', $xml);
            }

            $xml = \str_replace('###NEW_TYPE###', $this->buildTypeXml($returnUnionNew), $xml);
        }

        if (isset($newTypes['params'])) {
            $params = $xmlParser->findMultiOrFalse('//methodparam');
            if ($params !== false) {
                foreach ($params as $param) {
                    $paramName = $param->findOne('parameter')->text();
                    $escapedParamName = \preg_quote($paramName, '#');

                    if (isset($newTypes['params'][$paramName])) {
                        $paramTypesNew = (array) \explode('|', $newTypes['params'][$paramName]);
                    } else {
                        $paramTypesNew = [];
                    }
                    $paramTypesNewCount = \count($paramTypesNew);

                    if ($paramTypesNewCount === 0) {
                        // TODO: error in stubs?
                        continue;
                    }

                    if ($this->extractNormalizedMethodParamType($param) === $newTypes['params'][$paramName]) {
                        continue;
                    }

                    $paramUnionTypeFound = $param->findOneOrFalse('type.union') !== false;

                    if ($paramUnionTypeFound) {
                        $xml = (string) \preg_replace('#<methodparam([^>]*)><type class="union">.*</type><parameter>' . $escapedParamName . '#Usi', '<methodparam$1>###NEW_TYPE###<parameter>' . $paramName, $xml);
                    } elseif ($param->findOneOrFalse('type') !== false) {
                        $xml = (string) \preg_replace('#<methodparam([^>]*)><type>.*</type><parameter>' . $escapedParamName . '#Usi', '<methodparam$1>###NEW_TYPE###<parameter>' . $paramName, $xml);
                    }

                    $xml = \str_replace('###NEW_TYPE###', $this->buildTypeXml($paramTypesNew), $xml);
                }
            }
        }

        // fix :/
        return \str_replace(
            ['<void></void>'],
            ['<void />'],
            $xml
        );
    }

    /**
     * @param array<int, string> $types
     */
    private function buildTypeXml(array $types): string
    {
        if (\count($types) <= 1) {
            return '<type>' . $types[0] . '</type>';
        }

        $typeXml = '';
        foreach ($types as $type) {
            $typeXml .= '<type>' . $type . '</type>';
        }

        return '<type class="union">' . $typeXml . '</type>';
    }

    private function extractNormalizedSynopsisType(\voku\helper\XmlDomParser $xmlParser): string
    {
        $types = [];
        $typeNodes = $xmlParser->findMultiOrFalse('methodsynopsis > type.union > type');
        if ($typeNodes === false) {
            $typeNodes = $xmlParser->findMultiOrFalse('constructorsynopsis > type.union > type');
        }
        if ($typeNodes !== false) {
            foreach ($typeNodes as $typeNode) {
                $types[] = $typeNode->text();
            }
        } elseif (($typeNode = $xmlParser->findOneOrFalse('methodsynopsis > type')) !== false) {
            $types[] = $typeNode->text();
        } elseif (($typeNode = $xmlParser->findOneOrFalse('constructorsynopsis > type')) !== false) {
            $types[] = $typeNode->text();
        }

        return $this->normalizeTypeList($types);
    }

    private function extractNormalizedMethodParamType($xmlParser): string
    {
        $types = [];
        $typeNodes = $xmlParser->findMultiOrFalse('methodparam > type.union > type');
        if ($typeNodes !== false) {
            foreach ($typeNodes as $typeNode) {
                $types[] = $typeNode->text();
            }
        } elseif (($typeNode = $xmlParser->findOneOrFalse('methodparam > type')) !== false) {
            $types[] = $typeNode->text();
        }

        return $this->normalizeTypeList($types);
    }

    /**
     * @param array<int, string> $types
     */
    private function normalizeTypeList(array $types): string
    {
        $types = \array_values(\array_unique($types));
        \sort($types);

        return \implode('|', $types);
    }
}
