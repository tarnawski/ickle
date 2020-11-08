<?php

declare(strict_types=1);

namespace App\Tests\Integration\Fake;

use App\Domain\Reference\Name;
use App\Domain\Reference\Reference;
use App\Domain\ReferenceRepositoryInterface;
use App\Infrastructure\Exception\PersistenceException;

class InMemoryReferenceRepository implements ReferenceRepositoryInterface
{
    public array $references = [];

    public function __construct(array $references = [])
    {
        foreach ($references as $reference) {
            $this->add($reference);
        }
    }

    public function exist(Name $name): bool
    {
        return isset($this->references[$name->asString()]);
    }

    public function get(Name $name): Reference
    {
        if (!$this->exist($name)) {
            throw new PersistenceException('Failed to fetch reference by name.');
        }

        return $this->references[$name->asString()];
    }

    public function add(Reference $reference): void
    {
        $this->references[$reference->getName()->asString()] = $reference;
    }
}
