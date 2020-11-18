<?php

declare(strict_types=1);

namespace voku\tests;

/**
 * @internal
 */
final class CheckerTest extends \PHPUnit\Framework\TestCase
{
    public static function testPhpStormStubsReader(): void
    {
        $phpStormStubsPath = __DIR__ . '/../vendor/jetbrains/phpstorm-stubs/mbstring/';
        $phpTypesFromPhpStormStubs = new \voku\PhpDocFixer\PhpStormStubs\PhpStormStubsReader($phpStormStubsPath);
        $phpStormStubsInfo = $phpTypesFromPhpStormStubs->parse();

        $expected = [
            'return' => 'int|false',
            'params' => [
                'haystack' => 'string',
                'needle'   => 'string',
                'offset'   => 'int',
                'encoding' => 'string',
            ],
        ];

        static::assertSame($expected, $phpStormStubsInfo['mb_strpos']);
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
