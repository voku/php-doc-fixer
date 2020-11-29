<?php

declare(strict_types=1);

namespace voku\PhpDocFixer\PhpStanStubs;

final class PhpStanReader
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

            $return[$functionName]['return'] = \ltrim($returnType, '\\');
            if ($this->removeArrayValueInfo) {
                $return[$functionName]['return'] = $this->removeArrayValueInfo($return[$functionName]['return']);
            }
            if ($return[$functionName]['return'] === '') {
                $return[$functionName]['return'] = 'void';
            }

            foreach ($info as $paramName => $paramTypes) {
                if (strpos($paramName, '=') !== false) {
                    $paramTypes .= '|null';
                }

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
        return (string) \preg_replace('#([\w_]*\[\])#', 'array', $phpdoc_type);
    }
}
