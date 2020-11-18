<?php

declare(strict_types=1);

namespace voku\PhpDocFixer\PhpStormStubs;

final class PhpStormStubsReader
{
    private string $path;

    private bool $removeArrayValueInfo;

    public function __construct(string $path, bool $removeArrayValueInfo = false)
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
        $phpCode = \voku\SimplePhpParser\Parsers\PhpCodeParser::getPhpFiles($this->path);

        $return = [];
        $functionInfo = $phpCode->getFunctionsInfo();
        foreach ($functionInfo as $functionName => $info) {
            $return[$functionName]['return'] = \ltrim($info['returnTypes']['typeFromPhpDocSimple'] ?? '', '\\');
            if ($this->removeArrayValueInfo) {
                $return[$functionName]['return'] = $this->removeArrayValueInfo($return[$functionName]['return']);
            }
            foreach ($info['paramsTypes'] as $paramName => $paramTypes) {
                $return[$functionName]['params'][$paramName] = \ltrim($paramTypes['typeFromPhpDocSimple'] ?? '', '\\');
                if ($this->removeArrayValueInfo) {
                    $return[$functionName]['params'][$paramName] = $this->removeArrayValueInfo($return[$functionName]['params'][$paramName]);
                }
            }
        }

        foreach ($phpCode->getClasses() as $class) {
            $methodInfo = $class->getMethodsInfo();
            $className = (string) $class->name;
            foreach ($methodInfo as $methodName => $info) {
                $return[$className . '::' . $methodName]['return'] = \ltrim($info['returnTypes']['typeFromPhpDocSimple'] ?? '', '\\');
                if ($this->removeArrayValueInfo) {
                    $return[$className . '::' . $methodName]['return'] = $this->removeArrayValueInfo($return[$className . '::' . $methodName]['return']);
                }
                foreach ($info['paramsTypes'] as $paramName => $paramTypes) {
                    $return[$className . '::' . $methodName]['params'][$paramName] = \ltrim($paramTypes['typeFromPhpDocSimple'] ?? '', '\\');
                    if ($this->removeArrayValueInfo) {
                        $return[$className . '::' . $methodName]['params'][$paramName] = $this->removeArrayValueInfo($return[$className . '::' . $methodName]['params'][$paramName]);
                    }
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
