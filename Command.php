<?php
namespace Spaceboy\NetteCli;

use Nette\Utils\AssertionException;

class Command
{
    private string $name;
    
    private array $paramsRequired = [];
    
    private array $paramsOptional = [];
    
    private array $switches = [];
    
    private $worker;
    
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
     * Add required command parameter name.
     * @param string $paramName
     * @return Command
     */
    public function withParameterRequired(string $paramName): self
    {
        $this->paramCheck($paramName);
        $this->paramsRequired[] = $paramName;
        return $this;
    }
    
    /**
     * Add optional command parameter name.
     * @param string $paramName
     * @return Command
     */
    public function withParameterOptional(string $paramName): self
    {
        $this->paramCheck($paramName);
        $this->paramsOptional[] = $paramName;
        return $this;
    }
    
    /**
     * Add command switch name.
     * @param string $paramName
     * @return Command
     */
    public function withSwitch(string $switchName): self
    {
        $this->paramCheck($switchName);
        $this->switches[] = $switchName;
        return $this;
    }
    
    /**
     * Command parameters getter.
     * @return array
     */
    public function getParameters(): array
    {
        return [
            'required' => $this->paramsRequired,
            'optional' => $this->paramsOptional,
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
     * @param array $parameters defined for CLI app
     * @param array $switches defined for CLI app
     * @return void
     */
    public function execute($container, array $parameters, array $switches): void
    {
        $function = new \ReflectionFunction($this->worker);
        $functionParams = $function->getParameters();
        $commandParams = array_merge($this->paramsRequired, $this->paramsOptional, $this->switches);
        $params = [];
        
        // Check parameters:
        try {
            foreach ($functionParams as $parameter) {
                $name = $parameter->getName();
                $knownParameter = in_array($name, $commandParams);
                if ($knownParameter && array_key_exists($name, $parameters)) {
                    $parameters[$name]->validate(in_array($name, $this->paramsRequired));
                    $params[$name] = $parameters[$name]->getValue();
                } elseif ($knownParameter && array_key_exists($name, $switches)) {
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
            echo 'Invalid parameter: ' . $ex->getMessage() . PHP_EOL;
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

    private function paramCheck(string $paramName)
    {
        if (in_array($paramName, array_merge($this->paramsRequired, $this->paramsOptional, $this->switches))) {
            echo "Duplicate parameter/switch name ({$paramName}) in command {$this->name}." . PHP_EOL;
            exit;
        }
    }
}
