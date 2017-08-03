<?php

namespace Archivr\ConfigurationFactory;

use Archivr\Configuration;

interface ConfigurationFactoryInterface
{
    public function __invoke(): Configuration;
}
