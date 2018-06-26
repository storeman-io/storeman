<?php

namespace Storeman\Test\LockAdapter;

use Storeman\LockAdapter\DummyLockAdapter;
use Storeman\LockAdapter\LockAdapterInterface;
use Storeman\Test\ConfiguredMockProviderTrait;

class DummyLockAdapterTest extends AbstractLockAdapterTest
{
    use ConfiguredMockProviderTrait;

    protected function getLockAdapter(): LockAdapterInterface
    {
        return new DummyLockAdapter(
            $this->getConfigurationMock(['getIdentity' => 'test identity'])
        );
    }
}
