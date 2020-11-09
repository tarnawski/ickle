<?php

declare(strict_types=1);

namespace App\Infrastructure\Container;

use App\Application\ContainerInterface;
use Pimple\Container;

class PimpleContainerAdapter implements ContainerInterface
{
    private Container $container;

    public function __construct(Container $container)
    {
        $this->container = $container;
    }

    public function get(string $identity)
    {
        return $this->container[$identity];
    }

    public function has(string $identity): bool
    {
        return isset($this->container[$identity]);
    }
}
