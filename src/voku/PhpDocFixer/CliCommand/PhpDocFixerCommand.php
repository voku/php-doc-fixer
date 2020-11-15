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
                        new InputArgument('path', InputArgument::REQUIRED | InputArgument::IS_ARRAY, 'The path to analyse'),
                    ]
                )
            );
    }

    public function execute(InputInterface $input, OutputInterface $output): int
    {
        $pathArray = $input->getArgument('path');
        if (!$pathArray) {
            // fallback
            $pathArray = ['.'];
        }
        $realPath = null;
        \assert(\is_array($pathArray));
        foreach ($pathArray as $pathItem) {
            $realPath = \realpath($pathItem);
            \assert(\is_string($realPath));

            if (!$realPath || !\file_exists($realPath)) {
                $output->writeln('-------------------------------');
                $output->writeln('The path "' . $pathItem . '" does not exists.');
                $output->writeln('-------------------------------');

                return 2;
            }
        }

        $xmlReader = new \voku\PhpDocFixer\ReadXmlDocs\XmlReader($realPath);
        $xmlDocInfo = $xmlReader->parse();

        $phpStormStubsPath = __DIR__ . '/../../../../vendor/jetbrains/phpstorm-stubs/';
        $phpTypesFromPhpStormStubs = new \voku\PhpDocFixer\ReadPhpStormStubs\PhpStormStubsReader($phpStormStubsPath);
        $phpStormStubsInfo = $phpTypesFromPhpStormStubs->parse();

        $errors = [];
        foreach ($xmlDocInfo as $functionName_or_classAndMethodName => $types) {
            if (!isset($phpStormStubsInfo[$functionName_or_classAndMethodName])) {
                // TODO: check this
                //\var_dump($functionName_or_classAndMethodName);
                continue;
            }

            if ($phpStormStubsInfo[$functionName_or_classAndMethodName] !== $types) {
                $errors[$functionName_or_classAndMethodName] = [
                    'phpStubTypes' => $phpStormStubsInfo[$functionName_or_classAndMethodName],
                    'phpDocTypes' => $types,
                ];
            }
        }

        $output->writeln($realPath);

        foreach ($errors as $name => $typesArray) {
            $output->writeln('----------------');
            $output->writeln($name);
            $output->writeln(\print_r($typesArray, true));
            $output->writeln('----------------');
        }

        return 0;
    }
}
