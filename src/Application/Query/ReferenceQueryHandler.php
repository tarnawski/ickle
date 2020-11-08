<?php

declare(strict_types=1);

namespace App\Application\Query;

use App\Application\Exception\ApplicationException;
use App\Application\LoggerInterface;
use App\Application\ShortLink;
use App\Domain\Reference\Name;
use App\Domain\ReferenceRepositoryInterface;
use App\Infrastructure\Exception\NotFoundException;

class ReferenceQueryHandler
{
    private ReferenceRepositoryInterface $referenceRepository;
    private LoggerInterface $logger;

    public function __construct(ReferenceRepositoryInterface $referenceRepository, LoggerInterface $logger)
    {
        $this->referenceRepository = $referenceRepository;
        $this->logger = $logger;
    }

    public function handle(ReferenceQuery $query): ShortLink
    {
        $this->logger->log(LoggerInterface::NOTICE, 'Reference query appear in system.', [
            'name' => $query->getName(),
        ]);

        try {
            $reference = $this->referenceRepository->get(Name::fromString($query->getName()));
        } catch (NotFoundException $exception) {
            $this->logger->log(LoggerInterface::ERROR, 'Reference can not be fetch.');
            throw new ApplicationException('Reference can not be fetch.');
        }

        return ShortLink::fromString($reference->getUrl()->asString());
    }
}
