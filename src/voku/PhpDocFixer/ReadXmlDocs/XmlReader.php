<?php

declare(strict_types=1);

namespace voku\PhpDocFixer\ReadXmlDocs;

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
     * @phpstan-return array<string, array{return: array<string, string>, params: array<string, array<string, string>}>
     */
    public function parse(): array
    {
        $xmlParser = new \voku\helper\XmlDomParser();
        $xmlParser->autoRemoveXPathNamespaces();

        $finder = new \Symfony\Component\Finder\Finder();
        $finder->files()->in($this->xml_path);

        $data = [[]];
        foreach ($finder as $file) {
            if (\strpos($file->getFilename(), '.xml') === false) {
                continue;
            }

            $content = \trim($file->getContents());

            $contentMethod = \voku\helper\UTF8::between($content, '<methodsynopsis>', '</methodsynopsis>');
            $contentConstructor = \voku\helper\UTF8::between($content, '<constructorsynopsis>', '</constructorsynopsis>');

            if (!$contentMethod && !$contentConstructor) {
                continue;
            }

            if ($contentMethod) {
                $methodsynopsis = $xmlParser->loadXml('<methodsynopsis>' . $contentMethod . '</methodsynopsis>');
                $data[] = $this->findTypes($methodsynopsis);
            }

            if ($contentConstructor) {
                $constructorsynopsis = $xmlParser->loadXml('<methodsynopsis>' . $contentConstructor . '</methodsynopsis>');
                $data[] = $this->findTypes($constructorsynopsis);
            }
        }

        return \array_merge([], ...$data);
    }

    /**
     * @param \voku\helper\XmlDomParser $xmlParser
     *
     * @phpstan-return array<string, array{return: string, params: array<string, string>>
     */
    private function findTypes(\voku\helper\XmlDomParser $xmlParser): array
    {
        // init
        $data = [];

        $name = $xmlParser->findOne('//methodname')->text();
        $returnTypesTmp = $xmlParser->findMultiOrFalse('type.union type');
        if ($returnTypesTmp !== false) {
            foreach ($returnTypesTmp as $returnTypeTmp) {
                $returnTypeText = $returnTypeTmp->text();
                $data[$name]['return'][$returnTypeText] = \ltrim($returnTypeText, '\\');
            }
        } elseif (($returnTypeTmp = $xmlParser->findOneOrFalse('type')) !== false) {
            $returnTypeText = $returnTypeTmp->text();
            $data[$name]['return'][$returnTypeText] = \ltrim($returnTypeText, '\\');
        }
        $data[$name]['return'] = \implode('|', $data[$name]['return'] ?? []);

        $params = $xmlParser->findMultiOrFalse('//methodparam');
        if ($params !== false) {
            foreach ($params as $param) {
                $paramName = $param->findOne('parameter')->text();

                $paramTypesTmp = $param->findMultiOrFalse('type.union type');
                if ($paramTypesTmp !== false) {
                    foreach ($paramTypesTmp as $paramTypeTmp) {
                        $paramTypeText = $paramTypeTmp->text();
                        $data[$name]['params'][$paramName][$paramTypeText] = \ltrim($paramTypeText, '\\');
                    }
                } elseif (($paramTypeTmp = $param->findOneOrFalse('type')) !== false) {
                    $paramTypeText = $paramTypeTmp->text();
                    $data[$name]['params'][$paramName][$paramTypeText] = \ltrim($paramTypeText, '\\');
                }

                $data[$name]['params'][$paramName] = \implode('|', $data[$name]['params'][$paramName] ?? []);
            }
        }

        return $data;
    }
}
