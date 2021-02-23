# NetteCli
Simple tool for easy CLI apps creation in Nette framework

## Installation
The best way to install into web application is the easiest one.  
Open console, go to your app directory and type:
```
composer install spaceboy/nette-cli
```

## My first CLI application

I strongly recommend you to create a dedicated space for CLI applications in app root directory. For example `bin` for apps operated from command line and `cron` for that operated from cron.

Create a `PHP` file, f.e. `command.php` in `bin` directory:
```
root
 +- app   // Nette app directory
 +- bin   // CLI commands directory
 |    command.php   // Out first CLI aplication
 +- cron  // cron CLI commands directory
 +- log   // Nette log directory
 ...
 ```

In `command.php`, we at first must create app namespace and include required files and namespaces:
```
<?php
namespace App\Bin;

require_once __DIR__ . '/../vendor/autoload.php';

use Spaceboy\NetteCli\Cli;
use Spaceboy\NetteCli\Parameter;
use Spaceboy\NetteCli\Command;
```
Then we can create our first app:
```
(new Cli())
    // Parameter definition:
    ->registerParameter(
        Parameter::create('name')
            ->setShortcut('n')
            ->setFormat('string:2..25')
    )
    // Switch definition:
    ->registerSwitch(
        Parameter::create('strong')
    )
    // Command definition:
    ->registerCommand(
        Command::create('hello')
            ->withParameterRequired('name')
            ->withSwitch('strong')
            ->setWorker(
                // worker function:
                function ($name, $strong) {
                    echo "Hello, {$name}";
                    echo ($strong ? "!" : ".");
                    echo PHP_EOL;
                }
            )
    )
    // Don't forget to run whole CLI application:
    ->run();
```
That's all, folks. Try it in command line:
```
php command.php hello --name World
```
As we've registered `command` named "hello", it's worker function is called and executed.

As we've registered `parameter` named "name" and set that parameter required for command "hello" (`Command->withParameterRequired([param-name])`), our app will not run without typing parameter in command line.

As we've registered also `shortcut` parameter name (`Parameter->setShortcut()`), we can run our app wit less writing:
```
php command.php hello -n=World
```

As we've set required format of parameter "name" (`Parameter->setFormat()`) as `string:2..25` (string with length at least 2 chars and 25 chars max), our app will not run with too short or too long name. Try it yourself.

As we've registered also `switch` named "strong" in application (`Cli->registerSwitch`) and enabled this switch in command "hello" (`Command->withSwitch([switch-name])`), we can use it:
```
php command.php hello --name World --strong
```

## Cli methods
* ### `setName(string $name)`
  Sets application name displayed during execution.

* ### `registerParameter(Parameter $parameter)`
  Registers parameter (see Parameter). Only registered parameters can be referrenced by commands.

* ### `registerSwitch(Parameter $switch)`
  Registers switch (see Parameter, as switch is Parameter). Only registered parameters can be referrenced by commands.

* ### `registerCommand(Command $command)`
  Registers executable command (see Command).

* ### `run()`
  Runs whole application.

## Parameter
* ### `create(string $name)`
  Creates Parameter, returns Parameter.  
  All other methods can be chained.
  ```
    Parameter::create('my-parameter')
  ```

* ### `setDescription(string $description)`
  Sets parameter description (displayed when user looks for help, so try to be acurate).

* ### `setShortcut(string $shortcut)`
  Sets one char shortcut for parameter name. Try to find and predicable and intuitive char, or just don't use shortcut.

* ### `setFormat(string $format)`
  Sets required [Nette validation type](https://doc.nette.org/en/3.1/validators#toc-expected-types) for parameter. Can save you lot of validations in the command worker function body.

## Command
* ### `create(string $name)`
  Creates Command, returns Command.  
  All other methods can be chained.
  ```
    Command::create('my-command')
  ```

* ### `setDescription(string $description)`
  Sets parameter description (displayed when user looks for help, so try to be acurate).

* ### `withParameterRequired(string $parameterName)`
  Sets _required_ parameter for command worker function.  
  Only registered parameter names can be used.

* ### `withParameterOptional(string $parameterName)`
  Sets _optional_ parameter for command worker function.  
  Only registered parameter names can be used.

* ### `withSwitch(string $switchName)`
  Sets _optional_ boolean parameter (switch) for command worker function.  
  Only registered switch names can be used.

* ### `setWorker(callable $worker)`
  Sets executive function for command.  
  Function parameters must be:

  1. Registered in Cli (using `Cli->registerParameter()` or `Cli->registerSwitch()`)  
  AND declared in Command definition (using `Command->withParameterRequired()`, `Command->withParameterOptional()` or `Command->withSwitch()`)  
  (Parameters passed from command line)

  **OR**

  2. Declared by typehint  
  (Nette application classes/objects)

  Example:
```
    ->registerParameter(
        Parameter::create('name')
    )
    ...
    ->registerCommand(
      Command::create('use-database')
        ->withParameterRequired('name')
        ->setWorker(
            function (
                \Nette\Database\Connection $connection, // DI
                $name
            ) {
                $row = $connection->query(
                    'SELECT * FROM table WHERE name = ?', $name
                )->fetch();
            }
        )
    )
```

## Using helpers
When things gonna be way much complicated or you need to share some parameters or worker functions between two or more scripts (eg. between `CLI` script and `cron` script), feel free to use helpers.  
There's no reason, why constructions like the one bellow shouldn't work:

```
    ...
    ->registerParameter(ParametersClass::paramName())
    ...
    ->registerCommand($commandClass->getCommand('commandOne'))
    ->registerCommand(
      Command::create('use-database')
        ->withParameterRequired('name')
        ->setWorker('WorkersClass::useDatabase')
    )
    ...
    ->run();
```

Be well, Earthers!
