<?php

namespace Archivr\Test\Tree;

interface TreeGeneratorInterface
{
    public function generateTree(): TreeNode;
}