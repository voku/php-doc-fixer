<?php

/** @noinspection TransitiveDependenciesUsageInspection */

declare(strict_types=1);

namespace voku\PhpDocFixer\CliCommand;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputDefinition;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

final class StaticAnalysisFixerCommand extends Command
{
    public const COMMAND_NAME = 'static_analysis';

    public function __construct()
    {
        parent::__construct();
    }

    public function configure(): void
    {
        $this
            ->setName(self::COMMAND_NAME)
            ->setDescription('Try to find type errors from static code analysis stubs.')
            ->setDefinition(
                new InputDefinition(
                    [
                        new InputArgument('path', InputArgument::REQUIRED, 'The path to analyse'),
                    ]
                )
            )
            ->addOption(
                'remove-array-value-info',
                null,
                \Symfony\Component\Console\Input\InputOption::VALUE_OPTIONAL,
                'Automatically convert e.g. int[] into array. (false or true)',
                'false'
            )->addOption(
                'stubs-path',
                null,
                \Symfony\Component\Console\Input\InputOption::VALUE_OPTIONAL,
                'Overwrite the source of the stubs, by default we use PhpStorm Stubs via composer.',
                ''
            )->addOption(
                'stubs-file-extension',
                null,
                \Symfony\Component\Console\Input\InputOption::VALUE_OPTIONAL,
                'Overwrite the default file extension for stubs.',
                '.php'
            );
    }

    public function execute(InputInterface $input, OutputInterface $output): int
    {
        $path = $input->getArgument('path');
        \assert(\is_string($path));
        $realPath = \realpath($path);
        \assert(\is_string($realPath));

        $removeArrayValueInfo = $input->getOption('remove-array-value-info') !== 'false';
        $stubsPath = $input->getOption('stubs-path');
        $stubsFileExtension = $input->getOption('stubs-file-extension');

        if (!$realPath || !\file_exists($realPath)) {
            $output->writeln('-------------------------------');
            $output->writeln('The path "' . $path . '" does not exists.');
            $output->writeln('-------------------------------');

            return 2;
        }

        $phpTypesSource = new \voku\PhpDocFixer\StaticCodeAnalysisStubs\StaticCodeAnalysisReader($realPath);
        $phpTypesSourceInfo = $phpTypesSource->parse();

        if (!$stubsPath) {
            $stubsPath = __DIR__ . '/../../../../vendor/jetbrains/phpstorm-stubs/';
        }
        $phpTypesFromStubs = new \voku\PhpDocFixer\PhpStubs\PhpStubsReader(
            $stubsPath,
            $removeArrayValueInfo,
            $stubsFileExtension
        );
        $stubsInfo = $phpTypesFromStubs->parse();

        $errors = [];
        foreach ($phpTypesSourceInfo as $functionName_or_classAndMethodName => $types) {
            if (!isset($stubsInfo[$functionName_or_classAndMethodName])) {
                // TODO: error in stubs?
                //\var_dump($functionName_or_classAndMethodName); exit;
                continue;
            }

            if (
                ($stubsInfo[$functionName_or_classAndMethodName]['return'] ?? []) !== ($types['return'] ?? [])
                ||
                (array_values($stubsInfo[$functionName_or_classAndMethodName]['params'] ?? [])) !== (array_values($types['params'] ?? []))
            ) {
                $errors[$functionName_or_classAndMethodName] = [
                    'phpStubTypes' => $stubsInfo[$functionName_or_classAndMethodName],
                    'phpStanTypes'  => $types,
                ];
            }
        }

        $output->writeln(\count($errors) . ' ' . 'errors found');

        foreach ($errors as $name => $typesArray) {
            $output->writeln('----------------');
            $output->writeln($name);
            $output->writeln(\print_r($typesArray, true));
            $output->writeln('----------------');
        }

        return 0;
    }
}
