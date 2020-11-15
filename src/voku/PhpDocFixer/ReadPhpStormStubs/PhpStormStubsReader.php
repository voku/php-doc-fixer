<?php

declare(strict_types=1);

namespace voku\PhpDocFixer\ReadPhpStormStubs;

final class PhpStormStubsReader
{
    private string $path;

    public function __construct(string $path)
    {
        $this->path = $path;
    }

    /**
     * @return array
     *
     * @phpstan-return array<string, array{return: string, params?: array<string, string>}>
     */
    public function parse(): array
    {
        $phpCode = \voku\SimplePhpParser\Parsers\PhpCodeParser::getPhpFiles($this->path);

        $return = [];
        $functionInfo = $phpCode->getFunctionsInfo();
        foreach ($functionInfo as $functionName => $info) {
            $return[$functionName]['return'] = \ltrim($info['returnTypes']['typeFromPhpDocSimple'] ?? '', '\\');
            foreach ($info['paramsTypes'] as $paramName => $paramTypes) {
                $return[$functionName]['params'][$paramName] = \ltrim($paramTypes['typeFromPhpDocSimple'] ?? '', '\\');
            }
        }

        foreach ($phpCode->getClasses() as $class) {
            $methodInfo = $class->getMethodsInfo();
            foreach ($methodInfo as $methodName => $info) {
                $return[$class->name . '::' . $methodName]['return'] = \ltrim($info['returnTypes']['typeFromPhpDocSimple'] ?? '', '\\');
                foreach ($info['paramsTypes'] as $paramName => $paramTypes) {
                    $return[$class->name . '::' . $methodName]['params'][$paramName] = \ltrim($paramTypes['typeFromPhpDocSimple'] ?? '', '\\');
                }
            }
        }

        return $return;
    }
}
