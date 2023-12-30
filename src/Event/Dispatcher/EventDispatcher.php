<?php

declare(strict_types=1);

namespace Lexal\LaravelSteppedForm\Event\Dispatcher;

use Illuminate\Contracts\Events\Dispatcher;
use Lexal\SteppedForm\EventDispatcher\EventDispatcherInterface;

final class EventDispatcher implements EventDispatcherInterface
{
    public function __construct(private readonly Dispatcher $dispatcher)
    {
    }

    public function dispatch(object $event): object
    {
        $this->dispatcher->dispatch($event);

        return $event;
    }
}
