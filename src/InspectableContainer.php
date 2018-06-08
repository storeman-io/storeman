<?php

namespace Storeman;

final class InspectableContainer extends \League\Container\Container
{
    /**
     * Builds and returns array of all provided service names.
     *
     * @return array
     */
    public function getProvidedServiceNames(): array
    {
        return array_merge(
            array_keys($this->definitions),
            array_keys($this->shared),
            array_keys($this->sharedDefinitions)
        );
    }
}
