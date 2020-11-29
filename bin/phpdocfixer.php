<?php

declare(strict_types=1);

namespace voku\PhpDocFixer;

use Symfony\Component\Console\Application;

require_once dirname(__DIR__) . '/vendor/autoload.php';

(static function () {
    error_reporting(E_ALL);
    ini_set('display_errors', 'stderr');

    define('__PHPDOCFIXER_RUNNING__', true);

    $app = new Application('PhpDocFixer');

    /** @noinspection UnusedFunctionResultInspection */
    $app->add(new \voku\PhpDocFixer\CliCommand\PhpDocFixerCommand());

    /** @noinspection UnusedFunctionResultInspection */
    $app->add(new \voku\PhpDocFixer\CliCommand\PhpStanFixerCommand());

    /** @noinspection PhpUnhandledExceptionInspection */
    $app->run();
})();
