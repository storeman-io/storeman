<?php

namespace Storeman\Cli\ConflictHandler;

use Storeman\Cli\ConsoleStyle;
use Storeman\ConflictHandler\ConflictHandlerInterface;
use Storeman\Exception;
use Storeman\Index\IndexObject;

/**
 * This implementation of a conflict handler asks the user to resolve it manually.
 */
class ConsolePromptConflictHandler implements ConflictHandlerInterface
{
    /**
     * @var ConsoleStyle
     */
    protected $consoleStyle;

    public function __construct(ConsoleStyle $consoleStyle)
    {
        $this->consoleStyle = $consoleStyle;
    }

    /**
     * {@inheritdoc}
     */
    public function handleConflict(IndexObject $remoteObject, IndexObject $localObject = null, IndexObject $lastLocalObject = null): int
    {
        if (!$this->consoleStyle->getInput()->isInteractive())
        {
            throw new Exception(sprintf("Trying to use conflict handler %s with non-interactive input", static::class));
        }

        $localObjectString = $localObject ? $localObject->__toString() : '-';
        $lastLocalObjectString = $lastLocalObject ? $lastLocalObject->__toString() : '-';
        $text = <<<TXT
<question>Encountered conflict at {$remoteObject->getRelativePath()}
Local: {$localObjectString}
Last local: {$lastLocalObjectString}
Remote: {$remoteObject}</question>
TXT;

        $this->consoleStyle->writeln($text);

        $return = null;
        do
        {
            $choice = $this->consoleStyle->choice('Would you like to use the local (l) or the remote (r) version?', ['l', 'r']);

            switch ($choice)
            {
                case 'l': $return = ConflictHandlerInterface::USE_LOCAL; break 2;
                case 'r': $return = ConflictHandlerInterface::USE_REMOTE; break 2;
            }

            $this->consoleStyle->warning("Invalid choice");
        }
        while(true);

        return $return;
    }
}
