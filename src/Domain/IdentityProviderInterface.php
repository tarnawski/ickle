<?php

namespace App\Domain;

interface IdentityProviderInterface
{
    public function generate(): string;
}
