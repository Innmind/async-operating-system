<?php
declare(strict_types = 1);

namespace Tests\Innmind\Async\OperatingSystem;

use Innmind\Async\OperatingSystem\Factory;
use Innmind\OperatingSystem\Factory as Sync;
use Innmind\Filesystem\Name;
use Innmind\TimeContinuum\Earth\Period\Second;
use Innmind\Server\Control\Server\Command;
use Innmind\Url\{
    Url,
    Path,
};
use Innmind\Http\{
    Message\Request\Request,
    Message\Method,
    ProtocolVersion,
};
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

    public function testAsyncHttpCall()
    {
        $queue = new \SplQueue;
        $forerunner = Forerunner::of($this->synchronous->clock());
        $forerunner(null, Predetermined::of(
            function($suspend) use ($queue) {
                $queue->push('first started');

                $this
                    ->factory
                    ->build($suspend)
                    ->remote()
                    ->http()(new Request(
                        Url::of('https://github.com'),
                        Method::get,
                        ProtocolVersion::v11,
                    ))
                    ->match(
                        static fn() => null,
                        static fn() => null,
                    );

                $queue->push('first finished');
            },
            function($suspend) use ($queue) {
                $queue->push('second started');

                $this
                    ->factory
                    ->build($suspend)
                    ->remote()
                    ->http()(new Request(
                        Url::of('https://github.com'),
                        Method::get,
                        ProtocolVersion::v11,
                    ))
                    ->match(
                        static fn() => null,
                        static fn() => null,
                    );

                $queue->push('second finished');
            },
        ));

        $this->assertCount(4, $queue);
        $this->assertSame('first started', $queue[0]);
        $this->assertSame('second started', $queue[1]);
        // the order it finished can't be determined as it depends on the speed
        // of the http call
        $this->assertContains('first finished', $queue);
        $this->assertContains('second finished', $queue);
    }
}
