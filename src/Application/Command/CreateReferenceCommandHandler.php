<?php

declare(strict_types=1);

namespace App\Application\Command;

use App\Application\Exception\ApplicationException;
use App\Application\LoggerInterface;
use App\Domain\Exception\DomainException;
use App\Domain\Reference\Name;
use App\Domain\Reference\Url;
use App\Domain\ReferenceService;

class CreateReferenceCommandHandler
{
    private ReferenceService $referenceService;
    private LoggerInterface $logger;

    public function __construct(ReferenceService $referenceService, LoggerInterface $logger)
    {
        $this->referenceService = $referenceService;
        $this->logger = $logger;
    }

    public function handle(CreateReferenceCommand $command): void
    {
        $this->logger->log(LoggerInterface::NOTICE, 'Create reference command appear in system.', [
            'url' => $command->getUrl(),
            'name' => $command->getName(),
        ]);

        try {
            $this->referenceService->createReference(
                Name::fromString($command->getName()),
                Url::fromString($command->getUrl())
            );
        } catch (DomainException $exception) {
            $this->logger->log(LoggerInterface::ERROR, $exception->getMessage());
            throw new ApplicationException('Reference can not be created.');
        }
    }
}
