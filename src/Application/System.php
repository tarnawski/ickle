<?php

declare(strict_types=1);

namespace App\Application;

use App\Application\Exception\ApplicationException;

final class System
{
    private ContainerInterface $container;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    public function handle($command): void
    {
        if (!$this->container->has('command_bus')) {
            throw new ApplicationException('No command bus configured in system container.');
        }
        $this->container->get('command_bus')->handle($command);
    }

    public function query($query)
    {
        if (!$this->container->has('query_bus')) {
            throw new ApplicationException('No query bus configured in system container.');
        }

        return $this->container->get('query_bus')->handle($query);
    }
}
