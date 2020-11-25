<?php

declare(strict_types=1);

namespace App\Infrastructure\ServiceBus;

use App\Application\Exception\ApplicationException;
use App\Application\QueryBusInterface;
use App\Infrastructure\ServiceBus\Adapter\QueryHandlerAdapter;
use Symfony\Component\Messenger\Exception\HandlerFailedException;
use Symfony\Component\Messenger\Handler\HandlersLocator;
use Symfony\Component\Messenger\MessageBus;
use Symfony\Component\Messenger\Middleware\HandleMessageMiddleware;
use Symfony\Component\Messenger\Stamp\HandledStamp;

class SymfonyQueryBus implements QueryBusInterface
{
    private MessageBus $bus;

    public function __construct(array $mapping)
    {
        $mapping = array_map(fn ($handler) => [new QueryHandlerAdapter($handler)], $mapping);

        $this->bus = new MessageBus([new HandleMessageMiddleware(new HandlersLocator($mapping))]);
    }

    public function handle($query)
    {
        try {
            /** @var HandledStamp $stamp */
            $stamp = $this->bus->dispatch($query)->last(HandledStamp::class);
        } catch (HandlerFailedException $exception) {
            foreach ($exception->getNestedExceptions() as $nestedException) {
                if ($nestedException instanceof ApplicationException) {
                    throw $nestedException;
                }
            }

            throw $exception;
        }

        return $stamp->getResult();
    }
}
