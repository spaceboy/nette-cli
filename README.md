# NetteCli
Simple tool for easy CLI apps creation in Nette framework

## Installation
The best way to install into [Nette web application](https://github.com/nette/nette) is the easiest one.
Open console, go to your app directory and execute following command:
```
composer require spaceboy/nette-cli
```

## My first CLI application

I strongly recommend you to create a dedicated space for CLI applications in app root directory. For example `bin` for apps operated from command line and `cron` for apps runned from cron.

After installation copy (or link) file `nette-cli.php` from `vendor/spaceboy/nette-cli/bin` directory to folder dedicated for CLI scripts (e.g. `bin`).

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
Or create template script by simple running `nette-cli.php` in `bin` directory:
```
php nette-cli.php create --name command.php
```

In `command.php`, we at first must create app namespace and include required files and namespaces.

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

* ### `setName(string $name): Cli`
  Sets application name displayed during each command execution.

* ### `setDescription(string $description): Cli`
  Sets application description displayed when application is run without any command/argument (help), lists of commands, arguments and options follow.

* ### `registerArgument(Argument $argument): Cli`
  Registers argument (see Argument). Only registered arguments can be referrenced by commands.

* ### `registerOption(Argument $option): Cli`
  Registers option (see Argument, as option has type Argument). Only registered arguments can be referrenced by commands.

* ### `registerCommand(Command $command): Cli`
  Registers executable command (see Command).

* ### `run(string $arguments = null)`
  Runs whole application.
  When you for some reason (e.g. during testing) need manipulate arguments from command line, use `$arguments` argument.
  Example:
  ```
  ...
    ->run('--arg1 "Argument one" --arg2 Argument2 --option')
  ```
* ### `error(string $message): void`
  **Static** method; displays error message (`$message`) end exits script.

## `Argument` public methods:

* ### `create(string $name): Argument`
  **Static** method, creates an instance of `Argument`.
  All other methods can be chained.
  ```
    Argument::create('my-argument')
  ```

* ### `setDescription(string $description): Argument`
  Sets argument description (displayed when user looks for help, so try to be acurate).

* ### `setShortcut(string $shortcut): Argument`
  Sets one char shortcut for argument name. Try to find and predicable and intuitive char, or just don't use shortcut.

* ### `setFormat(string $format): Argument`
  Sets required [Nette validation type](https://doc.nette.org/en/3.1/validators#toc-expected-types) for argument. Can save you lot of validations in the command worker function body.

## `Command` public methods:

* ### `create(string $name)`
  **Static** method, creates an instance of `Command`.
  All other methods can be chained.
  ```
    Command::create('my-command')
  ```

* ### `setDescription(string $description): Command`
  Sets argument description (displayed when user looks for help, so try to be acurate).

* ### `withArgumentRequired(string $argumentName): Command`
  Sets _required_ argument for command worker function.
  Only registered argument names can be used.

* ### `withArgumentOptional(string $argumentName): Command`
  Sets _optional_ argument for command worker function.
  Only registered argument names can be used.

* ### `withOption(string $optionName): Command`
  Sets _optional_ boolean argument (option) for command worker function.
  Only registered option names can be used.

* ### `setWorker(callable $worker): Command`
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

## Class `Format`
Class `Format` is an simple helper for easier command line text formatting.

### Methods:

* ### `reset(): string`
  Return string which resets text/background color settings to standard.

* ### `color(string ...$color): string`
  Return string which (after echoing on console) sets text/background color for next output.
  At the end, don't forget to reset settings to default (using `reset` method)!
```
  echo
      Format::color(Format::RED, Format::BG_WHITE)
      . 'Red text on white background'
      . Format::color(Format::GREEN)
      . 'GREEN text on white background'
      . Format::reset()
      ;
```

* ### `bold(string $text): string`
  Return string which is (after echoing on console) displayed bold.

* ### `dim(string $text): string`
  Return string which is (after echoing on console) displayed dim.

* ### `underlined(string $text): string`
  Return string which is (after echoing on console) displayed underlined.

* ### `blink(string $text): string`
  Return string which is (after echoing on console) displayed blinking.

* ### `reverse(string $text): string`
  Return string which is (after echoing on console) displayed in reverse (text and background colors are switched).

* ### `hidden(string $text): string`
  Return string which is (after echoing on console) displayed hidden (useful for passwords etc.).

* ### `bell(): string`
  Return string which (after echoing on console) makes beep sound (like good old telex bell).

* ### `backspace(): string`
  Return string which (after echoing on console) moves cursor one position left.

* ### `tab(): string`
  Return string which (after echoing on console) moves cursor to the next tab stop (or the end of line, when there ane no more tab stops).

* ### `getConsoleColumns: int`
  Return console width (in characters).

* ### `getConsoleLines: int`
  Return console heights (in lines).

### Color table:

| text color code | background color code | color |
|-----------------|-----------------------|-------|
| DEFAULT_COLOR | BG_DEFAULT | default console color |
| BLACK | BG_BLACK | black |
| RED | BG_RED | red |
| GREEN | BG_GREEN | green |
| YELLOW | BG_YELLOW | yellow |
| BLUE | BG_BLUE | blue |
| MAGENTA | BG_MAGENTA | magenta |
| CYAN | BG_CYAN | cyan |
| LIGHT_GRAY | BG_LIGHT_GRAY | light gray |
| DARK_GRAY | BG_DARK_GRAY | dark gray |
| LIGHT_RED | BG_LIGHT_RED | light red |
| LIGHT_GREEN | BG_LIGHT_GREEN | light green |
| LIGHT_YELLOW | BG_LIGHT_YELLOW | light yellow |
| LIGHT_BLUE | BG_LIGHT_BLUE | light blue |
| LIGHT_MAGENTA | BG_LIGHT_MAGENTA | light magenta |
| LIGHT_CYAN | BG_LIGHT_CYAN | light cyan |
| WHITE | BG_WHITE | white |


## Using helpers

When things gonna be complicated, you should need to share some argument(s) or worker functions(s) between two or more scripts (e.g. between `cli` script and `cron` script).
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
