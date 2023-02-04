<?php
declare(strict_types = 1);

namespace Innmind\Async\OperatingSystem\Filesystem;

use Innmind\OperatingSystem\{
    Filesystem,
    Config,
};
use Innmind\Filesystem\Adapter;
use Innmind\Server\Control\Server\Processes;
use Innmind\Async\TimeWarp\Suspend as Halt;
use Innmind\Stream\Capabilities;
use Innmind\Url\Path;
use Innmind\FileWatch\{
    Factory,
    Ping,
    Watch,
};
use Innmind\Immutable\Maybe;

final class Async implements Filesystem
{
    private Filesystem $synchronous;
    private Config $config;
    private Capabilities $capabilities;
    private Watch $watch;
    /** @var \WeakMap<Adapter, string> */
    private \WeakMap $mounted;

    private function __construct(
        Filesystem $synchronous,
        Config $config,
        Capabilities $capabilities,
        Processes $processes,
        Halt $halt,
    ) {
        $this->synchronous = $synchronous;
        $this->config = $config;
        $this->capabilities = $capabilities;
        $this->watch = Factory::build($processes, $halt);
        /** @var \WeakMap<Adapter, string> */
        $this->mounted = new \WeakMap;
    }

    /**
     * @internal
     */
    public static function of(
        Filesystem $synchronous,
        Config $config,
        Capabilities $capabilities,
        Processes $processes,
        Halt $halt,
    ): self {
        return new self($synchronous, $config, $capabilities, $processes, $halt);
    }

    public function mount(Path $path): Adapter
    {
        foreach ($this->mounted as $adapter => $mounted) {
            if ($path->toString() === $mounted) {
                return $adapter;
            }
        }

        $adapter = Adapter\Filesystem::mount($path, $this->capabilities)
            ->withCaseSensitivity(
                $this->config->filesystemCaseSensitivity(),
            );
        $this->mounted[$adapter] = $path->toString();

        return $adapter;
    }

    public function contains(Path $path): bool
    {
        return $this->synchronous->contains($path);
    }

    public function require(Path $path): Maybe
    {
        return $this->synchronous->require($path);
    }

    public function watch(Path $path): Ping
    {
        return ($this->watch)($path);
    }
}
