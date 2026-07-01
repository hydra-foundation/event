<?php

declare(strict_types=1);

namespace Hydra\Event;

use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\EventDispatcher\ListenerProviderInterface;
use Psr\EventDispatcher\StoppableEventInterface;

/**
 * The read side of the event system: hand it an event object and it calls every
 * listener the provider matched, in order.
 *
 * A pure PSR-14 dispatcher — events are plain objects (no name strings, no magic),
 * listeners are callables. The event itself is the payload and, when mutable, the
 * channel a listener uses to pass information back: {@see dispatch()} returns the
 * same object it was given so a caller can read what listeners left on it.
 *
 * Propagation control is opt-in: an event that implements
 * {@see StoppableEventInterface} is checked before each listener, so a listener
 * can halt the chain (the standard use is a "handled" flag). Events that don't
 * implement it always run every listener.
 */
final class Dispatcher implements EventDispatcherInterface
{
    public function __construct(
        private readonly ListenerProviderInterface $listeners,
    ) {}

    public function dispatch(object $event): object
    {
        // Only pay for the interface check when the event opts into stopping.
        $stoppable = $event instanceof StoppableEventInterface;

        foreach ($this->listeners->getListenersForEvent($event) as $listener) {
            if ($stoppable && $event->isPropagationStopped()) {
                break;
            }

            $listener($event);
        }

        return $event;
    }
}
