<?php

namespace Storeman\Test\Hash;

use PHPUnit\Framework\TestCase;
use Storeman\Hash\HashContainer;

class HashContainerTest extends TestCase
{
    public function testAsContainer()
    {
        $hashes = new HashContainer();

        $this->assertFalse($hashes->hasHash('func1'));
        $this->assertNull($hashes->getHash('func1'));

        $hashes->addHash('func2', 'abc');

        $this->assertFalse($hashes->hasHash('func1'));
        $this->assertNull($hashes->getHash('func1'));
        $this->assertTrue($hashes->hasHash('func2'));
        $this->assertEquals('abc', $hashes->getHash('func2'));
    }

    public function testOverridePrevention()
    {
        $this->expectException(\Exception::class);

        $hashes = new HashContainer();
        $hashes->addHash('func', 'abc');
        $hashes->addHash('func', 'cba');
    }

    public function testComparison()
    {
        $hashesA = new HashContainer();
        $hashesA->addHash('func1', 'abc');
        $hashesA->addHash('func2', 'def');

        $hashesB = new HashContainer();

        $this->assertFalse($hashesA->equals(null));
        $this->assertTrue($hashesA->equals($hashesB));
        $this->assertTrue($hashesB->equals($hashesA));

        $hashesB->addHash('func1', 'abc');

        $this->assertTrue($hashesA->equals($hashesB));
        $this->assertTrue($hashesB->equals($hashesA));

        $hashesB->addHash('func3', 'ghi');

        $this->assertTrue($hashesA->equals($hashesB));
        $this->assertTrue($hashesB->equals($hashesA));

        $hashesB->addHash('func2', 'xxx');

        $this->assertFalse($hashesA->equals($hashesB));
        $this->assertFalse($hashesB->equals($hashesA));
    }

    /**
     * @depends testComparison
     */
    public function testSerialization()
    {
        $hashesA = new HashContainer();
        $hashesA->addHash('func1', 'abc');
        $hashesA->addHash('func2', 'def');

        $hashesB = (new HashContainer())->unserialize($hashesA->serialize());

        $this->assertTrue($hashesA->equals($hashesB));
    }

    public function testCount()
    {
        $hashes = new HashContainer();

        $this->assertEquals(0, count($hashes));

        $hashes->addHash('func', 'abc');

        $this->assertEquals(1, count($hashes));
    }

    public function testIteration()
    {
        $map = [
            'func1' => 'abc',
            'func2' => 'def',
        ];

        $hashes = new HashContainer();

        foreach ($map as $algorithm => $hash)
        {
            $hashes->addHash($algorithm, $hash);
        }

        $this->assertEquals($map, iterator_to_array($hashes->getIterator()));
    }
}
