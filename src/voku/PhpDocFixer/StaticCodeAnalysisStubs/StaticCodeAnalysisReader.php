<?php

declare(strict_types=1);

namespace voku\PhpDocFixer\StaticCodeAnalysisStubs;

use voku\PhpDocFixer\Type\TypeNormalizer;

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
            $returnType = $this->normalizeType((string) $returnType);

            $return[$functionName]['return'] = $returnType;
            if ($return[$functionName]['return'] === '') {
                $return[$functionName]['return'] = 'void';
            }

            foreach ($info as $paramName => $paramTypes) {
                $paramTypes = $this->normalizeType($paramTypes);

                $return[$functionName]['params'][$paramName] = $paramTypes ?? '';
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
        return TypeNormalizer::removeArrayValueInfo($phpdoc_type);
    }

    private function normalizeType(string $type): string
    {
        return (new TypeNormalizer($this->removeArrayValueInfo))->normalize($type);
    }
}
