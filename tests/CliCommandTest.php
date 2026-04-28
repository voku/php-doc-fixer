<?php

declare(strict_types=1);

namespace voku\tests;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;
use voku\PhpDocFixer\CliCommand\PhpDocFixerCommand;
use voku\PhpDocFixer\CliCommand\StaticAnalysisFixerCommand;

/**
 * @internal
 */
final class CliCommandTest extends \PHPUnit\Framework\TestCase
{
    public function testPhpDocFixerCommandSucceedsForMatchingDocumentation(): void
    {
        $commandTester = new CommandTester(new PhpDocFixerCommand());
        $exitCode = $commandTester->execute([
            'path' => __DIR__ . '/fixtures/bcpow.xml',
            '--stubs-path' => __DIR__ . '/../vendor/jetbrains/phpstorm-stubs/bcmath',
        ]);

        static::assertSame(Command::SUCCESS, $exitCode);
        static::assertStringContainsString('0 errors found', $commandTester->getDisplay());
    }

    public function testPhpDocFixerCommandFailsForDocumentationMismatch(): void
    {
        $commandTester = new CommandTester(new PhpDocFixerCommand());
        $exitCode = $commandTester->execute([
            'path' => __DIR__ . '/fixtures/bcpow-invalid.xml',
            '--stubs-path' => __DIR__ . '/../vendor/jetbrains/phpstorm-stubs/bcmath',
        ]);

        static::assertSame(Command::FAILURE, $exitCode);
        static::assertStringContainsString('1 errors found', $commandTester->getDisplay());
        static::assertStringContainsString('bcpow', $commandTester->getDisplay());
    }

    public function testPhpDocFixerCommandFailsForProceduralSynopsisMismatchInMultiSynopsisFile(): void
    {
        $commandTester = new CommandTester(new PhpDocFixerCommand());
        $exitCode = $commandTester->execute([
            'path' => __DIR__ . '/fixtures/multi-synopsis-invalid.xml',
            '--stubs-path' => __DIR__ . '/fixtures/stubs/multi-synopsis',
        ]);

        static::assertSame(Command::FAILURE, $exitCode);
        static::assertStringContainsString('1 errors found', $commandTester->getDisplay());
        static::assertStringContainsString('first_function', $commandTester->getDisplay());
    }

    public function testPhpDocFixerCommandAutoFixesMatchingSynopsisInMultiSynopsisFile(): void
    {
        $tempDirectory = \sys_get_temp_dir() . '/php-doc-fixer-' . \bin2hex(\random_bytes(8));
        static::assertTrue(\mkdir($tempDirectory, 0777, true));

        $tempPath = $tempDirectory . '/multi-synopsis-invalid.xml';
        static::assertTrue(\copy(__DIR__ . '/fixtures/multi-synopsis-invalid.xml', $tempPath));

        try {
            $commandTester = new CommandTester(new PhpDocFixerCommand());
            $exitCode = $commandTester->execute([
                'path' => $tempPath,
                '--auto-fix' => 'true',
                '--stubs-path' => __DIR__ . '/fixtures/stubs/multi-synopsis',
            ]);

            static::assertSame(Command::SUCCESS, $exitCode);
            static::assertStringContainsString('1 errors found', $commandTester->getDisplay());
            static::assertStringContainsString('1 entries updated', $commandTester->getDisplay());
            static::assertStringContainsString('0 errors remaining after auto-fix', $commandTester->getDisplay());
            static::assertStringContainsString('<type>string</type><methodname>first_function</methodname>', (string) \file_get_contents($tempPath));
            static::assertStringContainsString('<type>int</type><methodname>SecondClass::secondMethod</methodname>', (string) \file_get_contents($tempPath));

            $commandTester = new CommandTester(new PhpDocFixerCommand());
            $exitCode = $commandTester->execute([
                'path' => $tempPath,
                '--stubs-path' => __DIR__ . '/fixtures/stubs/multi-synopsis',
            ]);

            static::assertSame(Command::SUCCESS, $exitCode);
            static::assertStringContainsString('0 errors found', $commandTester->getDisplay());
        } finally {
            \unlink($tempPath);
            \rmdir($tempDirectory);
        }
    }

    public function testPhpDocFixerCommandAutoFixesMultipleMismatchesInOneFile(): void
    {
        $tempDirectory = \sys_get_temp_dir() . '/php-doc-fixer-' . \bin2hex(\random_bytes(8));
        static::assertTrue(\mkdir($tempDirectory, 0777, true));

        $tempPath = $tempDirectory . '/multi-synopsis-both-invalid.xml';
        static::assertTrue(\copy(__DIR__ . '/fixtures/multi-synopsis-both-invalid.xml', $tempPath));

        try {
            $commandTester = new CommandTester(new PhpDocFixerCommand());
            $exitCode = $commandTester->execute([
                'path' => $tempPath,
                '--auto-fix' => 'true',
                '--stubs-path' => __DIR__ . '/fixtures/stubs/multi-synopsis',
            ]);

            static::assertSame(Command::SUCCESS, $exitCode);
            static::assertStringContainsString('2 errors found', $commandTester->getDisplay());
            static::assertStringContainsString('2 entries updated', $commandTester->getDisplay());
            static::assertStringContainsString('0 errors remaining after auto-fix', $commandTester->getDisplay());
            static::assertStringContainsString('<type>string</type><methodname>first_function</methodname>', (string) \file_get_contents($tempPath));
            static::assertStringContainsString('<type>int</type><methodname>SecondClass::secondMethod</methodname>', (string) \file_get_contents($tempPath));
            static::assertStringContainsString('<methodparam><type>int</type><parameter>count</parameter></methodparam>', (string) \file_get_contents($tempPath));
        } finally {
            \unlink($tempPath);
            \rmdir($tempDirectory);
        }
    }

    public function testPhpDocFixerCommandAutoFixReportsRemainingErrorsWhenMismatchCannotBeApplied(): void
    {
        $tempDirectory = \sys_get_temp_dir() . '/php-doc-fixer-' . \bin2hex(\random_bytes(8));
        static::assertTrue(\mkdir($tempDirectory, 0777, true));

        $tempPath = $tempDirectory . '/reference-matching-invalid.xml';
        static::assertTrue(\copy(__DIR__ . '/fixtures/reference-matching-invalid.xml', $tempPath));

        try {
            $commandTester = new CommandTester(new PhpDocFixerCommand());
            $exitCode = $commandTester->execute([
                'path' => $tempPath,
                '--auto-fix' => 'true',
                '--stubs-path' => __DIR__ . '/fixtures/stubs/reference-matching',
            ]);

            static::assertSame(Command::FAILURE, $exitCode);
            static::assertStringContainsString('1 errors found', $commandTester->getDisplay());
            static::assertStringContainsString('0 entries updated', $commandTester->getDisplay());
            static::assertStringContainsString('1 errors remaining after auto-fix', $commandTester->getDisplay());
            static::assertStringContainsString('[expected] => string', $commandTester->getDisplay());
            static::assertStringContainsString('[received] => string', $commandTester->getDisplay());
        } finally {
            \unlink($tempPath);
            \rmdir($tempDirectory);
        }
    }

    public function testPhpDocFixerCommandAutoFixesUnionTypesToSingleTypes(): void
    {
        $tempDirectory = \sys_get_temp_dir() . '/php-doc-fixer-' . \bin2hex(\random_bytes(8));
        static::assertTrue(\mkdir($tempDirectory, 0777, true));

        $tempPath = $tempDirectory . '/union-to-single-invalid.xml';
        static::assertTrue(\copy(__DIR__ . '/fixtures/union-to-single-invalid.xml', $tempPath));

        try {
            $commandTester = new CommandTester(new PhpDocFixerCommand());
            $exitCode = $commandTester->execute([
                'path' => $tempPath,
                '--auto-fix' => 'true',
                '--stubs-path' => __DIR__ . '/fixtures/stubs/union-to-single',
            ]);

            static::assertSame(Command::SUCCESS, $exitCode);
            static::assertStringContainsString('1 errors found', $commandTester->getDisplay());
            static::assertStringContainsString('1 entries updated', $commandTester->getDisplay());
            static::assertStringContainsString('0 errors remaining after auto-fix', $commandTester->getDisplay());
            static::assertStringContainsString('<type>string</type><methodname>union_to_single</methodname>', (string) \file_get_contents($tempPath));
            static::assertStringContainsString('<methodparam><type>int</type><parameter>value</parameter></methodparam>', (string) \file_get_contents($tempPath));
            static::assertStringNotContainsString('<type class="union">', (string) \file_get_contents($tempPath));
        } finally {
            \unlink($tempPath);
            \rmdir($tempDirectory);
        }
    }

    public function testPhpDocFixerCommandFailsForReferenceParamNameMismatch(): void
    {
        $commandTester = new CommandTester(new PhpDocFixerCommand());
        $exitCode = $commandTester->execute([
            'path' => __DIR__ . '/fixtures/reference-matching-invalid.xml',
            '--stubs-path' => __DIR__ . '/fixtures/stubs/reference-matching',
        ]);

        static::assertSame(Command::FAILURE, $exitCode);
        static::assertStringContainsString('1 errors found', $commandTester->getDisplay());
        static::assertStringContainsString('reference_match', $commandTester->getDisplay());
        static::assertStringContainsString('[expected] => string', $commandTester->getDisplay());
        static::assertStringContainsString('[received] => string', $commandTester->getDisplay());
    }

    public function testPhpDocFixerCommandPrefersNativeTypesOverStubPhpDoc(): void
    {
        $commandTester = new CommandTester(new PhpDocFixerCommand());
        $exitCode = $commandTester->execute([
            'path' => __DIR__ . '/fixtures/native-types.xml',
            '--stubs-path' => __DIR__ . '/fixtures/stubs/native-types',
        ]);

        static::assertSame(Command::SUCCESS, $exitCode);
        static::assertStringContainsString('0 errors found', $commandTester->getDisplay());
    }

    public function testPhpDocFixerCommandNormalizesArrayValueInfoByDefault(): void
    {
        $commandTester = new CommandTester(new PhpDocFixerCommand());
        $exitCode = $commandTester->execute([
            'path' => __DIR__ . '/fixtures/doc-safe-array.xml',
            '--stubs-path' => __DIR__ . '/fixtures/stubs/doc-safe-array',
        ]);

        static::assertSame(Command::SUCCESS, $exitCode);
        static::assertStringContainsString('0 errors found', $commandTester->getDisplay());
    }

    public function testPhpDocFixerCommandTreatsMixedNullAsMixed(): void
    {
        $commandTester = new CommandTester(new PhpDocFixerCommand());
        $exitCode = $commandTester->execute([
            'path' => __DIR__ . '/fixtures/mixed-null-is-mixed.xml',
            '--stubs-path' => __DIR__ . '/fixtures/stubs/mixed-null-is-mixed',
        ]);

        static::assertSame(Command::SUCCESS, $exitCode);
        static::assertStringContainsString('0 errors found', $commandTester->getDisplay());
    }

    public function testPhpDocFixerCommandAutoFixesRedundantMixedNullUnionToMixed(): void
    {
        $tempDirectory = \sys_get_temp_dir() . '/php-doc-fixer-' . \bin2hex(\random_bytes(8));
        static::assertTrue(\mkdir($tempDirectory, 0777, true));

        $tempPath = $tempDirectory . '/mixed-null-is-mixed-invalid.xml';
        static::assertTrue(\copy(__DIR__ . '/fixtures/mixed-null-is-mixed-invalid.xml', $tempPath));

        try {
            $commandTester = new CommandTester(new PhpDocFixerCommand());
            $exitCode = $commandTester->execute([
                'path' => $tempPath,
                '--auto-fix' => 'true',
                '--stubs-path' => __DIR__ . '/fixtures/stubs/mixed-null-is-mixed',
            ]);

            static::assertSame(Command::SUCCESS, $exitCode);
            static::assertStringContainsString('1 errors found', $commandTester->getDisplay());
            static::assertStringContainsString('1 entries updated', $commandTester->getDisplay());
            static::assertStringContainsString('0 errors remaining after auto-fix', $commandTester->getDisplay());
            static::assertStringContainsString('<type>mixed</type><methodname>mixed_null_is_mixed</methodname>', (string) \file_get_contents($tempPath));
            static::assertStringContainsString('<methodparam><type>mixed</type><parameter>value1</parameter></methodparam>', (string) \file_get_contents($tempPath));
            static::assertStringContainsString('<methodparam><type>mixed</type><parameter>value2</parameter></methodparam>', (string) \file_get_contents($tempPath));
            static::assertStringNotContainsString('<type class="union">', (string) \file_get_contents($tempPath));
        } finally {
            \unlink($tempPath);
            \rmdir($tempDirectory);
        }
    }

    public function testPhpDocFixerCommandPreservesExistingEquivalentUnionOrderDuringAutoFix(): void
    {
        $tempDirectory = \sys_get_temp_dir() . '/php-doc-fixer-' . \bin2hex(\random_bytes(8));
        static::assertTrue(\mkdir($tempDirectory, 0777, true));

        $tempPath = $tempDirectory . '/preserve-union-order-invalid.xml';
        static::assertTrue(\copy(__DIR__ . '/fixtures/preserve-union-order-invalid.xml', $tempPath));

        try {
            $commandTester = new CommandTester(new PhpDocFixerCommand());
            $exitCode = $commandTester->execute([
                'path' => $tempPath,
                '--auto-fix' => 'true',
                '--stubs-path' => __DIR__ . '/fixtures/stubs/preserve-union-order',
            ]);

            static::assertSame(Command::SUCCESS, $exitCode);
            static::assertStringContainsString('1 errors found', $commandTester->getDisplay());
            static::assertStringContainsString('1 entries updated', $commandTester->getDisplay());
            static::assertStringContainsString('0 errors remaining after auto-fix', $commandTester->getDisplay());
            static::assertStringContainsString('<type class="union"><type>string</type><type>false</type></type><methodname>preserve_union_order</methodname>', (string) \file_get_contents($tempPath));
            static::assertStringContainsString('<methodparam><type class="union"><type>string</type><type>int</type></type><parameter>preserve</parameter></methodparam>', (string) \file_get_contents($tempPath));
            static::assertStringContainsString('<methodparam><type class="union"><type>int</type><type>string</type></type><parameter>change</parameter></methodparam>', (string) \file_get_contents($tempPath));
        } finally {
            \unlink($tempPath);
            \rmdir($tempDirectory);
        }
    }

    public function testPhpDocFixerCommandIgnoresDuplicateSynopsisNamesInOneFile(): void
    {
        $commandTester = new CommandTester(new PhpDocFixerCommand());
        $exitCode = $commandTester->execute([
            'path' => __DIR__ . '/fixtures/duplicate-synopsis-name.xml',
            '--stubs-path' => __DIR__ . '/fixtures/stubs/duplicate-synopsis-name',
        ]);

        static::assertSame(Command::SUCCESS, $exitCode);
        static::assertStringContainsString('0 errors found', $commandTester->getDisplay());
    }

    public function testStaticAnalysisCommandSucceedsForMatchingFixture(): void
    {
        $commandTester = new CommandTester(new StaticAnalysisFixerCommand());
        $exitCode = $commandTester->execute([
            'path' => __DIR__ . '/fixtures/functionMap-mbstring.php',
            '--stubs-path' => __DIR__ . '/../vendor/jetbrains/phpstorm-stubs/mbstring',
        ]);

        static::assertSame(Command::SUCCESS, $exitCode);
        static::assertStringContainsString('0 errors found', $commandTester->getDisplay());
    }

    public function testStaticAnalysisCommandFailsForMismatchFixture(): void
    {
        $commandTester = new CommandTester(new StaticAnalysisFixerCommand());
        $exitCode = $commandTester->execute([
            'path' => __DIR__ . '/fixtures/functionMap-mbstring-invalid.php',
            '--stubs-path' => __DIR__ . '/../vendor/jetbrains/phpstorm-stubs/mbstring',
        ]);

        static::assertSame(Command::FAILURE, $exitCode);
        static::assertStringContainsString('1 errors found', $commandTester->getDisplay());
        static::assertStringContainsString('mb_strpos', $commandTester->getDisplay());
    }

    public function testStaticAnalysisCommandIgnoresReferenceParamNameMismatch(): void
    {
        $commandTester = new CommandTester(new StaticAnalysisFixerCommand());
        $exitCode = $commandTester->execute([
            'path' => __DIR__ . '/fixtures/functionMap-reference-matching-invalid.php',
            '--stubs-path' => __DIR__ . '/fixtures/stubs/reference-matching',
        ]);

        static::assertSame(Command::SUCCESS, $exitCode);
        static::assertStringContainsString('0 errors found', $commandTester->getDisplay());
    }

    public function testStaticAnalysisCommandPrefersNativeTypesOverStubPhpDoc(): void
    {
        $commandTester = new CommandTester(new StaticAnalysisFixerCommand());
        $exitCode = $commandTester->execute([
            'path' => __DIR__ . '/fixtures/functionMap-native-types.php',
            '--stubs-path' => __DIR__ . '/fixtures/stubs/native-types',
        ]);

        static::assertSame(Command::SUCCESS, $exitCode);
        static::assertStringContainsString('0 errors found', $commandTester->getDisplay());
    }

    public function testStaticAnalysisCommandNormalizesRefinedTypesToNativeTypes(): void
    {
        $commandTester = new CommandTester(new StaticAnalysisFixerCommand());
        $exitCode = $commandTester->execute([
            'path' => __DIR__ . '/fixtures/functionMap-native-refined-types.php',
            '--stubs-path' => __DIR__ . '/fixtures/stubs/native-types',
        ]);

        static::assertSame(Command::SUCCESS, $exitCode);
        static::assertStringContainsString('0 errors found', $commandTester->getDisplay());
    }

    public function testStaticAnalysisCommandNormalizesPhpStanPseudoTypesToNativeTypes(): void
    {
        $commandTester = new CommandTester(new StaticAnalysisFixerCommand());
        $exitCode = $commandTester->execute([
            'path' => __DIR__ . '/fixtures/functionMap-phpstan-types.php',
            '--stubs-path' => __DIR__ . '/fixtures/stubs/phpstan-types',
        ]);

        static::assertSame(Command::SUCCESS, $exitCode);
        static::assertStringContainsString('0 errors found', $commandTester->getDisplay());
    }
}
