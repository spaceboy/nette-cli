<?php
namespace Spaceboy\NetteCli;

require_once __DIR__ . '/Parameter.php';
require_once __DIR__ . '/Command.php';

require_once __DIR__ . '/../../../vendor/autoload.php';
require_once __DIR__ . '/../../Bootstrap.php';

use App\Bootstrap;
use Spaceboy\NetteCli\Parameter;
use Spaceboy\NetteCli\Command;

class Cli extends Bootstrap
{
    /** @var Nette container */
    //private $container;
    
    /** @var string command */
    private ?string $command = null;
    
    /** @var string program name */
    private string $name = 'NetteCli application 0.01';
    
    /** @var Parameter[] list of parameters */
    private $parameters = [];
    
    /** @var Parameter[] list of switches */
    private $switches = [];
    
    /** @var string[] list of parameter/switch shortcuts (aliases) */
    private $shortcuts = [];
    
    /** @var Command[] list of commands */
    private $commands = [];
    
    /*
    public function __construct()
    {
        $this->container = $this->getConfigurator()->createContainer();
    }
    */
    
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
     * @param Parameter $param
     * @return Cli
     */
    public function registerParameter(Parameter $param): self
    {
        $name = $param->getName();
        if (array_key_exists($name, $this->parameters)) {
            echo "Duplicite parameter name ({$name})." . PHP_EOL;
            exit;
        }
        $this->parameters[$name] = $param;
        if ($shortcut = $param->getShortcut()) {
            if (array_key_exists($shortcut, $this->shortcuts)) {
                echo "Duplicite parameter shortcut ({$shortcut})." . PHP_EOL;
                exit;
            }
            $this->shortcuts[$shortcut] = $name;
        }
        return $this;
    }
    
    /**
     * @param Parameter $param
     * @return Cli
     */
    public function registerSwitch(Parameter $param): self
    {
        $name = $param->getName();
        if (array_key_exists($name, $this->switches)) {
            echo "Duplicate switch name ({$name})." . PHP_EOL;
            exit;
        }
        $this->switches[$name] = $param->setValue(false);
        if ($shortcut = $param->getShortcut()) {
            if (array_key_exists($shortcut, $this->shortcuts)) {
                echo "Duplicate switch shortcut ({$shortcut})." . PHP_EOL;
                exit;
            }
            $this->shortcuts[$shortcut] = $name;
        }
        return $this;
    }
    
    /**
     * @param Command $param
     * @return Cli
     */
    public function registerCommand(Command $command): self
    {
        if (array_key_exists($name = $command->getName(), $this->commands)) {
            echo "Duplicate command name ({$name})." . PHP_EOL;
            exit;
        }
        foreach ($command->getParameters() as $type => $parameters) {
            foreach ($parameters as $paramName) {
                if (!array_key_exists($paramName, ($type === 'switches' ? $this->switches : $this->parameters))) {
                    echo "Undefined parameter/switch ({$paramName}) in command {$name}." . PHP_EOL;
                    exit;
                }
            }
        }
        $this->commands[$name] = $command;
        return $this;
    }
    
    /**
     * Execute command.
     */
    public function run(): void
    {
        echo $this->name . PHP_EOL;
        $this->parseParameters(array_slice($_SERVER['argv'], 1));
        
        if ($this->command === null) {
            echo 'Command missing.' . PHP_EOL;
            exit;
        }
        if (!array_key_exists($this->command, $this->commands)) {
            echo "Unknown command ({$this->command})." . PHP_EOL;
            exit;
        }
        $this->commands[$this->command]->execute(
            $this->getConfigurator()->createContainer(),
            $this->parameters,
            $this->switches
        );
    }

    /**
     * Parse and validate comand line parameters
     * @param array $params
    */
    private function parseParameters(array $params): void
    {
        $command = null;
        while ($param = array_shift($params)) {
            $this_ = $this;
            if (preg_match('/^\-\-(.*)$/', $param)) {
                // Parameter --parameter [value]
                preg_replace_callback(
                    '/^\-\-(.*)$/',
                    function ($match) use (&$this_, &$params) {
                        if (array_key_exists($match[1], $this_->parameters)) {
                            $this_->parameters[$match[1]]->setValue(array_shift($params));
                            return;
                        }
                        if (array_key_exists($match[1], $this_->switches)) {
                            $this_->switches[$match[1]]->setValue(true);
                            return;
                        }
                        echo "Unknown parameter/switch ({$match[1]})." . PHP_EOL;
                        exit;
                    },
                    $param
                );
            } elseif (preg_match('/^\-(.)\=(.*)$/', $param)) {
                // Parameter -p=value
                preg_replace_callback(
                    '/^\-(.)\=(.*)$/',
                    function ($match) use (&$this_) {
                        if (!array_key_exists($match[1], $this->shortcuts)) {
                            echo "Unknown parameter shortcut ({$match[1]})." . PHP_EOL;
                            exit;
                        }
                        $this_->parameters[$this->shortcuts[$match[1]]]->setValue($match[2]);
                    },
                    $param
                );
            } elseif (preg_match('/^\-(.)$/', $param)) {
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
                    $param
                );
            } else {
                // Command
                $this->command = $param;
            }
        }
    }
}
