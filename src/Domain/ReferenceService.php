<?php

declare(strict_types=1);

namespace App\Domain;

use App\Domain\Exception\DomainException;
use App\Domain\Reference\Identity;
use App\Domain\Reference\Name;
use App\Domain\Reference\Reference;
use App\Domain\Reference\Url;
use App\Infrastructure\Exception\PersistenceException;

class ReferenceService
{
    private IdentityProviderInterface $identityProvider;
    private CalendarInterface $calendar;
    private ReferenceRepositoryInterface $referenceRepository;

    public function __construct(
        IdentityProviderInterface $identityProvider,
        CalendarInterface $calendar,
        ReferenceRepositoryInterface $referenceRepository
    ) {
        $this->identityProvider = $identityProvider;
        $this->calendar = $calendar;
        $this->referenceRepository = $referenceRepository;
    }

    public function getReference(Name $name): Reference
    {
        try {
            $reference = $this->referenceRepository->get($name);
        } catch (PersistenceException $exception) {
            throw new DomainException($exception->getMessage());
        }

        return $reference;
    }

    public function createReference(Name $name, Url $url): void
    {
        if ($this->referenceRepository->exist($name)) {
            throw new DomainException('Reference name already exist.');
        }

        $reference = Reference::fromParameters(
            Identity::fromString($this->identityProvider->generate()),
            $name,
            $url,
            $this->calendar->currentTime()
        );

        try {
            $this->referenceRepository->add($reference);
        } catch (PersistenceException $exception) {
            throw new DomainException($exception->getMessage());
        }
    }
}
