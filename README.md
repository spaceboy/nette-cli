# NetteCli
Simple tool for easy CLI apps creation in Nette framework

## Installation
The best way to install into web application is the easiest one.  
Open console, go to your app directory and type:
```
composer install spaceboy/nette-cli
```

## My first CLI application

I strongly recommend you to create a dedicated space for CLI applications in app root directory. For example `bin` for apps operated from command line and `cron` for apps runned from cron.

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
use Spaceboy\NetteCli\Argument;
use Spaceboy\NetteCli\Command;
```
Then we can create our first app:
```
(new Cli())
    // Argument definition:
    ->registerArgument(
        Argument::create('name')
            ->setShortcut('n')
            ->setFormat('string:2..25')
    )
    // Option definition:
    ->registerOption(
        Argument::create('strong')
    )
    // Command definition:
    ->registerCommand(
        Command::create('hello')
            ->withArgumentRequired('name')
            ->withOption('strong')
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

As we've registered `argument` named "name" and set that argument required for command "hello" (`Command->withArgumentRequired([arg-name])`), our app will not run without typing argument in command line.

As we've registered also `shortcut` argument name (`Argument->setShortcut()`), we can run our app wit less writing:
```
php command.php hello -n=World
```

As we've set required format of argument "name" (`Argument->setFormat()`) as `string:2..25` (string with length at least 2 chars and 25 chars max), our app will not run with too short or too long name. Try it yourself.

As we've registered also `option` named "strong" in application (`Cli->registerOption('strong')`) and enabled this option in command "hello" (`Command->withOption(strong)`), we can use it:
```
php command.php hello --name World --strong
```

## `Cli` public methods:

* ### `setName(string $name)`
  Sets application name displayed during each command execution.

* ### `setDescription(string $description)`
  Sets application description displayed when application is run without any command/argument (help), lists of commands, arguments and options follow.

* ### `registerArgument(Argument $argument)`
  Registers argument (see Argument). Only registered arguments can be referrenced by commands.

* ### `registerOption(Argument $option)`
  Registers option (see Argument, as option has type Argument). Only registered arguments can be referrenced by commands.

* ### `registerCommand(Command $command)`
  Registers executable command (see Command).

* ### `run(string $arguments = null)`
  Runs whole application.  
  When you for some reason (eg. during testing) need manipulate arguments from command line, use `$arguments` argument.  
  Example:
  ```
  ...
    ->run('--arg1 "Argument one" --arg2 Argument2 --option')
  ```

## `Argument` public methods:

* ### `create(string $name)`
  Creates Argument, returns Argument.  
  All other methods can be chained.
  ```
    Argument::create('my-argument')
  ```

* ### `setDescription(string $description)`
  Sets argument description (displayed when user looks for help, so try to be acurate).

* ### `setShortcut(string $shortcut)`
  Sets one char shortcut for argument name. Try to find and predicable and intuitive char, or just don't use shortcut.

* ### `setFormat(string $format)`
  Sets required [Nette validation type](https://doc.nette.org/en/3.1/validators#toc-expected-types) for argument. Can save you lot of validations in the command worker function body.

## `Command` public methods:

* ### `create(string $name)`
  Creates Command, returns Command.  
  All other methods can be chained.
  ```
    Command::create('my-command')
  ```

* ### `setDescription(string $description)`
  Sets argument description (displayed when user looks for help, so try to be acurate).

* ### `withArgumentRequired(string $argumentName)`
  Sets _required_ argument for command worker function.  
  Only registered argument names can be used.

* ### `withArgumentOptional(string $argumentName)`
  Sets _optional_ argument for command worker function.  
  Only registered argument names can be used.

* ### `withOption(string $optionName)`
  Sets _optional_ boolean argument (option) for command worker function.  
  Only registered option names can be used.

* ### `setWorker(callable $worker)`
  Sets executive function for command.  
  Function arguments must be:

  1. Registered in Cli (using `Cli->registerArgument()` or `Cli->registerOption()`)  
  AND declared in Command definition (using `Command->withArgumentRequired()`, `Command->withArgumentOptional()` or `Command->withOption()`)  
  (Arguments passed from command line)

  **OR**

  2. Declared by typehint  
  (Nette application classes/objects)

  Example:
```
    ->registerArgument(
        Argument::create('name')
    )
    ...
    ->registerCommand(
      Command::create('use-database')
        ->withArgumentRequired('name')
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

When things gonna be complicated, you should need to share some argument(s) or worker functions(s) between two or more scripts (eg. between `cli` script and `cron` script).  
Feel free to use helpers. You can set both static and dynamic methods as command worker function as well as closure. Just don't forget that those methods must be public.

```
  ...
  ->registerArgument(ArgumentsClass::argName());
  ...
  ->registerCommand($commandClass->getCommand('commandOne'));
  ->registerCommand(
    Command::create('command')
      ->withArgumentRequired('name')
      // Use static method:
      ->setWorker([WorkersClass::class, 'staticMethod'])
      // Or use dynamic method:
      ->setWorker([new WorkersClass(), 'dynamicMethod'])
  );
  ...
  ->run();
```
