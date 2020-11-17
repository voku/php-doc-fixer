<?php

declare(strict_types=1);

namespace voku\PhpDocFixer\XmlDocs;

final class XmlReader
{
    private string $xml_path;

    public function __construct(string $xml_path)
    {
        $this->xml_path = $xml_path;
    }

    /**
     * @return array
     *
     * @phpstan-return array<string, array{
     *     return?: string,
     *     params?: array<string, string>,
     *     absoluteFilePath: string
     * }>
     */
    public function parse(): array
    {
        $xmlParser = new \voku\helper\XmlDomParser();
        $xmlParser->autoRemoveXPathNamespaces();

        $finder = new \Symfony\Component\Finder\Finder();
        $finder->files()->in($this->xml_path);

        $data = [[]];
        foreach ($finder as $file) {
            $fileName = $file->getFilename();
            if (\strpos($fileName, '.xml') === false) {
                continue;
            }

            $absoluteFilePath = $file->getPath() . \DIRECTORY_SEPARATOR . $fileName;
            $content = $file->getContents();

            \preg_match('#<methodsynopsis>.*</methodsynopsis>#Uis', $content, $contentMethod);
            $contentMethod = $contentMethod[0] ?? null;

            \preg_match('#<methodsynopsis role="oop">.*</methodsynopsis>#Uis', $content, $contentMethodOop);
            $contentMethodOop = $contentMethodOop[0] ?? null;

            \preg_match('#<constructorsynopsis>.*</constructorsynopsis>#Uis', $content, $contentConstructor);
            $contentConstructor = $contentConstructor[0] ?? null;

            if (!$contentMethod && !$contentMethodOop && !$contentConstructor) {
                continue;
            }

            if ($contentMethod) {
                $methodsynopsis = $xmlParser->loadXml($contentMethod);
                $data[] = $this->findTypes($methodsynopsis, $absoluteFilePath);
            }

            if ($contentMethodOop) {
                $methodsynopsisOop = $xmlParser->loadXml($contentMethodOop);
                $data[] = $this->findTypes($methodsynopsisOop, $absoluteFilePath);
            }

            if ($contentConstructor) {
                $constructorsynopsis = $xmlParser->loadXml($contentConstructor);
                $data[] = $this->findTypes($constructorsynopsis, $absoluteFilePath);
            }
        }

        return \array_merge([], ...$data);
    }

    /**
     * @param \voku\helper\XmlDomParser $xmlParser
     * @param string                    $absoluteFilePath
     *
     * @return array
     *
     * @phpstan-return array<string, array{
     *     return?: string,
     *     params?: array<string, string>,
     *     absoluteFilePath: string
     * }>
     */
    private function findTypes(
        \voku\helper\XmlDomParser $xmlParser,
        string $absoluteFilePath
    ): array {
        // init
        $data = [];

        $name = $xmlParser->findOne('//methodname')->text();
        $data[$name]['absoluteFilePath'] = $absoluteFilePath;

        $returnTypesArray = [];
        $returnTypesTmp = $xmlParser->findMultiOrFalse('type.union type');
        if ($returnTypesTmp !== false) {
            foreach ($returnTypesTmp as $returnTypeTmp) {
                $returnTypeText = $returnTypeTmp->text();
                $returnTypesArray[$returnTypeText] = \ltrim($returnTypeText, '\\');
            }
        } elseif (($returnTypeTmp = $xmlParser->findOneOrFalse('type')) !== false) {
            $returnTypeText = $returnTypeTmp->text();
            $returnTypesArray[$returnTypeText] = \ltrim($returnTypeText, '\\');
        }
        $data[$name]['return'] = \implode('|', $returnTypesArray ?? []);

        $params = $xmlParser->findMultiOrFalse('//methodparam');
        if ($params !== false) {
            foreach ($params as $param) {
                $paramName = $param->findOne('parameter')->text();
                $paramTypesArray = [];

                $paramTypesTmp = $param->findMultiOrFalse('type.union type');
                if ($paramTypesTmp !== false) {
                    foreach ($paramTypesTmp as $paramTypeTmp) {
                        $paramTypeText = $paramTypeTmp->text();
                        $paramTypesArray[$paramTypeText] = \ltrim($paramTypeText, '\\');
                    }
                } elseif (($paramTypeTmp = $param->findOneOrFalse('type')) !== false) {
                    $paramTypeText = $paramTypeTmp->text();
                    $paramTypesArray[$paramTypeText] = \ltrim($paramTypeText, '\\');
                }

                $data[$name]['params'][$paramName] = \implode('|', $paramTypesArray ?? []);
            }
        }

        return $data;
    }
}
