<?php
declare(strict_types = 1);

namespace Innmind\Async\OperatingSystem;

use Innmind\Async\OperatingSystem\{
    Filesystem\Async as AsyncFilesystem,
    Ports\Async as AsyncPorts,
    Sockets\Async as AsyncSockets,
    Remote\Async as AsyncRemote,
    CurrentProcess\Async as AsyncCurrentProcess,
};
use Innmind\OperatingSystem\{
    OperatingSystem,
    Filesystem,
    Ports,
    Sockets,
    Remote,
    CurrentProcess,
    Config,
};
use Innmind\TimeContinuum\Clock;
use Innmind\Server\{
    Control\Server as ServerControl,
    Control\Servers,
    Status\Server as ServerStatus,
    Status\ServerFactory,
};
use Innmind\Stream\{
    Capabilities,
    Streams,
};
use Innmind\Mantle\Suspend;
use Innmind\Async\Stream\Streams as AsyncStreams;
use Innmind\Async\TimeWarp\Halt;

final class Async implements OperatingSystem
{
    private OperatingSystem $synchronous;
    private Suspend $suspend;
    private Config $config;
    private Capabilities $capabilities;
    private Halt $halt;
    private ?Filesystem $filesystem = null;
    private ?ServerStatus $status = null;
    private ?ServerControl $control = null;
    private ?Ports $ports = null;
    private ?Sockets $sockets = null;
    private ?Remote $remote = null;
    private ?CurrentProcess $process = null;

    private function __construct(
        OperatingSystem $synchronous,
        Suspend $suspend,
        Config $config,
    ) {
        $this->synchronous = $synchronous;
        $this->suspend = $suspend;
        $this->config = $config;
        $this->capabilities = AsyncStreams::of(
            $config->streamCapabilities(),
            $suspend,
            $this->clock(),
        );
        $this->halt = Halt::of($this->clock(), $suspend);
    }

    public static function of(
        OperatingSystem $synchronous,
        Suspend $suspend,
        Config $config = null,
    ): self {
        return new self($synchronous, $suspend, $config ?? Config::of());
    }

    public function clock(): Clock
    {
        return $this->synchronous->clock();
    }

    public function filesystem(): Filesystem
    {
        return $this->filesystem ??= AsyncFilesystem::of(
            $this->synchronous->filesystem(),
            $this->config,
            $this->capabilities,
            $this->control()->processes(),
            $this->halt,
        );
    }

    public function status(): ServerStatus
    {
        return $this->status ??= ServerFactory::build(
            $this->clock(),
            $this->control(),
            $this->config->environmentPath(),
        );
    }

    public function control(): ServerControl
    {
        return $this->control ??= Servers\Unix::of(
            $this->clock(),
            $this->capabilities,
            $this->halt,
        );
    }

    public function ports(): Ports
    {
        return $this->ports ??= AsyncPorts::of(
            $this->synchronous->ports(),
            $this->suspend,
        );
    }

    public function sockets(): Sockets
    {
        return $this->sockets ??= AsyncSockets::of(
            $this->synchronous->sockets(),
            $this->suspend,
            $this->capabilities,
        );
    }

    public function remote(): Remote
    {
        return $this->remote ??= AsyncRemote::of(
            $this->synchronous->remote(),
            $this->suspend,
            $this->clock(),
            $this->capabilities,
            $this->control(),
        );
    }

    public function process(): CurrentProcess
    {
        return $this->process ??= AsyncCurrentProcess::of(
            $this->synchronous->process(),
            $this->halt,
        );
    }
}
