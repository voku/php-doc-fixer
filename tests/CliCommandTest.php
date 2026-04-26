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
}
