<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain;

use App\Domain\CalendarInterface;
use App\Domain\Exception\DomainException;
use App\Domain\IdentityProviderInterface;
use App\Domain\Reference\Identity;
use App\Domain\Reference\Name;
use App\Domain\Reference\Reference;
use App\Domain\Reference\Url;
use App\Domain\ReferenceRepositoryInterface;
use App\Domain\ReferenceService;
use App\Infrastructure\Exception\PersistenceException;
use DateTimeImmutable;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class ReferenceServiceTest extends TestCase
{
    /** @var IdentityProviderInterface|MockObject */
    private $identityProvider;

    /** @var CalendarInterface|MockObject */
    private $calendar;

    /** @var ReferenceRepositoryInterface|MockObject */
    private $referenceRepository;

    public function setUp(): void
    {
        $this->identityProvider = $this->createMock(IdentityProviderInterface::class);
        $this->calendar = $this->createMock(CalendarInterface::class);
        $this->referenceRepository = $this->createMock(ReferenceRepositoryInterface::class);
    }

    public function testGetReference(): void
    {
        $reference = Reference::fromParameters(
            Identity::fromString('83866757-5304-4f11-aa71-f88bd0e4b2a0'),
            Name::fromString('qwerty'),
            Url::fromString('http://www.google.com'),
            new DateTimeImmutable()
        );
        $this->referenceRepository->method('get')->willReturn($reference);

        $service = new ReferenceService(
            $this->identityProvider,
            $this->calendar,
            $this->referenceRepository
        );
        $result = $service->getReference(Name::fromString('qwerty'));

        $this->assertEquals($reference, $result);
    }

    public function testGetNotExistReference(): void
    {
        $this->referenceRepository->method('get')->willThrowException(
            new PersistenceException('Message')
        );

        $service = new ReferenceService(
            $this->identityProvider,
            $this->calendar,
            $this->referenceRepository
        );

        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('Message');
        $service->getReference(Name::fromString('qwerty'));
    }

    public function testCreateExistingReference(): void
    {
        $this->referenceRepository->method('exist')->willReturn(true);

        $service = new ReferenceService(
            $this->identityProvider,
            $this->calendar,
            $this->referenceRepository
        );

        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('Reference name already exist.');
        $service->createReference(Name::fromString('qwerty'), Url::fromString('http://www.google.com'));
    }
}
