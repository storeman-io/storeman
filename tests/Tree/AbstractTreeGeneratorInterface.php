<?php

namespace Archivr\Test\Tree;

abstract class AbstractTreeGeneratorInterface implements TreeGeneratorInterface
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