<?php

/** @noinspection TransitiveDependenciesUsageInspection */

declare(strict_types=1);

namespace voku\PhpDocFixer\CliCommand;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputDefinition;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

final class PhpDocFixerCommand extends Command
{
    public const COMMAND_NAME = 'run';

    public function __construct()
    {
        parent::__construct();
    }

    public function configure(): void
    {
        $this
            ->setName(self::COMMAND_NAME)
            ->setDescription('Try to fix types in the php documentation.')
            ->setDefinition(
                new InputDefinition(
                    [
                        new InputArgument('path', InputArgument::REQUIRED, 'The path to analyse'),
                    ]
                )
            )
            ->addOption(
                'auto-fix',
                null,
                \Symfony\Component\Console\Input\InputOption::VALUE_OPTIONAL,
                'Automatically fix the types in the given xml files. (false or true)',
                'false'
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

        $autoFix = $input->getOption('auto-fix') !== 'false';
        $removeArrayValueInfo = $input->getOption('remove-array-value-info') !== 'false';
        $stubsPath = $input->getOption('stubs-path');
        $stubsFileExtension = $input->getOption('stubs-file-extension');

        if (!$realPath || !\file_exists($realPath)) {
            $output->writeln('-------------------------------');
            $output->writeln('The path "' . $path . '" does not exists.');
            $output->writeln('-------------------------------');

            return self::INVALID;
        }

        if (!$stubsPath) {
            $stubsPath = __DIR__ . '/../../../../vendor/jetbrains/phpstorm-stubs/';
        }
        $phpTypesFromStubs = new \voku\PhpDocFixer\PhpStubs\PhpStubsReader(
            $stubsPath,
            $removeArrayValueInfo,
            $stubsFileExtension
        );
        $stubsInfo = $phpTypesFromStubs->parse();

        $xmlReader = new \voku\PhpDocFixer\XmlDocs\XmlReader($realPath);
        $xmlDocInfo = $xmlReader->parse();
        $errors = $this->findErrors($xmlDocInfo, $stubsInfo);

        $output->writeln(\count($errors) . ' ' . 'errors found');

        if ($autoFix && $errors !== []) {
            $updatedEntries = 0;
            foreach ($errors as $functionName_or_classAndMethodName => $typesArray) {
                $xmlFixer = new \voku\PhpDocFixer\XmlDocs\XmlWriter($typesArray['path']);
                if ($xmlFixer->fix($typesArray['phpStubTypes'], $functionName_or_classAndMethodName)) {
                    $updatedEntries++;
                }
            }

            $output->writeln($updatedEntries . ' entries updated');

            $xmlDocInfo = (new \voku\PhpDocFixer\XmlDocs\XmlReader($realPath))->parse();
            $errors = $this->findErrors($xmlDocInfo, $stubsInfo);

            $output->writeln(\count($errors) . ' ' . 'errors remaining after auto-fix');
        }

        foreach ($errors as $name => $typesArray) {
            $output->writeln('----------------');
            $output->writeln($name);
            $output->writeln(\print_r($typesArray, true));
            $output->writeln('----------------');
        }

        if ($errors !== []) {
            return self::FAILURE;
        }

        return self::SUCCESS;
    }

    /**
     * @param array<string, array{return?: string, params?: array<string, string>, absoluteFilePath: string}> $xmlDocInfo
     * @param array<string, array{return?: string, params?: array<string, string>}>                            $stubsInfo
     *
     * @return array<string, array{
     *     phpStubTypes: array{return?: string, params?: array<string, string>},
     *     phpDocTypes: array{return?: string, params?: array<string, string>},
     *     path: string
     * }>
     */
    private function findErrors(array $xmlDocInfo, array $stubsInfo): array
    {
        $errors = [];
        foreach ($xmlDocInfo as $functionName_or_classAndMethodName => $types) {
            if (!isset($stubsInfo[$functionName_or_classAndMethodName])) {
                // TODO: error in stubs?
                //\var_dump($functionName_or_classAndMethodName); exit;
                continue;
            }

            if (
                ($stubsInfo[$functionName_or_classAndMethodName]['return'] ?? []) !== ($types['return'] ?? [])
                ||
                ($stubsInfo[$functionName_or_classAndMethodName]['params'] ?? []) !== ($types['params'] ?? [])
            ) {
                $pathTmp = $types['absoluteFilePath'];
                unset($types['absoluteFilePath']);

                $errors[$functionName_or_classAndMethodName] = [
                    'phpStubTypes' => $stubsInfo[$functionName_or_classAndMethodName],
                    'phpDocTypes'  => $types,
                    'path'         => $pathTmp,
                ];
            }
        }

        return $errors;
    }
}
