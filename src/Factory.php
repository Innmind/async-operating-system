<?php
declare(strict_types = 1);

namespace Innmind\Async\OperatingSystem;

use Innmind\OperatingSystem\{
    OperatingSystem,
    Config,
};
use Innmind\Mantle\Suspend;

final class Factory
{
    private OperatingSystem $synchronous;
    private ?Config $config;

    private function __construct(OperatingSystem $synchronous, ?Config $config)
    {
        $this->synchronous = $synchronous;
        $this->config = $config;
    }

    public static function of(OperatingSystem $synchronous, Config $config = null): self
    {
        return new self($synchronous, $config);
    }

    public function build(Suspend $suspend): OperatingSystem
    {
        return Async::of(
            $this->synchronous,
            $suspend,
            $this->config,
        );
    }
}
