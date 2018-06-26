<?php

namespace Storeman\Test;

use Storeman\Test\Tree\SimpleTreeGenerator;
use Storeman\Test\Tree\TreeGeneratorInterface;
use Storeman\Test\Tree\TreeNode;
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
            $path = $this->getCurrentPath($treeIterator);

            $testVault->fwrite($path, random_bytes(4096 * 2));
            $testVault->touch($path, random_int(1, time()));
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

        return implode('/', $pathParts);
    }
}
