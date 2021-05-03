<?php
namespace Spaceboy\NetteCli;


use Nette\Utils\AssertionException;
use Nette\DI\Container;


class Command
{
    private string $name;

    private array $argumentsRequired = [];

    private array $argumentsOptional = [];

    private array $options = [];

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
     * Add command option name.
     * @param string $optionName
     * @return Command
     */
    public function withOption(string $optionName): self
    {
        $this->argumentCheck($optionName);
        $this->options[] = $optionName;
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
            'options' => $this->options,
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
     * @param array $options defined for CLI app
     * @return void
     */
    public function execute(Container $container, array $arguments, array $options): void
    {
        $function = (
            is_array($this->worker)
            ? new \ReflectionMethod($this->worker[0], $this->worker[1])
            : new \ReflectionFunction($this->worker)
        );

        $params = $this->getFunctionParameters(
            $container,
            $function->getParameters(),
            array_merge($this->argumentsRequired, $this->argumentsOptional, $this->options),
            $arguments,
            $options
        );

        // Execute worker:
        try {
            if (is_array($this->worker)) {
                // Worker is method:
                $function->invokeArgs(($function->isStatic() ? null : $this->worker[0]), $params);
            } else {
                // Worker is closure:
                $function->invokeArgs($params);
            }
        } catch (\Exception $ex) {
            Cli::error($ex->getMessage());
        }
    }

    /**
     * Check for argument/option name duplicity.
     * @param string $argumentName
     * @return void
     */
    private function argumentCheck(string $argumentName): void
    {
        if (in_array($argumentName, array_merge($this->argumentsRequired, $this->argumentsOptional, $this->options))) {
            Cli::error("Duplicate argument/option name ({$argumentName}) in command {$this->name}.");
        }
    }

    /**
     * Validate arguments from command line and convert them to worker function/method format.
     * @param Container $container Nette DI container
     * @param array $functionParameters list of worker parameters
     * @param array $commandArguments list of arguments from commandline
     * @param array $arguments list of Cli registered parameters
     * @param array $options list of Cli registered options
     * @return array
     */
    private function getFunctionParameters(
        Container $container,
        array $functionParameters,
        array $commandArguments,
        array $arguments,
        array $options
    ): array
    {
        $params = [];
        try {
            foreach ($functionParameters as $parameter) {
                $name = $parameter->getName();
                $knownArgument = in_array($name, $commandArguments);
                if ($knownArgument && array_key_exists($name, $arguments)) {
                    $arguments[$name]->validate(in_array($name, $this->argumentsRequired));
                    $params[$name] = $arguments[$name]->getValue();
                } elseif ($knownArgument && array_key_exists($name, $options)) {
                    $params[$name] = $options[$name]->getValue();
                } elseif ($class = $parameter->getClass()) {
                    $params[$name] = $container->getByType($class->getName());
                } else {
                    // Can't resolve function parameter:
                    Cli::error("Can not resolve worker function parameter ({$name}).");
                }
            }
        } catch (AssertionException $ex) {
            Cli::error('Invalid argument format' . PHP_EOL . $ex->getMessage());
        }
        return $params;
    }
}
