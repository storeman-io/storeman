<?php

namespace Archivr\Test;

use Archivr\Test\Tree\SimpleTreeGenerator;
use Archivr\Test\Tree\TreeGeneratorInterface;
use Archivr\Test\Tree\TreeNode;
use Symfony\Component\Filesystem\Filesystem;

class TestVaultGenerator
{
    protected $filesystem;
    protected $treeGenerator;

    public function __construct()
    {
        $this->filesystem = new Filesystem();
    }

    public function setTreeGenerator(TreeGeneratorInterface $treeGenerator = null): TestVaultGenerator
    {
        $this->treeGenerator = $treeGenerator;

        return $this;
    }

    public function getTreeGenerator(): TreeGeneratorInterface
    {
        if ($this->treeGenerator === null)
        {
            $this->setTreeGenerator(new SimpleTreeGenerator());
        }

        return $this->treeGenerator;
    }

    public function generate(): TestVault
    {
        $testVault = new TestVault();

        $contentTree = $this->getTreeGenerator()->generateTree();
        $treeIterator = new \RecursiveIteratorIterator($contentTree);

        foreach ($treeIterator as $node)
        {
            $testVault->fwrite($this->getCurrentPath($treeIterator), random_bytes(4096 * 2));
        }

        return $testVault;
    }

    protected function getCurrentPath(\RecursiveIteratorIterator $iterator): string
    {
        $pathParts = [];

        for ($currentDepth = 1; $currentDepth <= $iterator->getDepth(); $currentDepth++)
        {
            /** @var TreeNode $subIterator */
            $subIterator = $iterator->getSubIterator($currentDepth);

            $pathParts[] = $subIterator->getName();
        }

        $pathParts[] = $iterator->current()->getName();

        return implode(DIRECTORY_SEPARATOR, $pathParts);
    }
}