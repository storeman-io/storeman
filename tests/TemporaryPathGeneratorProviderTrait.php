<?php

namespace Storeman\Test;

trait TemporaryPathGeneratorProviderTrait
{
    /**
     * @var TemporaryPathGenerator
     */
    private $temporaryPathGenerator;

    private function getTemporaryPathGenerator(): TemporaryPathGenerator
    {
        if ($this->temporaryPathGenerator === null)
        {
            $this->temporaryPathGenerator = new TemporaryPathGenerator();
        }

        return $this->temporaryPathGenerator;
    }
}
