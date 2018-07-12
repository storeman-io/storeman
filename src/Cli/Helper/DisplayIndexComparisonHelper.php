<?php

namespace Storeman\Cli\Helper;

use Storeman\FilesystemUtility;
use Storeman\Index\Comparison\IndexComparison;
use Storeman\Index\Comparison\IndexObjectComparison;
use Storeman\Index\IndexObject;
use Symfony\Component\Console\Helper\Helper;
use Symfony\Component\Console\Helper\HelperInterface;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Output\OutputInterface;

class DisplayIndexComparisonHelper extends Helper implements HelperInterface
{
    /**
     * {@inheritdoc}
     */
    public function getName(): string
    {
        return 'displayIndexComparison';
    }

    public function displayIndexComparison(IndexComparison $indexComparison, OutputInterface $output, string $titleA = 'IndexA', string $titleB = 'IndexB')
    {
        $table = new Table($output);
        $table->setStyle('compact');
        $table->setHeaders(['Path', $titleA, $titleB]);
        $table->addRows(array_map(function(IndexObjectComparison $difference) {

            return [
                $difference->getRelativePath(),
                $this->renderIndexObjectColumn($difference->getIndexObjectA()),
                $this->renderIndexObjectColumn($difference->getIndexObjectB()),
            ];

        }, iterator_to_array($indexComparison->getIterator())));

        $table->render();
    }

    protected function renderIndexObjectColumn(?IndexObject $indexObject): string
    {
        if ($indexObject === null)
        {
            return '-';
        }

        $parts = [
            $indexObject->getTypeName(),
            "mtime: " . ($indexObject->getMtime() === null ? '-' : FilesystemUtility::buildTime($indexObject->getMtime())),
            "ctime: " . ($indexObject->getCtime() === null ? '-' : FilesystemUtility::buildTime($indexObject->getCtime())),
            "perms: 0{$indexObject->getPermissionsString()}",
        ];

        if ($indexObject->isFile())
        {
            $parts = array_merge($parts, [
                "size: " . (($indexObject->getSize() !== null) ? static::formatMemory($indexObject->getSize()) : '-'),
                "blobId: " . ($indexObject->getBlobId() ?: '-'),
                "hash(es):" . ($indexObject->getHashes() && $indexObject->getHashes()->count() ? ("\n" . str_replace(', ', "\n", $indexObject->getHashes()->__toString())) : ' -'),
            ]);
        }
        elseif ($indexObject->isLink())
        {
            $parts = array_merge($parts, [
                "target: {$indexObject->getLinkTarget()}"
            ]);
        }

        return implode("\n", $parts);
    }
}
