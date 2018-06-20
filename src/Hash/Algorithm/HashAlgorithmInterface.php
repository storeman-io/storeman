<?php

namespace Storeman\Hash\Algorithm;

interface HashAlgorithmInterface
{
    public function getName(): string;
    public function initialize(): void;
    public function digest(string $buffer): void;
    public function finalize(): string;
}
