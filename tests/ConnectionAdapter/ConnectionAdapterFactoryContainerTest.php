<?php

namespace Archivr\Test\ConnectionAdapter;

use Archivr\ConnectionAdapter\ConnectionAdapterFactoryContainer;
use Archivr\ConnectionAdapter\ConnectionAdapterInterface;
use Archivr\ConnectionConfiguration;
use PHPUnit\Framework\TestCase;

class ConnectionAdapterFactoryContainerTest extends TestCase
{
    public function testAdditionAndRetrieval()
    {
        /** @var ConnectionConfiguration $config */
        $config = $this->createMock(ConnectionConfiguration::class);
        $firstAdapter = $this->createMock(ConnectionAdapterInterface::class);

        $container = new ConnectionAdapterFactoryContainer([
            'initial' => function() use ($firstAdapter)
            {
                return $firstAdapter;
            }
        ]);

        $this->assertNull($container->get('x', $config));
        $this->assertTrue($container->has('initial'));
        $this->assertFalse($container->has('x'));
        $this->assertEquals($firstAdapter, $container->get('initial', $config));

        $secondAdapter = $this->createMock(ConnectionAdapterInterface::class);

        $container->register('second', function() use ($secondAdapter) {

            return $secondAdapter;
        });

        $this->assertEquals($secondAdapter, $container->get('second', $config));
    }

    public function testInvalidFactory()
    {
        /** @var ConnectionConfiguration $config */
        $config = $this->createMock(ConnectionConfiguration::class);

        $container = new ConnectionAdapterFactoryContainer([
            'test' => function() {

                return new \DateTime();
            }
        ]);

        $this->expectException(\LogicException::class);

        $container->get('test', $config);
    }
}
