<?php

namespace Archivr\IndexMerger;

use Archivr\ConflictHandler\ConflictHandlerInterface;
use Archivr\ConflictHandler\PanickingConflictHandler;

abstract class AbstractIndexMerger implements IndexMergerInterface
{
    /**
     * @var ConflictHandlerInterface
     */
    protected $conflictHandler;

    public function setConflictHandler(ConflictHandlerInterface $conflictHandler = null): IndexMergerInterface
    {
        $this->conflictHandler = $conflictHandler;

        return $this;
    }

    public function getConflictHandler(): ConflictHandlerInterface
    {
        if ($this->conflictHandler === null)
        {
            $this->setConflictHandler(new PanickingConflictHandler());
        }

        return $this->conflictHandler;
    }
}
