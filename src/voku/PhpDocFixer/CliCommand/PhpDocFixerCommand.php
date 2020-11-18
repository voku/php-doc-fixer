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

        if (!$realPath || !\file_exists($realPath)) {
            $output->writeln('-------------------------------');
            $output->writeln('The path "' . $path . '" does not exists.');
            $output->writeln('-------------------------------');

            return 2;
        }

        $xmlReader = new \voku\PhpDocFixer\XmlDocs\XmlReader($realPath);
        $xmlDocInfo = $xmlReader->parse();

        $phpStormStubsPath = __DIR__ . '/../../../../vendor/jetbrains/phpstorm-stubs/';
        $phpTypesFromPhpStormStubs = new \voku\PhpDocFixer\PhpStormStubs\PhpStormStubsReader(
            $phpStormStubsPath,
            $removeArrayValueInfo
        );
        $phpStormStubsInfo = $phpTypesFromPhpStormStubs->parse();

        $errors = [];
        foreach ($xmlDocInfo as $functionName_or_classAndMethodName => $types) {
            if (!isset($phpStormStubsInfo[$functionName_or_classAndMethodName])) {
                // TODO: error in phpstorm-stubs ?
                //\var_dump($functionName_or_classAndMethodName); exit;
                continue;
            }

            if (
                ($phpStormStubsInfo[$functionName_or_classAndMethodName]['return'] ?? []) !== ($types['return'] ?? [])
                ||
                ($phpStormStubsInfo[$functionName_or_classAndMethodName]['params'] ?? []) !== ($types['params'] ?? [])
            ) {
                $pathTmp = $types['absoluteFilePath'];
                unset($types['absoluteFilePath']);

                $errors[$functionName_or_classAndMethodName] = [
                    'phpStubTypes' => $phpStormStubsInfo[$functionName_or_classAndMethodName],
                    'phpDocTypes'  => $types,
                    'path'         => $pathTmp,
                ];

                if ($autoFix) {
                    $xmlFixer = new \voku\PhpDocFixer\XmlDocs\XmlWriter($pathTmp);
                    $xmlFixer->fix($phpStormStubsInfo[$functionName_or_classAndMethodName]);
                }
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
