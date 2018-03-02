<?php

(function(): void {

    $autoloaderFound = false;
    foreach ([__DIR__ . '/vendor/autoload.php', __DIR__ . '/../../autoload.php'] as $path)
    {
        if (is_file($path))
        {
            require_once $path;

            $autoloaderFound = true;

            break;
        }
    }

    if (!$autoloaderFound)
    {
        fwrite(STDERR, 'Dependencies are not installed. Try `composer install`.' . PHP_EOL);

        exit(1);
    }

})();
