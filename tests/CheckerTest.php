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
        $phpTypesFromPhpStormStubs = new \voku\PhpDocFixer\ReadPhpStormStubs\PhpStormStubsReader($phpStormStubsPath);
        $phpStormStubsInfo = $phpTypesFromPhpStormStubs->parse();

        self::assertSame('int|false', $phpStormStubsInfo['mb_strpos']['return']);
    }
}
