<?php

namespace Storeman\Index\Comparison;

use Storeman\Index\IndexObject;

class IndexObjectComparison
{
    /**
     * @var string
     */
    protected $relativePath;

    /**
     * @var IndexObject
     */
    protected $indexObjectA;

    /**
     * @var IndexObject
     */
    protected $indexObjectB;

    public function __construct(?IndexObject $indexObjectA, ?IndexObject $indexObjectB)
    {
        assert(!(($indexObjectA === null) && ($indexObjectB === null)));
        assert(
            ($indexObjectA === null || $indexObjectB === null) ||
            ($indexObjectA->getRelativePath() === $indexObjectB->getRelativePath())
        );

        if ($indexObjectA instanceof IndexObject)
        {
            $this->relativePath = $indexObjectA->getRelativePath();
        }
        elseif ($indexObjectB instanceof IndexObject)
        {
            $this->relativePath = $indexObjectB->getRelativePath();
        }
        else
        {
            throw new \LogicException();
        }

        $this->indexObjectA = $indexObjectA;
        $this->indexObjectB = $indexObjectB;
    }

    public function getRelativePath(): string
    {
        return $this->relativePath;
    }

    public function getIndexObjectA(): ?IndexObject
    {
        return $this->indexObjectA;
    }

    public function getIndexObjectB(): ?IndexObject
    {
        return $this->indexObjectB;
    }
}
