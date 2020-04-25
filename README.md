# divix-laminas-cli-commands

CLI Commands for Laminas projects

## Installation

### Via Composer

Install the library using [Composer](https://getcomposer.org):

```bash
$ composer require divix/laminas-cli-commands
```

## Setup
Add the following config into your `config/local.php` file:
```php
'laminas-cli' => [
    'commands' => [
        'mvc:controller' => \Divix\Laminas\Cli\ControllerCommand::class,
        'mvc:rowset' => \Divix\Laminas\Cli\RowsetCommand::class,
        'mvc:model' => \Divix\Laminas\Cli\ModelCommand::class,
        'mvc:view' => \Divix\Laminas\Cli\ViewCommand::class,
    ],
],
```

## Usage
```bash
$ vendor/bin/laminas [command-params] [command-name]
or for Windows users:
$ "vendor/bin/laminas.bat" [command-params] [command-name]
```

## Available commands

### Controller
Generating sample controller with a list of available actions:
```bash
"vendor/bin/laminas-cli.bat" mvc:controller --actions=<action1> --actions=<action2> <name> <moduleName>
```