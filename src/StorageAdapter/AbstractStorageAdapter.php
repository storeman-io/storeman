<?php

namespace Storeman\StorageAdapter;

use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Psr\Log\NullLogger;

abstract class AbstractStorageAdapter implements LoggerAwareInterface, StorageAdapterInterface
{
    use LoggerAwareTrait;

    public function __construct()
    {
        $this->logger = new NullLogger();
    }
}
