<?php
declare(strict_types = 1);

namespace Innmind\Async\OperatingSystem\Ports;

use Innmind\Async\Socket\Server\Async as Server;
use Innmind\OperatingSystem\Ports;
use Innmind\Url\Authority\Port;
use Innmind\Socket\Internet\Transport;
use Innmind\IP\IP;
use Innmind\Immutable\Maybe;
use Innmind\Mantle\Suspend;

final class Async implements Ports
{
    private Ports $synchronous;
    private Suspend $suspend;

    private function __construct(Ports $synchronous, Suspend $suspend)
    {
        $this->synchronous = $synchronous;
        $this->suspend = $suspend;
    }

    /**
     * @internal
     */
    public static function of(Ports $synchronous, Suspend $suspend): self
    {
        return new self($synchronous, $suspend);
    }

    /** @psalm-suppress InvalidReturnType */
    public function open(Transport $transport, IP $ip, Port $port): Maybe
    {
        /** @psalm-suppress InvalidReturnStatement */
        return $this
            ->synchronous
            ->open($transport, $ip, $port)
            ->map(fn($server) => Server::of($server, $this->suspend));
    }
}
