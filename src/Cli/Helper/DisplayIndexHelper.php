<?php

namespace Storeman\Cli\Helper;

use Storeman\Index\Index;
use Storeman\Index\IndexObject;
use Symfony\Component\Console\Helper\Helper;
use Symfony\Component\Console\Helper\HelperInterface;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Output\OutputInterface;

class DisplayIndexHelper extends Helper implements HelperInterface
{
    /**
     * {@inheritdoc}
     */
    public function getName(): string
    {
        return 'displayIndex';
    }

    public function displayIndex(Index $index, OutputInterface $output)
    {
        $table = new Table($output);
        $table->setStyle('compact');
        $table->setHeaders(['Path', 'Type', 'Times', 'Permissions', 'Inode', 'LinkTarget', 'Size', 'BlobId', 'Hashes']);

        foreach ($index as $indexObject)
        {
            /** @var IndexObject $indexObject */

            $table->addRow([
                $indexObject->getRelativePath(),
                $indexObject->getTypeName(),
                'mtime: ' . \DateTime::createFromFormat('U', $indexObject->getMtime())->format('c') . "\n" .
                'ctime: ' . \DateTime::createFromFormat('U', $indexObject->getCtime())->format('c'),
                "0{$indexObject->getPermissionsString()}",
                $indexObject->getInode() ?: '-',
                $indexObject->getLinkTarget() ?: '-',
                ($indexObject->getSize() !== null) ? static::formatMemory($indexObject->getSize()) : '-',
                $indexObject->getBlobId() ?: '-',
                $indexObject->getHashes() ? str_replace(', ', "\n", $indexObject->getHashes()->__toString()) : '-',
            ]);
        }

        $table->render();
    }
}
