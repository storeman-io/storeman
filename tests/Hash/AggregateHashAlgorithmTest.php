<?php

namespace Storeman\Test\Hash;

use PHPUnit\Framework\TestCase;
use Storeman\Hash\AggregateHashAlgorithm;
use Storeman\Hash\Algorithm\Md5;
use Storeman\Hash\Algorithm\Sha1;
use Storeman\Hash\Algorithm\Sha2_256;

class AggregateHashAlgorithmTest extends TestCase
{
    public function test()
    {
        $hash = new AggregateHashAlgorithm([
            'md5' => new Md5(),
            'sha1' => new Sha1(),
        ]);
        $hash->addAlgorithm(new Sha2_256());

        $hash->initialize();

        foreach (['Hello ', 'World'] as $chunk)
        {
            $hash->digest($chunk);
        }

        $hashes = $hash->finalize();

        $this->assertEquals([
            'md5' => 'b10a8db164e0754105b7a99be72e3fe5',
            'sha1' => '0a4d55a8d778e5022fab701977c5d840bbc486d0',
            'sha2-256' => 'a591a6d40bf420404a011733cfb7b190d62c65bf0bcda32b57b277d9ad9f146e',
        ], $hashes);
    }
}
