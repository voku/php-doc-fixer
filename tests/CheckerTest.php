<?php

declare(strict_types=1);

namespace voku\tests;

/**
 * @internal
 */
final class CheckerTest extends \PHPUnit\Framework\TestCase
{
    public static function testPhpStubsReader(): void
    {
        $PhpStubsPath = __DIR__ . '/../vendor/jetbrains/phpstorm-stubs/mbstring/';
        $phpTypesFromPhpStubs = new \voku\PhpDocFixer\PhpStubs\PhpStubsReader($PhpStubsPath);
        $PhpStubsInfo = $phpTypesFromPhpStubs->parse();

        $expected = [
            'return' => 'false|int',
            'params' => [
                'haystack' => 'string',
                'needle'   => 'string',
                'offset'   => 'int',
                'encoding' => 'null|string',
            ],
        ];

        static::assertSame($expected, $PhpStubsInfo['mb_strpos']);
    }

    public static function testPhpStubsReaderHandlesDeprecatedConstantsInCurrentStubs(): void
    {
        $PhpStubsPath = __DIR__ . '/../vendor/jetbrains/phpstorm-stubs/imap/';
        $phpTypesFromPhpStubs = new \voku\PhpDocFixer\PhpStubs\PhpStubsReader($PhpStubsPath);

        $deprecations = [];
        \set_error_handler(
            static function (int $severity, string $message) use (&$deprecations): bool {
                if (($severity & (\E_DEPRECATED | \E_USER_DEPRECATED)) !== 0) {
                    $deprecations[] = $message;

                    return true;
                }

                return false;
            }
        );

        try {
            $PhpStubsInfo = $phpTypesFromPhpStubs->parse();
        } finally {
            \restore_error_handler();
        }

        static::assertSame([], $deprecations);
        static::assertSame('int', $PhpStubsInfo['imap_setflag_full']['params']['options']);
    }

    public static function testPhpDocXmlReader(): void
    {
        $xmlPath = __DIR__ . '/fixtures/bcpow.xml';
        $phpDocXmlReader = new \voku\PhpDocFixer\XmlDocs\XmlReader($xmlPath);
        $phpDocXmlReaderInfo = self::removeLocalPathForTheTest($phpDocXmlReader->parse());

        $expected = [
            'bcpow' => [
                'absoluteFilePath' => 'php-doc-fixer/tests/fixtures/bcpow.xml',
                'return'           => 'string',
                'params'           => [
                    'num'      => 'string',
                    'exponent' => 'string',
                    'scale'    => 'int|null',
                ],
            ],
        ];

        static::assertSame($expected, $phpDocXmlReaderInfo);
    }

    /**
     * @param array $result
     *
     * @return array
     */
    public static function removeLocalPathForTheTest(array $result): array
    {
        // hack for CI
        $pathReplace = \realpath(\getcwd() . '/../') . '/';
        if (!\is_array($result)) {
            return $result;
        }

        $helper = [];
        foreach ($result as $key => $value) {
            if (\is_string($key)) {
                $key = (string) \str_replace($pathReplace, '', $key);
            }

            if (\is_array($value)) {
                $helper[$key] = self::removeLocalPathForTheTest($value);
            } else {
                if (\is_string($value)) {
                    $helper[$key] = \str_replace($pathReplace, '', $value);
                } else {
                    $helper[$key] = $value;
                }
            }
        }

        return $helper;
    }
}
