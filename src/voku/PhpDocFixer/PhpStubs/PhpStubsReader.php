<?php

declare(strict_types=1);

namespace voku\PhpDocFixer\PhpStubs;

final class PhpStubsReader
{
    private string $path;

    private bool $removeArrayValueInfo;

    private string $stubsFileExtension;

    public function __construct(
        string $path,
        bool $removeArrayValueInfo = false,
        string $stubsFileExtension = '.php'
    )
    {
        $this->path = $path;
        $this->removeArrayValueInfo = $removeArrayValueInfo;
        $this->stubsFileExtension = $stubsFileExtension;
    }

    /**
     * @return array
     *
     * @phpstan-return array<string, array{return: string, params?: array<string, string>}>
     */
    public function parse(): array
    {
        \set_error_handler(
            static function (int $severity, string $message): bool {
                if (($severity & (\E_DEPRECATED | \E_USER_DEPRECATED)) === 0) {
                    return false;
                }

                return (bool) \preg_match('/^Constant .* is deprecated$/', $message);
            }
        );

        try {
            $phpCode = \voku\SimplePhpParser\Parsers\PhpCodeParser::getPhpFiles(
                $this->path,
                [],
                [],
                [$this->stubsFileExtension]
            );

            $return = [];
            $functionInfo = $phpCode->getFunctionsInfo();
            foreach ($functionInfo as $functionName => $info) {
                $returnTypeTmp = $this->normalizeType(
                    $info['returnTypes']['type'] ?? null,
                    $info['returnTypes']['typeFromPhpDocSimple'] ?? null
                );

                $return[$functionName]['return'] = $returnTypeTmp;
                if ($return[$functionName]['return'] === '') {
                    $return[$functionName]['return'] = 'void';
                }

                foreach ($info['paramsTypes'] as $paramName => $paramTypes) {
                    $paramTypeTmp = $this->normalizeType(
                        $paramTypes['type'] ?? null,
                        $paramTypes['typeFromPhpDocSimple'] ?? null
                    );

                    $return[$functionName]['params'][$paramName] = $paramTypeTmp;
                }
            }

            foreach ($phpCode->getClasses() as $class) {
                $methodInfo = $class->getMethodsInfo();
                $className = (string) $class->name;
                foreach ($methodInfo as $methodName => $info) {
                    $returnTypeTmp = $this->normalizeType(
                        $info['returnTypes']['type'] ?? null,
                        $info['returnTypes']['typeFromPhpDocSimple'] ?? null
                    );

                    $return[$className . '::' . $methodName]['return'] = $returnTypeTmp;
                    if ($return[$className . '::' . $methodName]['return'] === '') {
                        $return[$className . '::' . $methodName]['return'] = 'void';
                    }

                    foreach ($info['paramsTypes'] as $paramName => $paramTypes) {
                        $paramTypeTmp = $this->normalizeType(
                            $paramTypes['type'] ?? null,
                            $paramTypes['typeFromPhpDocSimple'] ?? null
                        );

                        $return[$className . '::' . $methodName]['params'][$paramName] = $paramTypeTmp;
                    }
                }
            }

            return $return;
        } finally {
            \restore_error_handler();
        }
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

    private function normalizeType(?string $nativeType, ?string $phpDocType): string
    {
        $type = $nativeType ?? $phpDocType ?? '';
        $typeTmp = explode('|', $type);

        foreach ($typeTmp as &$typeInnerTmp) {
            if ($this->removeArrayValueInfo) {
                $typeInnerTmp = $this->removeArrayValueInfo($typeInnerTmp);
            }

            $typeInnerTmp = \ltrim($typeInnerTmp, '\\');
        }

        sort($typeTmp);

        return implode('|', $typeTmp);
    }
}
