<?php
namespace Spaceboy\NetteCli;


use App\Bootstrap;
use Nette\DI\Container;
use Spaceboy\NetteCli\Argument;
use Spaceboy\NetteCli\Command;
use Spaceboy\NetteCli\Format;


class Cli extends Bootstrap
{
    private Container $container;

    /** @var string command */
    private ?string $command = null;

    /** @var string description */
    private ?string $description;

    /** @var string application name */
    private string $name = 'NetteCli application 0.01';

    /** @var Argument[] list of arguments */
    private $arguments = [];

    /** @var Argument[] list of options */
    private $options = [];

    /** @var string[] list of argument/option shortcuts (aliases) */
    private $shortcuts = [];

    /** @var Command[] list of commands */
    private $commands = [];

    /** @var bool showing application name on start or not */
    private bool $showingName = true;


    public function __construct()
    {
        $this->container = $this->getConfigurator()->createContainer();
    }
    /**
     *
     */
    public function getConfigurator()
    {
        return self::boot(in_array('--debug', $_SERVER['argv'], true));
    }

    /**
     * Name getter.
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * Name setter.
     * @param string $name CLI app name
     * @return Cli
     */
    public function setName(string $name): self
    {
        $this->name = $name;
        return $this;
    }

    /**
     * Print application name.
     * @return Cli
     */
    public function showName(): self
    {
        echo $this->getName() . PHP_EOL;
        return $this;
    }

    /**
     * Do not print application name on start.
     * @return Cli;
     */
    public function hideName(): self
    {
        $this->showingName = false;
        return $this;
    }

    /**
     * Description setter.
     * @param string $description
     * @return Cli
     */
    public function setDescription(string $description): self
    {
        $this->description = $description;
        return $this;
    }

    /**
     * @param Argument $argument
     * @return Cli
     */
    public function registerArgument(Argument $argument): self
    {
        $name = $argument->getName();
        if (array_key_exists($name, $this->arguments)) {
            static::error("Duplicite parameter name ({$name}).");
        }
        $this->arguments[$name] = $argument;
        if ($shortcut = $argument->getShortcut()) {
            if (array_key_exists($shortcut, $this->shortcuts)) {
                static::error("Duplicite argument shortcut ({$shortcut}).");
            }
            $this->shortcuts[$shortcut] = $name;
        }
        return $this;
    }

    /**
     * @param Argument $argument
     * @return Cli
     */
    public function registerOption(Argument $argument): self
    {
        $name = $argument->getName();
        if (array_key_exists($name, $this->options)) {
            static::error("Duplicate option name ({$name}).");
        }
        $this->options[$name] = $argument->setValue(false);
        if ($shortcut = $argument->getShortcut()) {
            if (array_key_exists($shortcut, $this->shortcuts)) {
                static::error("Duplicate option shortcut ({$shortcut}).");
            }
            $this->shortcuts[$shortcut] = $name;
        }
        return $this;
    }

    /**
     * @param Command $command
     * @return Cli
     */
    public function registerCommand(Command $command): self
    {
        if (array_key_exists($name = $command->getName(), $this->commands)) {
            static::error("Duplicate command name ({$name}).");
        }
        foreach ($command->getArguments() as $type => $arguments) {
            foreach ($arguments as $argName) {
                if (!array_key_exists($argName, ($type === 'options' ? $this->options : $this->arguments))) {
                    static::error("Argument/option ({$argName}) linked to command {$name} was not registered.");
                }
            }
        }
        $this->commands[$name] = $command;
        return $this;
    }

    /**
     * Execute command.
     * @param string $arguments
     */
    public function run(string $arguments = null): void
    {
        $this->parseArguments(
            $arguments === null
            ? array_slice($_SERVER['argv'], 1)
            : array_filter(str_getcsv($arguments, ' ', '"', '\\'))
        );

        if ($this->command === null) {
            $this->showHelp();
            exit;
        }

        if ($this->showingName) {
            $this->showName();
        }
        if (!array_key_exists($this->command, $this->commands)) {
            static::error("Unknown command ({$this->command}).");
        }
        $this->commands[$this->command]->execute(
            $this,
            $this->container,
            $this->arguments,
            $this->options
        );
    }

    public static function error(string $message): void
    {
        echo
            Format::bell()
            . Format::color(Format::WHITE, Format::BG_RED)
            . Format::bold('Error: ')
            . $message
            . Format::reset()
            . PHP_EOL;
        exit;
    }

    /**
     * Parse and validate comand line parameters
     * @param array $arguments
     */
    private function parseArguments(array $arguments): void
    {
        $command = null;
        while ($argument = array_shift($arguments)) {
            $this_ = $this;
            if (preg_match('/^\-\-(.*)$/', $argument)) {
                // Argument --argument value
                preg_replace_callback(
                    '/^\-\-(.*)$/',
                    function ($match) use (&$this_, &$arguments) {
                        if (array_key_exists($match[1], $this_->arguments)) {
                            $this_->arguments[$match[1]]->setValue(array_shift($arguments));
                            return;
                        }
                        if (array_key_exists($match[1], $this_->options)) {
                            $this_->options[$match[1]]->setValue(true);
                            return;
                        }
                        //Cli::error("Unknown argument");
                        static::error("Unknown argument/option ({$match[1]}).");
                    },
                    $argument
                );
            } elseif (preg_match('/^\-(.)\=(.*)$/', $argument)) {
                // Argument -a=value
                preg_replace_callback(
                    '/^\-(.)\=(.*)$/',
                    function ($match) use (&$this_) {
                        if (!array_key_exists($match[1], $this->shortcuts)) {
                            static::error("Unknown argument shortcut ({$match[1]}).");
                        }
                        $this_->arguments[$this->shortcuts[$match[1]]]->setValue($match[2]);
                    },
                    $argument
                );
            } elseif (preg_match('/^\-(.)$/', $argument)) {
                // Option -o
                preg_replace_callback(
                    '/^\-(.)$/',
                    function ($match) use (&$this_) {
                        if (!array_key_exists($match[1], $this->shortcuts)) {
                            static::error("Unknown option shortcut ({$match[1]}).");
                        }
                        $this_->options[$this->shortcuts[$match[1]]]->setValue(true);
                    },
                    $argument
                );
            } else {
                // Command
                $this->command = $argument;
            }
        }
    }

    /**
     * Show app description and list of all registered commands, arguments and options.
     * @return void
     */
    private function showHelp(): void
    {
        echo
            (
                $this->description
                ??
                Format::color(Format::YELLOW)
                    . Format::bold($this->name)
                    . Format::color(Format::DEFAULT_COLOR, Format::BG_DEFAULT)
            ) . PHP_EOL
            . 'Usage:' . PHP_EOL
            . Format::color(Format::GREEN)
            . '    [php] ' . $_SERVER['SCRIPT_NAME'] . ' command [arguments] [options]'
            . Format::reset() . PHP_EOL . PHP_EOL;

        if (count($this->commands) > 0) {
            echo Format::bold('Commands:') . PHP_EOL;
            foreach ($this->commands as $name => $command) {
                echo "{$name}: " . PHP_EOL
                    . '    ' . ($command->getDescription() ?? 'undescribed') . PHP_EOL;
                $arguments = $command->getArguments();
                $out = [];
                if (count($arguments['required'])) {
                    $out = array_map(
                        function ($item) {
                            return "--{$item}";
                        },
                        $arguments['required']
                    );
                }
                if (count($arguments['optional'])) {
                    $out = array_merge(
                        $out,
                        array_map(
                            function ($item) {
                                return "[--{$item}]";
                            },
                            $arguments['optional']
                        )
                    );
                }
                if (count($arguments['options'])) {
                    $out = array_merge(
                        $out,
                        array_map(
                            function ($item) {
                                return "[[--{$item}]]";
                            },
                            $arguments['options']
                        )
                    );
                }
                if ($out) {
                    echo '    ' . \join(' ', $out) . PHP_EOL;
                }
            }
        } else {
            echo 'No command defined yet.' . PHP_EOL;
        }

        $this->showHelpList($this->arguments, 'Arguments:');
        $this->showHelpList($this->options, 'Options:');
    }

    /**
     * Show list of all registered arguments/options.
     * @param array $list
     * @param string $title
     * @return void
     */
    private function showHelpList(array $list, string $title): void
    {
        if (count($list) === 0) {
            return;
        }
        echo PHP_EOL . Format::bold($title) . PHP_EOL;
        foreach ($list as $name => $item) {
            $short = $item->getShortcut();
            echo "--{$name}"
                . ($short ? ", -{$short}": '')
                . ': ' . PHP_EOL
                . '    ' . ($item->getDescription() ?? 'undescribed') . PHP_EOL;
        }
    }
}
