<?php

declare(strict_types=1);

namespace Hydra\Event;

use Hydra\Core\Contracts\ContainerInterface;
use Hydra\Core\Providers\ServiceProvider;
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\EventDispatcher\ListenerProviderInterface;

/**
 * Wires the event system into an application.
 *
 * Like hydrakit/session and hydrakit/auth (and unlike the stateless hydrakit/validation /
 * hydrakit/csrf), this package binds interfaces to defaults and — crucially — needs
 * a SHARED listener registry, so it earns a provider and ships its own.
 *
 * The one thing to get right is that a single {@see ListenerProvider} instance
 * backs all three bindings: the app resolves it (by class-string) to register
 * listeners, and the dispatcher reads from that very same instance. Bind it twice
 * as two instances and listeners would be registered into a provider the
 * dispatcher never consults.
 *
 * What it does NOT bind is any listener — mirroring auth's unbound
 * UserProviderInterface and authorization's absent ability registry. The
 * mechanism ships here; which events matter and who reacts to them is app policy,
 * registered via {@see ListenerProvider::listen()} at the composition root.
 */
final class EventServiceProvider extends ServiceProvider
{
    public function register(ContainerInterface $container): void
    {
        // The one shared registry, bound under its own class-string so the app
        // can resolve it to call listen().
        $container->singleton(ListenerProvider::class, fn () => new ListenerProvider);

        // The PSR-14 read interface points at that same instance, so a consumer
        // that depends only on the interface still sees the app's listeners.
        $container->singleton(
            ListenerProviderInterface::class,
            fn () => $container->get(ListenerProvider::class),
        );

        // The dispatcher over that registry. Subsystems (like auth) depend on the
        // PSR interface, never on this concrete class.
        $container->singleton(EventDispatcherInterface::class, function () use ($container) {
            return new Dispatcher($container->get(ListenerProviderInterface::class));
        });
    }
}
