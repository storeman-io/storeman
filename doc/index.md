
# Storeman

## Requirements

- Up to this point __Linux__ is the only tested operating system (Mac OS X should work fine though)
- __PHP 7.1__ or above
- [__Composer__](https://getcomposer.org/) to install required dependencies

## Quickstart

### Building

As storeman is primarily intended for CLI usage it should be build into a stand-alone phar archive:   
    
```bash
$ composer install
$ composer build
Building to /path/to/repo/build/storeman.phar...
Build successful!
```

> Alternatively you can run storeman directly with `php bin/storeman`

### Installing

For convenience you might want to symlink the storeman executable into `~/bin`, `/usr/bin` or wherever:

```bash
$ ln -s /path/to/repo/build/storeman.phar ~/bin/storeman
```

### Running

All available commands can be listed by executing `storeman` without any arguments. Help for individual commands can be shown like this: `storeman help ...`.

```bash
$ storeman
  ____  _                                       
 / ___|| |_ ___  _ __ ___ _ __ ___   __ _ _ __  
 \___ \| __/ _ \| '__/ _ \ '_ ` _ \ / _` | '_ \ 
  ___) | || (_) | | |  __/ | | | | | (_| | | | |
 |____/ \__\___/|_|  \___|_| |_| |_|\__,_|_| |_|

Storeman alpha

Usage:
  command [options] [arguments]

Options:
  -h, --help            Display this help message
  -q, --quiet           Do not output any message
  -V, --version         Display this application version
      --ansi            Force ANSI output
      --no-ansi         Disable ANSI output
  -n, --no-interaction  Do not ask any interactive question
  -v|vv|vvv, --verbose  Increase the verbosity of messages: 1 for normal output, 2 for more verbose output and 3 for debug

Available commands:
  diff        Displays the difference of a revision and another revision or the current local state.
  dump        Dump the contents of a vault.
  help        Displays help for a command
  info        Displays information about a vault and its local representation.
  init        Sets up an archive.
  list        Lists commands
  restore     Restores the local state from the vault state.
  show-index  Displays an index
  sync        Synchronizes the local state with the vault state.
```

## Testing

Storeman uses PHPUnit as its testing framework. The bundled tests can be run using the `test` composer script:

```bash
$ composer install
$ composer test
```
