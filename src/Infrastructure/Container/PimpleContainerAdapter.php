<?php

declare(strict_types=1);

namespace App\Infrastructure\Container;

use Pimple\Container;

class PimpleContainerAdapter implements ContainerInterface
{
    private Container $container;

    public function __construct(Container $container)
    {
        $this->container = $container;
    }

    public function get(string $id)
    {
        return $this->container[$id];
    }

    public function has(string $id): bool
    {
        return isset($this->container[$id]);
    }
}
