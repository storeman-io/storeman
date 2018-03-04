<?php

namespace Archivr\Test\ConnectionAdapter;

use Archivr\StorageDriver\StorageDriverFactoryContainer;
use Archivr\StorageDriver\StorageDriverInterface;
use Archivr\VaultConfiguration;
use Archivr\Exception\Exception;
use PHPUnit\Framework\TestCase;

class ConnectionAdapterFactoryContainerTest extends TestCase
{
    public function testAdditionAndRetrieval()
    {
        /** @var VaultConfiguration $config */
        $config = $this->createMock(VaultConfiguration::class);
        $firstAdapter = $this->createMock(StorageDriverInterface::class);

        $container = new StorageDriverFactoryContainer([
            'initial' => function() use ($firstAdapter)
            {
                return $firstAdapter;
            }
        ]);

        $this->assertNull($container->get('x', $config));
        $this->assertTrue($container->has('initial'));
        $this->assertFalse($container->has('x'));
        $this->assertEquals($firstAdapter, $container->get('initial', $config));

        $secondAdapter = $this->createMock(StorageDriverInterface::class);

        $container->register('second', function() use ($secondAdapter) {

            return $secondAdapter;
        });

        $this->assertEquals($secondAdapter, $container->get('second', $config));
    }

    public function testInvalidFactory()
    {
        /** @var VaultConfiguration $config */
        $config = $this->createMock(VaultConfiguration::class);

        $container = new StorageDriverFactoryContainer([
            'test' => function() {

                return new \DateTime();
            }
        ]);

        $this->expectException(Exception::class);

        $container->get('test', $config);
    }
}
