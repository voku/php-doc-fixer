<?php

declare(strict_types=1);

namespace voku\PhpDocFixer\Type;

final class TypeNormalizer
{
    private bool $removeArrayValueInfo;

    public function __construct(bool $removeArrayValueInfo = false)
    {
        $this->removeArrayValueInfo = $removeArrayValueInfo;
    }

    public function normalize(string $type): string
    {
        $type = \trim($type);

        if (isset($type[0]) && $type[0] === '?') {
            $type = \trim(\substr($type, 1));
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

    public static function removeArrayValueInfo(string $phpdoc_type): string
    {
        $phpdoc_type = (string) \preg_replace('#([\w_]*\[\])#', 'array', $phpdoc_type);

        $phpdoc_type = (string) \preg_replace('#(array{.*})#', 'array', $phpdoc_type);

        $phpdoc_type = (string) \preg_replace('#(array<.*>)#', 'array', $phpdoc_type);

        $phpdoc_type = (string) \preg_replace('#(list<.*>)#', 'array', $phpdoc_type);

        return $phpdoc_type;
    }

    private function normalizeTypePart(string $typePart): string
    {
        $typePart = \trim($typePart);
        $typePart = \ltrim($typePart, '\\');

        if ($this->removeArrayValueInfo) {
            $typePart = self::removeArrayValueInfo($typePart);
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
        $typePart = (string) \preg_replace('/^(?:pure-)?callable\(.*\)(?::.+)?$/', 'callable', $typePart);
        $typePart = (string) \preg_replace('/^Closure\(.*\)(?::.+)?$/', 'callable', $typePart);
        $typePart = (string) \preg_replace('/\bobject\{.*\}/', 'object', $typePart);
        $typePart = (string) \preg_replace('/\barray-key\b/', 'int|string', $typePart);
        $typePart = (string) \preg_replace('/^resource \(closed\)$/', 'resource', $typePart);

        if (\preg_match('/^[A-Za-z_][A-Za-z0-9_\\\\]*<.*>$/', $typePart)) {
            $typePart = (string) \preg_replace('/<.*>$/', '', $typePart);
        }

        return $typePart;
    }
}
