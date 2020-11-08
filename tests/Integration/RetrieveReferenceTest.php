<?php

declare(strict_types=1);

namespace App\Tests\Integration;

use App\Application\Query\ReferenceQuery;
use App\Application\ShortLink;
use App\Domain\Reference\Identity;
use App\Domain\Reference\Name;
use App\Domain\Reference\Reference;
use App\Domain\Reference\Url;
use App\Infrastructure\Container\ContainerInterface;
use App\Infrastructure\Container\PimpleContainerAdapter;
use App\Infrastructure\Logger\NoopLogger;
use App\Infrastructure\Persistence\InMemory\ReferenceRepository;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;
use Pimple\Container;

class RetrieveReferenceTest extends TestCase
{
    private ContainerInterface $container;

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
                    Name::fromString('google'),
                    Url::fromString('https://www.google.pl'),
                    new DateTimeImmutable('2019-06-17 18:24:21')
                ),
            ]);
        };
        $container['identity_provider'] = function ($c) {
            return new \App\Tests\Integration\Stub\StubUuidProvider('e9e6d82f-cb9b-4456-a48d-190eccba3a42');
        };
        $container['calendar'] = function ($c) {
            return new \App\Tests\Integration\Stub\StubCalendar(new DateTimeImmutable('2019-06-17 18:24:21'));
        };
        $container['query.handler.reference'] = function ($c) {
            return new \App\Application\Query\ReferenceQueryHandler(
                $c['reference_repository'],
                $c['logger']
            );
        };
        $container['query.bus'] = function ($c) {
            return new \App\Infrastructure\ServiceBus\SymfonyQueryBus([
                'App\Application\Query\ReferenceQuery' => $c['query.handler.reference']
            ]);
        };
        $this->container = new PimpleContainerAdapter($container);
    }

    public function testRetrieveReference(): void
    {
        $query = new ReferenceQuery('google');
        $result = $this->container->get('query.bus')->handle($query);

        $this->assertInstanceOf(ShortLink::class, $result);
        $this->assertEquals('https://www.google.pl', $result->asString());
    }
}
