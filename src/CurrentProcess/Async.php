<?php
declare(strict_types = 1);

namespace Innmind\Async\OperatingSystem\CurrentProcess;

use Innmind\Async\OperatingSystem\Exception\LogicException;
use Innmind\OperatingSystem\{
    CurrentProcess,
    CurrentProcess\Children,
    CurrentProcess\Signals,
};
use Innmind\Async\TimeWarp\Halt;
use Innmind\TimeContinuum\Period;
use Innmind\Server\Control\Server\Process\Pid;
use Innmind\Server\Status\Server\Memory\Bytes;
use Innmind\Immutable\Either;

final class Async implements CurrentProcess
{
    private CurrentProcess $synchronous;
    private Halt $halt;

    private function __construct(CurrentProcess $synchronous, Halt $halt)
    {
        $this->synchronous = $synchronous;
        $this->halt = $halt;
    }

    /**
     * @internal
     */
    public static function of(
        CurrentProcess $synchronous,
        Halt $halt,
    ): self {
        return new self($synchronous, $halt);
    }

    public function id(): Pid
    {
        return $this->synchronous->id();
    }

    public function fork(): Either
    {
        throw new LogicException('Async forks are not supported for now');
    }

    public function children(): Children
    {
        throw new LogicException('Async forks are not supported for now');
    }

    public function signals(): Signals
    {
        throw new LogicException('Async signal handling is not supported for now');
    }

    public function halt(Period $period): void
    {
        ($this->halt)($period);
    }

    public function memory(): Bytes
    {
        return $this->synchronous->memory();
    }
}
