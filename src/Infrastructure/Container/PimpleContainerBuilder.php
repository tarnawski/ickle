<?php

declare(strict_types=1);

namespace App\Infrastructure\Container;

use App\Application\Command\CreateReferenceCommandHandler;
use Pimple\Container;

class PimpleContainerBuilder
{
    public static function build(): Container
    {
        $container = new Container();
        $container['logger'] = function ($c) {
            return new \App\Infrastructure\Logger\NoopLogger();
        };
        $container['reference_repository'] = function ($c) {
            return new \App\Tests\Integration\Fake\InMemoryReferenceRepository();
        };
        $container['identity_provider'] = function ($c) {
            return new \App\Infrastructure\RamseyIdentityProvider();
        };
        $container['calendar'] = function ($c) {
            return new \App\Infrastructure\SystemCalendar();
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
        $container['command.handler.create_reference'] = function ($c) {
            return new CreateReferenceCommandHandler(
                $c['identity_provider'],
                $c['calendar'],
                $c['reference_repository'],
                $c['logger']
            );
        };
        $container['command.bus'] = function ($c) {
            return new \App\Infrastructure\ServiceBus\SymfonyCommandBus([
                'App\Application\Command\CreateReferenceCommand' => $c['command.handler.create_reference']
            ]);
        };

        return $container;
    }
}
