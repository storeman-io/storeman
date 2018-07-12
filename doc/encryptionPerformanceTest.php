<?php

if ($argc < 3) {

    exit("Usage: php {$argv[0]} [file] [algorithm] ([algorithm...])\n");
}

$fileHandle = fopen($argv[1], 'rb');

if (!$fileHandle)
{
    exit("fopen() failed for '{$argv[1]}'\n");
}

$contexts = [];
foreach (array_slice($argv, 2) as $algorithm)
{
    $contexts[$algorithm] = hash_init($algorithm);
}

while(!feof($fileHandle))
{
    $buffer = fread($fileHandle, 65536);

    array_walk($contexts, function($context) use ($buffer) {

        hash_update($context, $buffer);
    });
}

array_walk($contexts, function(&$context, string $algorithm) {

    print sprintf("{$algorithm}: %s\n", hash_final($context));
});

fclose($fileHandle);
