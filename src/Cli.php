<?php
namespace Spaceboy\NetteCli;

require_once __DIR__ . '/Argument.php';
require_once __DIR__ . '/Command.php';

require_once __DIR__ . '/../../../vendor/autoload.php';
require_once __DIR__ . '/../../Bootstrap.php';

use App\Bootstrap;
use Spaceboy\NetteCli\Argument;
use Spaceboy\NetteCli\Command;

class Cli extends Bootstrap
{
    /** @var Nette container */
    //private $container;
    
    /** @var string command */
    private ?string $command = null;
    
    /** @var string program name */
    private string $name = 'NetteCli application 0.01';
    
    /** @var Argument[] list of arguments */
    private $arguments = [];
    
    /** @var Argument[] list of switches */
    private $switches = [];
    
    /** @var string[] list of argument/switch shortcuts (aliases) */
    private $shortcuts = [];
    
    /** @var Command[] list of commands */
    private $commands = [];
    

    /**
     * 
     */
    public function getConfigurator()
    {
        return self::boot(in_array('--debug', $_SERVER['argv'], true));
    }
    
    /**
     * @param string $name CLI app name
     * @return Cli
     */
    public function setName(string $name): self
    {
        $this->name = $name;
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
            echo "Duplicite parameter name ({$name})." . PHP_EOL;
            exit;
        }
        $this->arguments[$name] = $argument;
        if ($shortcut = $argument->getShortcut()) {
            if (array_key_exists($shortcut, $this->shortcuts)) {
                echo "Duplicite argument shortcut ({$shortcut})." . PHP_EOL;
                exit;
            }
            $this->shortcuts[$shortcut] = $name;
        }
        return $this;
    }
    
    /**
     * @param Argument $argument
     * @return Cli
     */
    public function registerSwitch(Argument $argument): self
    {
        $name = $argument->getName();
        if (array_key_exists($name, $this->switches)) {
            echo "Duplicate switch name ({$name})." . PHP_EOL;
            exit;
        }
        $this->switches[$name] = $argument->setValue(false);
        if ($shortcut = $argument->getShortcut()) {
            if (array_key_exists($shortcut, $this->shortcuts)) {
                echo "Duplicate switch shortcut ({$shortcut})." . PHP_EOL;
                exit;
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
            echo "Duplicate command name ({$name})." . PHP_EOL;
            exit;
        }
        foreach ($command->getArguments() as $type => $arguments) {
            foreach ($arguments as $argName) {
                if (!array_key_exists($argName, ($type === 'switches' ? $this->switches : $this->arguments))) {
                    echo "Undefined argument/switch ({$argName}) for command {$name}." . PHP_EOL;
                    exit;
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
        if (!array_key_exists($this->command, $this->commands)) {
            echo "Unknown command ({$this->command})." . PHP_EOL;
            exit;
        }
        $this->commands[$this->command]->execute(
            $this->getConfigurator()->createContainer(),
            $this->arguments,
            $this->switches
        );
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
                // Parameter --parameter [value]
                preg_replace_callback(
                    '/^\-\-(.*)$/',
                    function ($match) use (&$this_, &$arguments) {
                        if (array_key_exists($match[1], $this_->arguments)) {
                            $this_->parameters[$match[1]]->setValue(array_shift($arguments));
                            return;
                        }
                        if (array_key_exists($match[1], $this_->switches)) {
                            $this_->switches[$match[1]]->setValue(true);
                            return;
                        }
                        echo "Unknown argument/switch ({$match[1]})." . PHP_EOL;
                        exit;
                    },
                    $argument
                );
            } elseif (preg_match('/^\-(.)\=(.*)$/', $argument)) {
                // Parameter -p=value
                preg_replace_callback(
                    '/^\-(.)\=(.*)$/',
                    function ($match) use (&$this_) {
                        if (!array_key_exists($match[1], $this->shortcuts)) {
                            echo "Unknown argument shortcut ({$match[1]})." . PHP_EOL;
                            exit;
                        }
                        $this_->parameters[$this->shortcuts[$match[1]]]->setValue($match[2]);
                    },
                    $argument
                );
            } elseif (preg_match('/^\-(.)$/', $argument)) {
                // Switch -s
                preg_replace_callback(
                    '/^\-(.)$/',
                    function ($match) use (&$this_) {
                        if (!array_key_exists($match[1], $this->shortcuts)) {
                            echo "Unknown switch shortcut ({$match[1]})." . PHP_EOL;
                            exit;
                        }
                        $this_->switches[$this->shortcuts[$match[1]]]->setValue(true);
                    },
                    $argument
                );
            } else {
                // Command
                $this->command = $argument;
            }
        }
    }

    private function showHelp()
    {
        echo $this->name . PHP_EOL;
        echo 'Usage:' . PHP_EOL;
        echo '    php ' . $_SERVER['SCRIPT_NAME'] . ' command [options] [arguments] [switches]' . PHP_EOL . PHP_EOL;

        echo 'Commands:' . PHP_EOL;
        foreach ($this->commands as $name => $command) {
            echo "{$name}: " . PHP_EOL
                . '    ' . ($command->getDescription() ?? 'undescribed') . PHP_EOL;
        }
        echo PHP_EOL;

        echo 'Arguments:' . PHP_EOL;
        foreach ($this->arguments as $name => $argument) {
            $short = $argument->getShortcut();
            echo "--{$name}"
                . ($short ? ", -{$short}": '')
                . ': ' . PHP_EOL
                . '    ' . ($argument->getDescription() ?? 'undescribed') . PHP_EOL;
        }
        echo PHP_EOL;

        echo 'Switches:' . PHP_EOL;
        foreach ($this->switches as $name => $switch) {
            $short = $switch->getShortcut();
            echo "--{$name}"
                . ($short ? ", -{$short}": '')
                . ': ' . PHP_EOL
                . '    ' . ($switch->getDescription() ?? 'undescribed') . PHP_EOL;
        }
    }
}
