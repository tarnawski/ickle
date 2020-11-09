<?php

declare(strict_types=1);

namespace App\Application;

interface ContainerInterface
{
    public function get(string $identity);
    public function has(string $identity): bool;
}
