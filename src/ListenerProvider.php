<?php

declare(strict_types=1);

namespace Hydra\Event;

use Psr\EventDispatcher\ListenerProviderInterface;

/**
 * The mutable half of the event system: where listeners are registered and, at
 * dispatch time, matched to an event.
 *
 * PSR-14 defines the read side ({@see ListenerProviderInterface::getListenersForEvent()})
 * but is deliberately silent on how listeners get in — that is left to each
 * implementation. {@see listen()} is our one small extension: register a callable
 * against an event class-string. The app calls it (typically from a provider's
 * boot()); nothing else in the framework needs a write API, so there is no
 * interface for it — the concrete class is resolved directly, the same way the
 * app resolves the concrete Router.
 *
 * Matching is by `instanceof`, not exact class: a listener registered against a
 * base class or a marker interface fires for every event that is-a that type.
 * This is the PSR-14 idiom and it is what lets an app subscribe to a whole
 * family of events (e.g. a shared base) with one registration.
 */
final class ListenerProvider implements ListenerProviderInterface
{
    /**
     * Registered listeners, keyed by the event type they were registered for.
     * Insertion order within a type is preserved so listeners fire in the order
     * they were added.
     *
     * @var array<class-string, list<callable>>
     */
    private array $listeners = [];

    /**
     * Register a listener for an event type. The type is a class-string; the
     * listener fires for that class and any subtype of it (see class docblock).
     *
     * @param class-string $eventType
     */
    public function listen(string $eventType, callable $listener): void
    {
        $this->listeners[$eventType][] = $listener;
    }

    /**
     * Every listener whose registered type the given event is an instance of,
     * yielded in registration order (and in the order the types were first
     * registered). The dispatcher calls each in turn.
     *
     * @return iterable<callable>
     */
    public function getListenersForEvent(object $event): iterable
    {
        foreach ($this->listeners as $eventType => $listeners) {
            if ($event instanceof $eventType) {
                yield from $listeners;
            }
        }
    }
}
