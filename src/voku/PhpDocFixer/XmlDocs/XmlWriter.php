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
     *
     * @return void
     *
     * @phpstan-param array{return: string, params?: array<string, string>} $newTypes
     */
    public function fix($newTypes): void
    {
        $xmlParser = new \voku\helper\XmlDomParser();
        $xmlParser->autoRemoveXPathNamespaces();

        $content = \file_get_contents($this->xml_file_path);
        if ($content === false) {
            throw new \InvalidArgumentException('Could not read the file: ' . $this->xml_file_path);
        }
        $contentOrig = $content;

        \preg_match('#<methodsynopsis>.*</methodsynopsis>#Uis', $content, $contentMethod);
        $contentMethod = $contentMethod[0] ?? null;

        \preg_match('#<methodsynopsis role="oop">.*</methodsynopsis>#Uis', $content, $contentMethodOop);
        $contentMethodOop = $contentMethodOop[0] ?? null;

        \preg_match('#<constructorsynopsis>.*</constructorsynopsis>#Uis', $content, $contentConstructor);
        $contentConstructor = $contentConstructor[0] ?? null;

        if (!$contentMethod && !$contentMethodOop && !$contentConstructor) {
            return;
        }

        if ($contentMethod) {
            $methodsynopsis = $xmlParser->loadXml($contentMethod);
            $newTypesXml = $this->replaceTypes($methodsynopsis, $newTypes);
            $content = \str_replace(
                $contentMethod,
                $newTypesXml,
                $content
            );
            $content = \str_replace('</methodsynopsis>' . "\n\n", '</methodsynopsis>' . "\n", $content);
        }

        if ($contentMethodOop) {
            $methodsynopsisOop = $xmlParser->loadXml($contentMethodOop);
            $newTypesXml = $this->replaceTypes($methodsynopsisOop, $newTypes);
            $content = \str_replace(
                $contentMethodOop,
                $newTypesXml,
                $content
            );
            $content = \str_replace('</methodsynopsis>' . "\n\n", '</methodsynopsis>' . "\n", $content);
        }

        if ($contentConstructor) {
            $constructorsynopsis = $xmlParser->loadXml($contentConstructor);
            $newTypesXml = $this->replaceTypes($constructorsynopsis, $newTypes);
            $content = \str_replace(
                $contentConstructor,
                $newTypesXml,
                $content
            );
            $content = \str_replace('</constructorsynopsis>' . "\n\n", '</constructorsynopsis>' . "\n", $content);
        }

        if ($contentOrig !== $content) {
            \file_put_contents($this->xml_file_path, $content);
        }
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
            // TODO: error in phpstorm-stubs ?
        } else {
            $returnUnionTypeFound = $xmlParser->findOneOrFalse('type.union') !== false;

            if ($returnUnionTypeFound && $returnUnionNewCount === 1) {
                // TODO: error in phpstorm-stubs ?
            } else {
                if ($returnUnionTypeFound) {
                    $xml = (string) \preg_replace('#<type class="union">.*</type><methodname>#Usi', '###NEW_TYPE###<methodname>', $xml);
                } elseif ($xmlParser->findOneOrFalse('type') !== false) {
                    $xml = (string) \preg_replace('#<type>.*</type><methodname>#Usi', '###NEW_TYPE###<methodname>', $xml);
                }

                if (\count($returnUnionNew) > 1) {
                    $returnXmlTmp = '';
                    foreach ($returnUnionNew as $returnUnionNewSingle) {
                        $returnXmlTmp .= '<type>' . $returnUnionNewSingle . '</type>';
                    }
                    $xml = \str_replace('###NEW_TYPE###', '<type class="union">' . $returnXmlTmp . '</type>', $xml);
                } else {
                    $xml = \str_replace('###NEW_TYPE###', '<type>' . $returnUnionNew[0] . '</type>', $xml);
                }
            }
        }

        if (isset($newTypes['params'])) {
            $params = $xmlParser->findMultiOrFalse('//methodparam');
            if ($params !== false) {
                foreach ($params as $param) {
                    $paramName = $param->findOne('parameter')->text();

                    if (isset($newTypes['params'][$paramName])) {
                        $paramTypesNew = (array) \explode('|', $newTypes['params'][$paramName]);
                    } else {
                        $paramTypesNew = [];
                    }
                    $paramTypesNewCount = \count($paramTypesNew);

                    if ($paramTypesNewCount === 0) {
                        // TODO: error in phpstorm-stubs ?
                        continue;
                    }

                    $paramUnionTypeFound = $param->findOneOrFalse('type.union') !== false;

                    if ($paramUnionTypeFound && $paramTypesNewCount === 1) {
                        // TODO: error in phpstorm-stubs ?
                        continue;
                    }

                    if ($paramUnionTypeFound) {
                        $xml = (string) \preg_replace('#<methodparam(.*)><type class="union">.*</type>' . $paramName . '#Usi', '<methodparam$1>###NEW_TYPE###' . $paramName, $xml);
                    } elseif ($param->findOneOrFalse('type') !== false) {
                        $xml = (string) \preg_replace('#<methodparam(.*)><type>.*</type>' . $paramName . '#Usi', '<methodparam$1>###NEW_TYPE###' . $paramName, $xml);
                    }

                    if ($paramTypesNewCount > 1) {
                        $paramXmlTmp = '';
                        foreach ($paramTypesNew as $paramTypesNewSingle) {
                            $paramXmlTmp .= '<type>' . $paramTypesNewSingle . '</type>';
                        }
                        $xml = \str_replace('###NEW_TYPE###', '<type class="union">' . $paramXmlTmp . '</type>', $xml);
                    } else {
                        $xml = \str_replace('###NEW_TYPE###', '<type>' . $paramTypesNew[0] . '</type>', $xml);
                    }
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
}
