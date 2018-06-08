<?php

namespace Storeman\Test\Tree;

class SimpleTreeGenerator extends AbstractTreeGenerator
{
    /**
     * @var int
     */
    protected $levels = 2;

    /**
     * @var int
     */
    protected $branching = 2;

    /**
     * @var int
     */
    protected $leafs = 3;

    public function generateTree(): TreeNode
    {
        $node = new TreeNode();

        $this->populateNode($node, $this->levels);

        return $node;
    }

    protected function populateNode(TreeNode $node, int $levels): TreeNode
    {
        for ($i = 0; $i < $this->leafs; $i++)
        {
            $node->addChild(new TreeNode($this->generateChildName($node)));
        }

        if ($levels > 0)
        {
            for ($i = 0; $i < $this->branching; $i++)
            {
                $subTree = new TreeNode($this->generateChildName($node));

                $this->populateNode($subTree, $levels - 1);

                $node->addChild($subTree);
            }
        }

        return $node;
    }
}
