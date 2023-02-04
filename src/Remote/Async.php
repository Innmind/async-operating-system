<?php
declare(strict_types = 1);

namespace Innmind\Async\OperatingSystem\Remote;

use Innmind\Async\Socket\Client\Async as Client;
use Innmind\OperatingSystem\Remote;
use Innmind\TimeContinuum\Clock;
use Innmind\Server\Control\{
    Server,
    Servers,
};
use Innmind\HttpTransport\{
    Transport as HttpTransport,
    Curl,
};
use Innmind\Filesystem\Chunk;
use Innmind\Stream\Capabilities;
use Innmind\Socket\Internet\Transport;
use Innmind\Url\{
    Url,
    Authority,
    Authority\Port,
};
use Formal\AccessLayer\Connection;
use Innmind\Immutable\Maybe;
use Innmind\Mantle\Suspend;

final class Async implements Remote
{
    private Remote $synchronous;
    private Suspend $suspend;
    private Clock $clock;
    private Capabilities $capabilities;
    private Server $server;
    private ?HttpTransport $http = null;

    private function __construct(
        Remote $synchronous,
        Suspend $suspend,
        Clock $clock,
        Capabilities $capabilities,
        Server $server,
    ) {
        $this->synchronous = $synchronous;
        $this->suspend = $suspend;
        $this->clock = $clock;
        $this->capabilities = $capabilities;
        $this->server = $server;
    }

    /**
     * @internal
     */
    public static function of(
        Remote $synchronous,
        Suspend $suspend,
        Clock $clock,
        Capabilities $capabilities,
        Server $server,
    ): self {
        return new self($synchronous, $suspend, $clock, $capabilities, $server);
    }

    public function ssh(Url $server): Server
    {
        $port = null;

        if ($server->authority()->port()->value() !== Port::none()->value()) {
            $port = $server->authority()->port();
        }

        return new Servers\Remote(
            $this->server,
            $server->authority()->userInformation()->user(),
            $server->authority()->host(),
            $port,
        );
    }

    /** @psalm-suppress InvalidReturnType */
    public function socket(Transport $transport, Authority $authority): Maybe
    {
        /** @psalm-suppress InvalidReturnStatement */
        return $this
            ->synchronous
            ->socket($transport, $authority)
            ->map(fn($client) => Client::of($client, $this->suspend));
    }

    public function http(): HttpTransport
    {
        return $this->http ??= Curl::of(
            $this->clock,
            new Chunk,
            $this->capabilities,
        );
    }

    public function sql(Url $server): Connection
    {
        return $this->synchronous->sql($server);
    }
}
