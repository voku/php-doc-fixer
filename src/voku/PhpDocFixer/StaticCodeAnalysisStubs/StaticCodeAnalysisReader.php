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
        $phpdoc_type = (string) \preg_replace('#([\w_]*\[\])#', 'array', $phpdoc_type);

        $phpdoc_type = (string) \preg_replace('#(array{.*})#', 'array', $phpdoc_type);

        $phpdoc_type = (string) \preg_replace('#(array<.*>)#', 'array', $phpdoc_type);

        $phpdoc_type = (string) \preg_replace('#(list<.*>)#', 'array', $phpdoc_type);

        return $phpdoc_type;
    }

    private function normalizeType(string $type): string
    {
        if (\strpos($type, '?') !== false) {
            $type = \str_replace('?', '', $type);
            $type .= '|null';
        }

        $typeParts = \explode('|', $type);
        $normalizedTypeParts = [];
        foreach ($typeParts as $typePart) {
            $normalizedTypePart = $this->normalizeTypePart($typePart);
            foreach (\explode('|', $normalizedTypePart) as $normalizedTypePartInner) {
                $normalizedTypePartInner = \trim($normalizedTypePartInner);
                if ($normalizedTypePartInner === '') {
                    continue;
                }

                $normalizedTypeParts[] = $normalizedTypePartInner;
            }
        }

        if (\in_array('bool', $normalizedTypeParts, true)) {
            $normalizedTypeParts = \array_values(
                \array_filter(
                    $normalizedTypeParts,
                    static fn (string $normalizedTypePart): bool => $normalizedTypePart !== 'true' && $normalizedTypePart !== 'false'
                )
            );
        } elseif (\in_array('true', $normalizedTypeParts, true) && \in_array('false', $normalizedTypeParts, true)) {
            $normalizedTypeParts = \array_values(
                \array_filter(
                    $normalizedTypeParts,
                    static fn (string $normalizedTypePart): bool => $normalizedTypePart !== 'true' && $normalizedTypePart !== 'false'
                )
            );
            $normalizedTypeParts[] = 'bool';
        }

        $normalizedTypeParts = \array_values(\array_unique($normalizedTypeParts));
        \sort($normalizedTypeParts);

        return \implode('|', $normalizedTypeParts);
    }

    private function normalizeTypePart(string $typePart): string
    {
        $typePart = \trim($typePart);
        $typePart = \ltrim($typePart, '\\');

        if ($this->removeArrayValueInfo) {
            $typePart = $this->removeArrayValueInfo($typePart);
        }

        $typePart = (string) \preg_replace('/\b(?:positive-int|negative-int|non-negative-int|non-positive-int)\b/', 'int', $typePart);
        $typePart = (string) \preg_replace('/\bint<(?:[^>]+)>/', 'int', $typePart);
        $typePart = (string) \preg_replace('/\bint-mask(?:-of)?<(?:[^>]+)>/', 'int', $typePart);
        $typePart = (string) \preg_replace('/\b(?:non-empty-string|non-falsy-string|numeric-string|literal-string|lowercase-string|uppercase-string|class-string(?:<[^>]+>)?|callable-string|trait-string|interface-string|enum-string)\b/', 'string', $typePart);
        $typePart = (string) \preg_replace('/\bnon-empty-array<.*>/', 'array', $typePart);
        $typePart = (string) \preg_replace('/\barray<.*>/', 'array', $typePart);
        $typePart = (string) \preg_replace('/\blist<.*>/', 'array', $typePart);
        $typePart = (string) \preg_replace('/\barray\{.*\}/', 'array', $typePart);
        $typePart = (string) \preg_replace('/\biterable<.*>/', 'iterable', $typePart);
        $typePart = (string) \preg_replace('/\b(?:pure-)?callable\(.*\)/', 'callable', $typePart);
        $typePart = (string) \preg_replace('/\bClosure\(.*\)/', 'callable', $typePart);
        $typePart = (string) \preg_replace('/\bobject\{.*\}/', 'object', $typePart);
        $typePart = (string) \preg_replace('/\barray-key\b/', 'int|string', $typePart);
        $typePart = (string) \preg_replace('/\bresource \(closed\)\b/', 'resource', $typePart);

        if (\preg_match('/^[A-Za-z_][A-Za-z0-9_\\\\]*<.*>$/', $typePart)) {
            $typePart = (string) \preg_replace('/<.*>$/', '', $typePart);
        }

        return $typePart;
    }
}
