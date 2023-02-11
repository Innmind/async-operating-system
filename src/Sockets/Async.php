<?php
declare(strict_types = 1);

namespace Innmind\Async\OperatingSystem\Sockets;

use Innmind\Async\Socket\{
    Server\Async as Server,
    Client\Async as Client,
};
use Innmind\OperatingSystem\Sockets;
use Innmind\Socket\Address\Unix;
use Innmind\Stream\{
    Capabilities,
    Watch,
};
use Innmind\TimeContinuum\ElapsedPeriod;
use Innmind\Immutable\Maybe;
use Innmind\Mantle\Suspend;

final class Async implements Sockets
{
    private Sockets $synchronous;
    private Suspend $suspend;
    private Capabilities $capabilities;

    private function __construct(
        Sockets $synchronous,
        Suspend $suspend,
        Capabilities $capabilities,
    ) {
        $this->synchronous = $synchronous;
        $this->suspend = $suspend;
        $this->capabilities = $capabilities;
    }

    /**
     * @internal
     */
    public static function of(
        Sockets $synchronous,
        Suspend $suspend,
        Capabilities $capabilities,
    ): self {
        return new self($synchronous, $suspend, $capabilities);
    }

    /** @psalm-suppress InvalidReturnType */
    public function open(Unix $address): Maybe
    {
        /** @psalm-suppress InvalidReturnStatement */
        return $this
            ->synchronous
            ->open($address)
            ->map(fn($server) => Server::of($server, $this->suspend));
    }

    /** @psalm-suppress InvalidReturnType */
    public function takeOver(Unix $address): Maybe
    {
        /** @psalm-suppress InvalidReturnStatement */
        return $this
            ->synchronous
            ->takeOver($address)
            ->map(fn($server) => Server::of($server, $this->suspend));
    }

    /** @psalm-suppress InvalidReturnType */
    public function connectTo(Unix $address): Maybe
    {
        /** @psalm-suppress InvalidReturnStatement */
        return $this
            ->synchronous
            ->connectTo($address)
            ->map(fn($client) => Client::of($client, $this->suspend));
    }

    public function watch(ElapsedPeriod $timeout = null): Watch
    {
        return match ($timeout) {
            null => $this->capabilities->watch()->waitForever(),
            default => $this->capabilities->watch()->timeoutAfter($timeout),
        };
    }
}
