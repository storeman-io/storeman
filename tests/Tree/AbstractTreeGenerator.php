<?php

namespace Storeman\Test\Tree;

abstract class AbstractTreeGenerator implements TreeGeneratorInterface
{
    protected function generateChildName(TreeNode $treeNode): string
    {
        do
        {
            $name = uniqid();
        }
        while($treeNode->getChildByName($name) !== null);

        return $name;
    }
}
