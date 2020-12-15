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
        $phpCode = \voku\SimplePhpParser\Parsers\PhpCodeParser::getPhpFiles(
            $this->path,
            [],
            [],
            [$this->stubsFileExtension]
        );

        $return = [];
        $functionInfo = $phpCode->getFunctionsInfo();
        foreach ($functionInfo as $functionName => $info) {

            $returnTypeTmp = explode('|', $info['returnTypes']['typeFromPhpDocSimple'] ?? '');
            sort($returnTypeTmp);
            $returnTypeTmp = implode('|', $returnTypeTmp);

            $return[$functionName]['return'] = \ltrim($returnTypeTmp, '\\');
            if ($this->removeArrayValueInfo) {
                $return[$functionName]['return'] = $this->removeArrayValueInfo($return[$functionName]['return']);
            }
            if ($return[$functionName]['return'] === '') {
                $return[$functionName]['return'] = 'void';
            }

            foreach ($info['paramsTypes'] as $paramName => $paramTypes) {

                $paramTypeTmp = explode('|', $paramTypes['typeFromPhpDocSimple'] ?? '');
                sort($paramTypeTmp);
                $paramTypeTmp = implode('|', $paramTypeTmp);

                $return[$functionName]['params'][$paramName] = \ltrim($paramTypeTmp, '\\');
                if ($this->removeArrayValueInfo) {
                    $return[$functionName]['params'][$paramName] = $this->removeArrayValueInfo($return[$functionName]['params'][$paramName]);
                }
            }
        }

        foreach ($phpCode->getClasses() as $class) {
            $methodInfo = $class->getMethodsInfo();
            $className = (string) $class->name;
            foreach ($methodInfo as $methodName => $info) {

                $returnTypeTmp = explode('|', $info['returnTypes']['typeFromPhpDocSimple'] ?? '');
                sort($returnTypeTmp);
                $returnTypeTmp = implode('|', $returnTypeTmp);

                $return[$className . '::' . $methodName]['return'] = \ltrim($returnTypeTmp, '\\');
                if ($this->removeArrayValueInfo) {
                    $return[$className . '::' . $methodName]['return'] = $this->removeArrayValueInfo($return[$className . '::' . $methodName]['return']);
                }
                if ($return[$className . '::' . $methodName]['return'] === '') {
                    $return[$className . '::' . $methodName]['return'] = 'void';
                }

                foreach ($info['paramsTypes'] as $paramName => $paramTypes) {

                    $paramTypeTmp = explode('|', $paramTypes['typeFromPhpDocSimple'] ?? '');
                    sort($paramTypeTmp);
                    $paramTypeTmp = implode('|', $paramTypeTmp);

                    $return[$className . '::' . $methodName]['params'][$paramName] = \ltrim($paramTypeTmp, '\\');
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
        $phpdoc_type = (string) \preg_replace('#([\w_]*\[\])#', 'array', $phpdoc_type);

        $phpdoc_type = (string) \preg_replace('#(array{.*})#', 'array', $phpdoc_type);

        $phpdoc_type = (string) \preg_replace('#(array<.*>)#', 'array', $phpdoc_type);

        $phpdoc_type = (string) \preg_replace('#(list<.*>)#', 'array', $phpdoc_type);

        return $phpdoc_type;
    }
}
