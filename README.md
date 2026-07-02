# Hydra Event

A minimal [PSR-14](https://www.php-fig.org/psr/psr-14/) event dispatcher for the
Hydra framework. It lets one subsystem announce that something happened without
knowing — or depending on — whoever cares.

Like the rest of Hydra it binds to the PSR interfaces rather than inventing its
own: events are plain objects, listeners are callables, and the dispatcher is a
`Psr\EventDispatcher\EventDispatcherInterface`. There is nothing to learn beyond
the standard.

## What it ships

- **`Dispatcher`** — a PSR-14 `EventDispatcherInterface`. `dispatch($event)` calls
  every matched listener in order and returns the same event object (so a caller
  can read what listeners left on a mutable event). Honours
  `StoppableEventInterface`.
- **`ListenerProvider`** — a PSR-14 `ListenerProviderInterface` with one small,
  non-standard extension: `listen(string $eventType, callable $listener)`. PSR-14
  is silent on registration; this is how listeners get in. Matching is by
  `instanceof`, so a listener on a base class or marker interface fires for every
  subtype.
- **`EventServiceProvider`** — binds a single shared `ListenerProvider` behind
  three keys (`ListenerProvider`, `ListenerProviderInterface`,
  `EventDispatcherInterface`) so the app registers into the same instance the
  dispatcher reads from.

It ships the mechanism only. Which events matter and who reacts to them is app
policy — registered at the composition root, the same "ship the verb, the app
supplies the noun" seam as auth's user provider.

## Using it

Register the provider (before any provider whose services dispatch events):

```php
$app->register(new SessionServiceProvider)
    ->register(new EventServiceProvider)
    ->register(new AuthServiceProvider)   // auth picks up the dispatcher if bound
    ->register(new AppServiceProvider);
```

Define an event — a plain object, immutable when it is just a fact:

```php
final class OrderPlaced
{
    public function __construct(public readonly int $orderId) {}
}
```

Register a listener where the app composes itself (typically a provider's `boot()`):

```php
$container->get(ListenerProvider::class)
    ->listen(OrderPlaced::class, [$auditListener, 'onOrderPlaced']);
```

Dispatch it from wherever the thing happens:

```php
$this->events->dispatch(new OrderPlaced($order->id));
```

A subsystem that wants to stay usable without the event package should depend on a
nullable `?EventDispatcherInterface` and dispatch with `?->` — exactly what
`hydrakit/auth` does.
