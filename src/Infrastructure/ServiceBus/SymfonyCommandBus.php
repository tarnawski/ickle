<?php

declare(strict_types=1);

namespace App\Infrastructure\ServiceBus;

use App\Application\CommandBusInterface;
use App\Application\Exception\ApplicationException;
use App\Infrastructure\ServiceBus\Adapter\CommandHandlerAdapter;
use Symfony\Component\Messenger\Exception\HandlerFailedException;
use Symfony\Component\Messenger\Handler\HandlersLocator;
use Symfony\Component\Messenger\MessageBus;
use Symfony\Component\Messenger\Middleware\HandleMessageMiddleware;

class SymfonyCommandBus implements CommandBusInterface
{
    private MessageBus $bus;

    public function __construct(array $mapping)
    {
        $mapping = array_map(fn ($handler) => [new CommandHandlerAdapter($handler)], $mapping);

        $this->bus = new MessageBus([new HandleMessageMiddleware(new HandlersLocator($mapping))]);
    }

    public function handle($command): void
    {
        try {
            $this->bus->dispatch($command);
        } catch (HandlerFailedException $exception) {
            foreach ($exception->getNestedExceptions() as $nestedException) {
                if ($nestedException instanceof ApplicationException) {
                    throw $nestedException;
                }
            }

            throw $exception;
        }
    }
}
