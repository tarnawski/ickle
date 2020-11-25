<?php

declare(strict_types=1);

namespace App\Tests\Integration;

use App\Application\Command\CreateReferenceCommand;
use App\Application\Command\CreateReferenceCommandHandler;
use App\Application\Query\ReferenceQuery;
use App\Application\ShortLink;
use App\Application\System;
use App\Domain\Reference\Identity;
use App\Domain\Reference\Name;
use App\Domain\Reference\Reference;
use App\Domain\Reference\Url;
use App\Infrastructure\Container\PimpleContainerAdapter;
use App\Infrastructure\Logger\NoopLogger;
use App\Infrastructure\ServiceBus\SymfonyCommandBus;
use App\Infrastructure\Persistence\InMemory\ReferenceRepository;
use App\Tests\Integration\Stub\StubCalendar;
use App\Tests\Integration\Stub\StubUuidProvider;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;
use Pimple\Container;
use Throwable;

class ReferenceTest extends TestCase
{
    private System $system;

    public function setUp(): void
    {
        $container = new Container();
        $container['logger'] = function ($c) {
            return new NoopLogger();
        };
        $container['reference_repository'] = function ($c) {
            return new ReferenceRepository([
                Reference::fromParameters(
                    Identity::fromString('de72ec48-62a8-4d37-84c3-08abd19fa66d'),
                    Name::fromString('facebook'),
                    Url::fromString('https://www.facebook.pl'),
                    new DateTimeImmutable('2019-06-17 18:24:21')
                ),
            ]);
        };
        $container['identity_provider'] = function ($c) {
            return new StubUuidProvider('e9e6d82f-cb9b-4456-a48d-190eccba3a42');
        };
        $container['calendar'] = function ($c) {
            return new StubCalendar(new DateTimeImmutable('2019-06-17 18:24:21'));
        };
        $container['reference_service'] = function ($c) {
            return new \App\Domain\ReferenceService(
                $c['identity_provider'],
                $c['calendar'],
                $c['reference_repository']
            );
        };
        $container['command.handler.create_reference'] = function ($c) {
            return new CreateReferenceCommandHandler(
                $c['reference_service'],
                $c['logger']
            );
        };
        $container['command_bus'] = function ($c) {
            return new SymfonyCommandBus([
                'App\Application\Command\CreateReferenceCommand' => $c['command.handler.create_reference']
            ]);
        };
        $container['query.handler.reference'] = function ($c) {
            return new \App\Application\Query\ReferenceQueryHandler(
                $c['reference_service'],
                $c['logger']
            );
        };
        $container['query_bus'] = function ($c) {
            return new \App\Infrastructure\ServiceBus\SymfonyQueryBus([
                'App\Application\Query\ReferenceQuery' => $c['query.handler.reference']
            ]);
        };
        $this->system = new System(new PimpleContainerAdapter($container));
    }

    public function testRetrieveReference(): void
    {
        $result = $this->system->query(new ReferenceQuery('facebook'));

        $this->assertInstanceOf(ShortLink::class, $result);
        $this->assertEquals('https://www.facebook.pl', $result->asString());
    }

    public function testCreateReference(): void
    {
        $this->system->handle(new CreateReferenceCommand('https://www.google.pl', 'google'));
        $result = $this->system->query(new ReferenceQuery('google'));

        $this->assertInstanceOf(ShortLink::class, $result);
        $this->assertEquals('https://www.google.pl', $result->asString());
    }

    public function testCreateAlreadyExistReference(): void
    {
        // TODO Symfony messenger change exception class
        $this->expectException(Throwable::class);
        $this->expectExceptionMessage('Reference can not be created.');

        $command = new CreateReferenceCommand('https://www.facebook.pl', 'facebook');
        $this->system->handle($command);
    }
}
