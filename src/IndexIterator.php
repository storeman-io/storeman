<?php

namespace Storeman;

class IndexIterator extends \RecursiveIteratorIterator
{
    public function __construct(RecursiveIndexIterator $indexIterator)
    {
        parent::__construct($indexIterator, \RecursiveIteratorIterator::SELF_FIRST);
    }

    /**
     * {@inheritdoc}
     */
    public function key()
    {
        return $this->getCurrentPath();
    }

    /**
     * Returns the current objects path.
     *
     * @return string
     */
    protected function getCurrentPath(): string
    {
        $parts = [];

        for ($level = 0; $level <= $this->getDepth(); $level++)
        {
            $parts[] = $this->getSubIterator($level)->key();
        }

        return implode('/', $parts);
    }
}
