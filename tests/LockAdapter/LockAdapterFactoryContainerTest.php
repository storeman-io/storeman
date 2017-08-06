<?php

namespace LockAdapter\Test\LockAdapter;

use Archivr\ConnectionConfiguration;
use Archivr\LockAdapter\LockAdapterFactoryContainer;
use Archivr\LockAdapter\LockAdapterInterface;
use PHPUnit\Framework\TestCase;

class LockAdapterFactoryContainerTest extends TestCase
{
    public function testAdditionAndRetrieval()
    {
        /** @var ConnectionConfiguration $config */
        $config = $this->createMock(ConnectionConfiguration::class);
        $firstAdapter = $this->createMock(LockAdapterInterface::class);

        $container = new LockAdapterFactoryContainer([
            'initial' => function() use ($firstAdapter)
            {
                return $firstAdapter;
            }
        ]);

        $this->assertNull($container->get('x', $config));
        $this->assertTrue($container->has('initial'));
        $this->assertFalse($container->has('x'));
        $this->assertEquals($firstAdapter, $container->get('initial', $config));

        $secondAdapter = $this->createMock(LockAdapterInterface::class);

        $container->register('second', function() use ($secondAdapter) {

            return $secondAdapter;
        });

        $this->assertEquals($secondAdapter, $container->get('second', $config));
    }

    public function testInvalidFactory()
    {
        /** @var ConnectionConfiguration $config */
        $config = $this->createMock(ConnectionConfiguration::class);

        $container = new LockAdapterFactoryContainer([
            'test' => function() {

                return new \DateTime();
            }
        ]);

        $this->expectException(\LogicException::class);

        $container->get('test', $config);
    }
}
