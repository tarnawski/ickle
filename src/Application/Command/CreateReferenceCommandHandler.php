<?php

declare(strict_types=1);

namespace App\Application\Command;

use App\Application\Exception\ApplicationException;
use App\Application\LoggerInterface;
use App\Domain\CalendarInterface;
use App\Domain\Exception\DomainException;
use App\Domain\IdentityProviderInterface;
use App\Domain\Reference\Identity;
use App\Domain\Reference\Name;
use App\Domain\Reference\Reference;
use App\Domain\Reference\Url;
use App\Domain\ReferenceRepositoryInterface;
use App\Infrastructure\Exception\PersistenceException;

class CreateReferenceCommandHandler
{
    private IdentityProviderInterface $identityProvider;
    private CalendarInterface $calendar;
    private ReferenceRepositoryInterface $referenceRepository;
    private LoggerInterface $logger;

    public function __construct(
        IdentityProviderInterface $identityProvider,
        CalendarInterface $calendar,
        ReferenceRepositoryInterface $referenceRepository,
        LoggerInterface $logger
    ) {
        $this->identityProvider = $identityProvider;
        $this->calendar = $calendar;
        $this->referenceRepository = $referenceRepository;
        $this->logger = $logger;
    }

    public function handle(CreateReferenceCommand $command): void
    {
        $this->logger->log(LoggerInterface::NOTICE, 'Create reference command appear in system.', [
            'url' => $command->getUrl(),
            'name' => $command->getName(),
        ]);

        if ($this->referenceRepository->exist(Name::fromString($command->getName()))) {
            $this->logger->log(LoggerInterface::ERROR, 'Reference name already exist.');
            throw new ApplicationException('Reference name already exist.');
        }

        try {
            $reference = Reference::fromParameters(
                Identity::fromString($this->identityProvider->generate()),
                Name::fromString($command->getName()),
                Url::fromString($command->getUrl()),
                $this->calendar->currentTime()
            );
        } catch (DomainException $exception) {
            $this->logger->log(LoggerInterface::ERROR, $exception->getMessage());
            throw new ApplicationException('Reference can not be created.');
        }

        try {
            $this->referenceRepository->add($reference);
        } catch (PersistenceException $exception) {
            $this->logger->log(LoggerInterface::ERROR, 'Reference can not be persist.', [
                'exception' => $exception->getMessage(),
            ]);
            throw new ApplicationException('Reference can not be persist.');
        }
    }
}
