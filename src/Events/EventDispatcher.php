<?php

declare(strict_types=1);

namespace SumsubSdk\Sumsub\Events;

/**
 * Event dispatcher for handling event listeners
 */
class EventDispatcher
{
    /** @var array<string, array<callable>> */
    private array $listeners = [];

    /**
     * Subscribe to an event
     *
     * @param string $eventClass Full event class name
     * @param callable $listener Callback function
     */
    public function listen(string $eventClass, callable $listener): void
    {
        if (!isset($this->listeners[$eventClass])) {
            $this->listeners[$eventClass] = [];
        }

        $this->listeners[$eventClass][] = $listener;
    }

    /**
     * Dispatch an event to all listeners
     *
     * @param Event $event Event instance
     */
    public function dispatch(Event $event): void
    {
        $eventClass = get_class($event);

        if (!isset($this->listeners[$eventClass])) {
            return;
        }

        foreach ($this->listeners[$eventClass] as $listener) {
            $listener($event);
        }
    }

    /**
     * Check if event has listeners
     *
     * @param string $eventClass Full event class name
     */
    public function hasListeners(string $eventClass): bool
    {
        return isset($this->listeners[$eventClass]) && count($this->listeners[$eventClass]) > 0;
    }

    /**
     * Remove all listeners for an event
     *
     * @param string $eventClass Full event class name
     */
    public function forget(string $eventClass): void
    {
        unset($this->listeners[$eventClass]);
    }

    /**
     * Remove all listeners
     */
    public function forgetAll(): void
    {
        $this->listeners = [];
    }

    /**
     * Get all listeners for an event
     *
     * @param string $eventClass Full event class name
     * @return array<callable>
     */
    public function getListeners(string $eventClass): array
    {
        return $this->listeners[$eventClass] ?? [];
    }
}

