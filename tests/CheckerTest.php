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
            'return' => 'int|false',
            'params' => [
                'haystack' => 'string',
                'needle'   => 'string',
                'offset'   => 'int',
                'encoding' => 'string',
            ],
        ];

        static::assertSame($expected, $PhpStubsInfo['mb_strpos']);
    }

    public static function testPhpDocXmlReader(): void
    {
        $xmlPath = __DIR__ . '/fixtures/bcpow.xml';
        $phpDocXmlReader = new \voku\PhpDocFixer\XmlDocs\XmlReader($xmlPath);
        $phpDocXmlReaderInfo = $phpDocXmlReader->parse();

        $expected = [
            'bcpow' => [
                'absoluteFilePath' => '/home/lmoelleken/testing/git/php-doc-fixer/tests/fixtures/bcpow.xml',
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
}
