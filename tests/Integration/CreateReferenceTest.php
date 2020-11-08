<?php

declare(strict_types=1);

namespace App\Tests\Integration;

use App\Application\Command\CreateReferenceCommand;
use App\Application\Command\CreateReferenceCommandHandler;
use App\Domain\Reference\Identity;
use App\Domain\Reference\Name;
use App\Domain\Reference\Reference;
use App\Domain\Reference\Url;
use App\Infrastructure\Container\ContainerInterface;
use App\Infrastructure\Container\PimpleContainerAdapter;
use App\Infrastructure\Logger\NoopLogger;
use App\Infrastructure\ServiceBus\SymfonyCommandBus;
use App\Tests\Integration\Fake\InMemoryReferenceRepository;
use App\Tests\Integration\Stub\StubCalendar;
use App\Tests\Integration\Stub\StubUuidProvider;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;
use Pimple\Container;
use Throwable;

class CreateReferenceTest extends TestCase
{
    private ContainerInterface $container;

    public function setUp(): void
    {
        $container = new Container();
        $container['logger'] = function ($c) {
            return new NoopLogger();
        };
        $container['reference_repository'] = function ($c) {
            return new InMemoryReferenceRepository([
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
        $container['command.handler.create_reference'] = function ($c) {
            return new CreateReferenceCommandHandler(
                $c['identity_provider'],
                $c['calendar'],
                $c['reference_repository'],
                $c['logger']
            );
        };
        $container['command.bus'] = function ($c) {
            return new SymfonyCommandBus([
                'App\Application\Command\CreateReferenceCommand' => $c['command.handler.create_reference']
            ]);
        };
        $this->container = new PimpleContainerAdapter($container);
    }

    public function testCreateReference(): void
    {
        $command = new CreateReferenceCommand('https://www.google.pl', 'goggle');
        $this->container->get('command.bus')->handle($command);
        $result = $this->container->get('reference_repository')->get(Name::fromString('goggle'));

        $this->assertInstanceOf(Reference::class, $result);
        $this->assertEquals('e9e6d82f-cb9b-4456-a48d-190eccba3a42', $result->getIdentity()->asString());
        $this->assertEquals('goggle', $result->getName()->asString());
        $this->assertEquals('https://www.google.pl', $result->getUrl()->asString());
        $this->assertEquals('2019-06-17 18:24:21', $result->getCreatedAt()->format('Y-m-d H:i:s'));
    }

    public function testCreateAlreadyExistReference(): void
    {
        // TODO Symfony messenger change exception class
        $this->expectException(Throwable::class);
        $this->expectExceptionMessage('Reference name already exist.');

        $command = new CreateReferenceCommand('https://www.facebook.pl', 'facebook');
        $this->container->get('command.bus')->handle($command);
    }
}
