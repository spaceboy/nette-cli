<?php
namespace Spaceboy\NetteCli;

require_once(__DIR__ . '/../vendor/autoload.php');

use Spaceboy\NetteCli\Cli;
use Spaceboy\NetteCli\Argument;
use Spaceboy\NetteCli\Command;
use Spaceboy\NetteCli\Helper;

(new Cli())
    ->setName('CLI application name')
    ->setDescription('CLI application description')
    ->registerArgument(
        Argument::create('name')
            ->setShortcut('n')
            ->setDescription('Script name (e.g. my-script for my-script.php)')
    )
    ->registerOption(
        Argument::create('executable')
            ->setShortcut('x')
            ->setDescription('Makes script self-executable (e.g. command.php instead of php command.php).')
    )
    ->registerOption(
        Argument::create('overwrite')
            ->setShortcut('o')
            ->setDescription('Overwrites existing script file (can be destructive).')
    )
    ->registerCommand(
        Command::create('create')
            ->setDescription('Creates CLI script with given name.')
            ->withArgumentRequired('name')
            ->withOption('executable')
            ->withOption('overwrite')
            ->setWorker(
                function(
                    string $name,
                    bool $executable,
                    bool $overwrite
                ) {
                    if (!$overwrite && file_exists($name)) {
                        Cli::error("File already exists ({$name}).");
                    }

                    $vendorPathArr = explode(
                        DIRECTORY_SEPARATOR,
                        \dirname(
                            realpath(
                                (new \ReflectionClass('\Composer\Autoload\ClassLoader'))->getFileName()
                            )
                        )
                    );
                    array_pop($vendorPathArr);
                    $vendorPath = DIRECTORY_SEPARATOR
                        . Helper::getRelativePath(getcwd(), join(DIRECTORY_SEPARATOR, $vendorPathArr))
                        . DIRECTORY_SEPARATOR
                        . 'autoload.php'
                    ;

                    $script = (
                        $executable
                        ? '#!' . PHP_BINARY
                        : ''
                    );

                    $user = get_current_user();

                    \file_put_contents(
                        $name,
<<<EOF
{$script}
<?php
/**
 * CLI script
 * @author {$user}
 */
namespace App\Bin;

require_once(__DIR__ . '{$vendorPath}');

use Spaceboy\NetteCli\Cli;
use Spaceboy\NetteCli\Argument;
use Spaceboy\NetteCli\Command;

(new Cli())
    ->setName('CLI application name')
    ->setDescription('CLI application description')
    // Register parameters and options using ->registerParameter() and ->registerOption() methods.
    // Register commands using ->registerCommand() method.
    ->run();
EOF
                    );
                    chmod($name, 0755);
                }
            )
    )
    ->run();
