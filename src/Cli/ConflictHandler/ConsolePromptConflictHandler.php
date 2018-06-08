<?php

namespace Archivr\Cli\ConflictHandler;

use Archivr\Cli\ConsoleStyle;
use Archivr\ConflictHandler\ConflictHandlerInterface;
use Archivr\IndexObject;

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
        $text = <<<TXT
<question>Encountered conflict at {$remoteObject->getRelativePath()}</question>
TXT;

        $this->consoleStyle->writeln($text);

        $choice = $this->consoleStyle->choice('Would you like to use the local (l) or the remote (r) version?', ['l', 'r']);

        switch ($choice)
        {
            case 'l': return ConflictHandlerInterface::USE_LOCAL;
            case 'r': return ConflictHandlerInterface::USE_REMOTE;

            default: throw new \RuntimeException("Invalid choice returned: {$choice}");
        }
    }
}
