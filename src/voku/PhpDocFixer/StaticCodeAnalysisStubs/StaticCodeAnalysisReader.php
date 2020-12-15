<?php

declare(strict_types=1);

namespace voku\PhpDocFixer\StaticCodeAnalysisStubs;

final class StaticCodeAnalysisReader
{
    private string $path;

    private bool $removeArrayValueInfo;

    private string $stubsFileExtension;

    public function __construct(
        string $path,
        bool $removeArrayValueInfo = false
    )
    {
        $this->path = $path;
        $this->removeArrayValueInfo = $removeArrayValueInfo;
    }

    /**
     * @return array
     *
     * @phpstan-return array<string, array{return: string, params?: array<string, string>}>
     */
    public function parse(): array
    {
        /** @noinspection PhpIncludeInspection */
        /** @noinspection UsingInclusionReturnValueInspection */
        $data = require $this->path;

        $return = [];
        foreach ($data as $functionName => $info) {
            $returnType = array_shift($info);

            if (strpos($returnType, '?') !== false) {
                $returnType = str_replace('?', '', $returnType);
                $returnType .= '|null';
            }

            $returnType = explode('|', $returnType);
            sort($returnType);
            $returnType = implode('|', $returnType);

            $return[$functionName]['return'] = \ltrim($returnType, '\\');
            if ($this->removeArrayValueInfo) {
                $return[$functionName]['return'] = $this->removeArrayValueInfo($return[$functionName]['return']);
            }
            if ($return[$functionName]['return'] === '') {
                $return[$functionName]['return'] = 'void';
            }

            foreach ($info as $paramName => $paramTypes) {
                if (strpos($paramTypes, '?') !== false) {
                    $paramTypes = str_replace('?', '', $paramTypes);
                    $paramTypes .= '|null';
                }

                $paramTypes = explode('|', $paramTypes);
                sort($paramTypes);
                $paramTypes = implode('|', $paramTypes);


                $return[$functionName]['params'][$paramName] = \ltrim($paramTypes ?? '', '\\');
                if ($this->removeArrayValueInfo) {
                    $return[$functionName]['params'][$paramName] = $this->removeArrayValueInfo($return[$functionName]['params'][$paramName]);
                }
            }
        }

        return $return;
    }

    /**
     * @param string $phpdoc_type
     *
     * @return string
     */
    public function removeArrayValueInfo(string $phpdoc_type): string
    {
        $phpdoc_type = (string) \preg_replace('#([\w_]*\[\])#', 'array', $phpdoc_type);

        $phpdoc_type = (string) \preg_replace('#(array{.*})#', 'array', $phpdoc_type);

        $phpdoc_type = (string) \preg_replace('#(array<.*>)#', 'array', $phpdoc_type);

        $phpdoc_type = (string) \preg_replace('#(list<.*>)#', 'array', $phpdoc_type);

        return $phpdoc_type;
    }
}
