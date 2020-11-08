<?php

namespace App\Domain;

use App\Domain\Reference\Identity;
use App\Domain\Reference\Name;
use App\Domain\Reference\Reference;

interface ReferenceRepositoryInterface
{
    public function get(Name $name): Reference;
    public function exist(Name $name): bool;
    public function add(Reference $reference): void;
}
