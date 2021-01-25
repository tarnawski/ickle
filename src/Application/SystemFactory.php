<?php

declare(strict_types=1);

namespace App\Application;

use App\Application\Command\CreateReferenceCommandHandler;
use App\Application\Query\ReferenceQueryHandler;
use App\Infrastructure\Container\PimpleContainerAdapter;
use App\Infrastructure\Logger\NoopLogger;
use App\Infrastructure\Persistence\PDO\PDOFactory;
use App\Infrastructure\Persistence\PDO\ReferenceRepository;
use App\Infrastructure\RamseyIdentityProvider;
use App\Infrastructure\ServiceBus\SymfonyCommandBus;
use App\Infrastructure\ServiceBus\SymfonyQueryBus;
use App\Infrastructure\SystemCalendar;
use PDO;
use Pimple\Container;

class SystemFactory
{
    public static function create(string $database): System
    {
        $container = new Container();
        $container['logger'] = function ($container) {
            return new NoopLogger();
        };
        $container['pdo'] = function ($container) use ($database) {
            return PDOFactory::createFromDsn($database);
        };
        $container['reference_repository'] = function ($container) {
            return new ReferenceRepository(
                $container['pdo']
            );
        };
        $container['identity_provider'] = function ($container) {
            return new RamseyIdentityProvider();
        };
        $container['calendar'] = function ($container) {
            return new SystemCalendar();
        };
        $container['reference_service'] = function ($container) {
            return new \App\Domain\ReferenceService(
                $container['identity_provider'],
                $container['calendar'],
                $container['reference_repository']
            );
        };
        $container['query.handler.reference'] = function ($container) {
            return new ReferenceQueryHandler(
                $container['reference_service'],
                $container['logger']
            );
        };
        $container['query_bus'] = function ($container) {
            return new SymfonyQueryBus([
                'App\Application\Query\ReferenceQuery' => $container['query.handler.reference'],
            ]);
        };
        $container['command.handler.create_reference'] = function ($container) {
            return new CreateReferenceCommandHandler(
                $container['reference_service'],
                $container['logger']
            );
        };
        $container['command_bus'] = function ($container) {
            return new SymfonyCommandBus([
                'App\Application\Command\CreateReferenceCommand' => $container['command.handler.create_reference'],
            ]);
        };

        return new System(new PimpleContainerAdapter($container));
    }
}
