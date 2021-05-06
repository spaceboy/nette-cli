<?php
namespace Spaceboy\NetteCli;

require_once(__DIR__ . '/../vendor/autoload.php');

use Spaceboy\NetteCli\Cli;
use Spaceboy\NetteCli\Argument;
use Spaceboy\NetteCli\Command;

(new Cli())
    ->setName('CLI application name')
    ->setDescription('CLI application description')
    ->registerArgument(
        Argument::create('name')
            ->setShortcut('n')
            ->setDescription('Script name (e.g. my-script for my-script.php)')
    )
    ->registerCommand(
        Command::create('create')
            ->setDescription('Creates CLI script with given name.')
            ->setWorker(

            )
    )
    ->run();
