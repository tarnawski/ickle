<?php

declare(strict_types=1);

namespace App\Application\Query;

use App\Application\Exception\ApplicationException;
use App\Application\LoggerInterface;
use App\Application\ShortLink;
use App\Domain\Exception\DomainException;
use App\Domain\ReferenceService;
use App\Domain\Reference\Name;

class ReferenceQueryHandler
{
    private ReferenceService $referenceService;
    private LoggerInterface $logger;

    public function __construct(ReferenceService $referenceService, LoggerInterface $logger)
    {
        $this->referenceService = $referenceService;
        $this->logger = $logger;
    }

    public function handle(ReferenceQuery $query): ShortLink
    {
        $this->logger->log(LoggerInterface::NOTICE, 'Reference query appear in system.', [
            'name' => $query->getName(),
        ]);

        try {
            $reference = $this->referenceService->getReference(Name::fromString($query->getName()));
        } catch (DomainException $exception) {
            $this->logger->log(LoggerInterface::ERROR, $exception->getMessage());
            throw new ApplicationException('Reference can not be fetch.');
        }

        return ShortLink::fromString($reference->getUrl()->asString());
    }
}
