<?php
declare(strict_types = 1);

namespace Tests\Innmind\Async\OperatingSystem;

use Innmind\Async\OperatingSystem\Factory;
use Innmind\OperatingSystem\Factory as Sync;
use Innmind\Filesystem\Name;
use Innmind\TimeContinuum\Earth\Period\Second;
use Innmind\Server\Control\Server\Command;
use Innmind\Url\Path;
use Innmind\Mantle\{
    Forerunner,
    Source\Predetermined,
};
use PHPUnit\Framework\TestCase;

class FunctionalTest extends TestCase
{
    private $factory;
    private $synchronous;

    public function setUp(): void
    {
        $this->synchronous = Sync::build();
        $this->factory = Factory::of($this->synchronous);
    }

    public function testAsyncFilesystem()
    {
        $jsonFinishedFirst = null;
        $total = 0;
        $forerunner = Forerunner::of($this->synchronous->clock());
        $forerunner(null, Predetermined::of(
            function($suspend) use (&$jsonFinishedFirst, &$total) {
                $this
                    ->factory
                    ->build($suspend)
                    ->filesystem()
                    ->mount(Path::of('./'))
                    ->get(Name::of('LICENSE'))
                    ->match(
                        static fn($file) => $file,
                        static fn() => throw new \LogicException('file not found'),
                    )
                    ->content()
                    ->lines()
                    ->foreach(static function() use (&$total) {
                        ++$total;
                    });
                $this->assertTrue($jsonFinishedFirst);
            },
            function($suspend) use (&$jsonFinishedFirst, &$total) {
                $this
                    ->factory
                    ->build($suspend)
                    ->filesystem()
                    ->mount(Path::of('./'))
                    ->get(Name::of('phpunit.xml.dist'))
                    ->match(
                        static fn($file) => $file,
                        static fn() => throw new \LogicException('file not found'),
                    )
                    ->content()
                    ->lines()
                    ->foreach(static function() use (&$total) {
                        ++$total;
                    });
                $jsonFinishedFirst = true;
            },
        ));

        $this->assertSame(40, $total);
    }

    public function testAsyncHalt()
    {
        $secondFinishedFirst = null;
        $forerunner = Forerunner::of($this->synchronous->clock());
        $forerunner(null, Predetermined::of(
            function($suspend) use (&$secondFinishedFirst) {
                $this
                    ->factory
                    ->build($suspend)
                    ->process()
                    ->halt(new Second(2));
                $this->assertTrue($secondFinishedFirst);
            },
            function($suspend) use (&$secondFinishedFirst) {
                $this
                    ->factory
                    ->build($suspend)
                    ->process()
                    ->halt(new Second(1));
                $secondFinishedFirst = true;
            },
        ));
    }

    public function testAsyncServerControl()
    {
        $secondFinishedFirst = null;
        $forerunner = Forerunner::of($this->synchronous->clock());
        $forerunner(null, Predetermined::of(
            function($suspend) use (&$secondFinishedFirst) {
                $this
                    ->factory
                    ->build($suspend)
                    ->control()
                    ->processes()
                    ->execute(Command::foreground('sleep 2'))
                    ->wait();
                $this->assertTrue($secondFinishedFirst);
            },
            function($suspend) use (&$secondFinishedFirst) {
                $this
                    ->factory
                    ->build($suspend)
                    ->control()
                    ->processes()
                    ->execute(Command::foreground('sleep 1'))
                    ->wait();
                $secondFinishedFirst = true;
            },
        ));
    }
}
