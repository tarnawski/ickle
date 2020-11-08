<?php

declare(strict_types=1);

namespace App\Infrastructure;

use App\Domain\IdentityProviderInterface;
use Ramsey\Uuid\Uuid;

class RamseyIdentityProvider implements IdentityProviderInterface
{
    public function generate(): string
    {
        return Uuid::uuid4()->toString();
    }
}
