<?php
namespace Spaceboy\NetteCli;

use Nette\Utils\AssertionException;

class Command
{
    private string $name;
    
    private array $argumentsRequired = [];
    
    private array $argumentsOptional = [];
    
    private array $switches = [];
    
    private $worker;

    private ?string $description = null;
    
    /**
     * Create class instance.
     * @param string $name command name
     * @return Command
     */
    public static function create(string $name): self
    {
        return new static($name);
    }
    
    /**
     * Class constructor
     * @param string $name command name
     */
    public function __construct(string $name)
    {
        $this->name = $name;
    }
    
    /**
     * Command name getter.
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * Description setter.
     * @param string $description
     * @return Command
     */
    public function setDescription(string $description): self
    {
        $this->description = $description;
        return $this;
    }

    /**
     * Description getter.
     * @return string|null
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * Add required command argument name.
     * @param string $argumentName
     * @return Command
     */
    public function withArgumentRequired(string $argumentName): self
    {
        $this->argumentCheck($argumentName);
        $this->argumentsRequired[] = $argumentName;
        return $this;
    }
    
    /**
     * Add optional command argument name.
     * @param string $paramName
     * @return Command
     */
    public function withArgumentOptional(string $argumentName): self
    {
        $this->argumentCheck($argumentName);
        $this->argumentsOptional[] = $argumentName;
        return $this;
    }
    
    /**
     * Add command switch name.
     * @param string $switchName
     * @return Command
     */
    public function withSwitch(string $switchName): self
    {
        $this->argumentCheck($switchName);
        $this->switches[] = $switchName;
        return $this;
    }
    
    /**
     * Command arguments getter.
     * @return array
     */
    public function getArguments(): array
    {
        return [
            'required' => $this->argumentsRequired,
            'optional' => $this->argumentsOptional,
            'switches' => $this->switches,
        ];
    }
    
    /**
     * Worker setter.
     * @param callable $worker
     * @return Command
     */
    public function setWorker(callable $worker): self
    {
        $this->worker = $worker;
        return $this;
    }

    /**
     * Execute command worker function.
     * @param $container
     * @param array $arguments defined for CLI app
     * @param array $switches defined for CLI app
     * @return void
     */
    public function execute($container, array $arguments, array $switches): void
    {
        $function = new \ReflectionFunction($this->worker);
        $functionParameters = $function->getParameters();
        $commandArguments = array_merge($this->argumentsRequired, $this->argumentsOptional, $this->switches);
        $params = [];
        
        // Check parameters:
        try {
            foreach ($functionParameters as $parameter) {
                $name = $parameter->getName();
                $knownArgument = in_array($name, $commandArguments);
                if ($knownArgument && array_key_exists($name, $arguments)) {
                    $arguments[$name]->validate(in_array($name, $this->argumentsRequired));
                    $params[$name] = $arguments[$name]->getValue();
                } elseif ($knownArgument && array_key_exists($name, $switches)) {
                    $params[$name] = $switches[$name]->getValue();
                } elseif ($class = $parameter->getClass()) {
                    $params[$name] = $container->getByType($class->getName());
                } else {
                    // Something went wrong:
                    echo "Unexpected trouble.\n";
                    exit;
                }
            }
        } catch (AssertionException $ex) {
            echo 'Invalid argument: ' . $ex->getMessage() . PHP_EOL;
            exit;
        }
        
        // Execute worker:
        try {
            $function->invokeArgs($params);
        } catch (\Exception $ex) {
            echo "Error: {$ex->getMessage()}" . PHP_EOL;
            exit;
        }
    }

    private function argumentCheck(string $argumentName)
    {
        if (in_array($argumentName, array_merge($this->argumentsRequired, $this->argumentsOptional, $this->switches))) {
            echo "Duplicate argument/switch name ({$argumentName}) in command {$this->name}." . PHP_EOL;
            exit;
        }
    }
}
