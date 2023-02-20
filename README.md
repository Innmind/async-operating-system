# Async Operating System

[![Build Status](https://github.com/innmind/async-operating-system/workflows/CI/badge.svg?branch=main)](https://github.com/innmind/async-operating-system/actions?query=workflow%3ACI)
[![codecov](https://codecov.io/gh/innmind/async-operating-system/branch/develop/graph/badge.svg)](https://codecov.io/gh/innmind/async-operating-system)
[![Type Coverage](https://shepherd.dev/github/innmind/async-operating-system/coverage.svg)](https://shepherd.dev/github/innmind/async-operating-system)

Async implementation of [`innmind/operating-system`](https://packagist.org/packages/innmind/operating-system) to allow to switch to another task when doing any I/O of suspending the current process.

**Warning**: the following features are disabled in this async context :
- Process forking
- Handling signals

**Note**: SQL connections are not async yet.

## Installation

```sh
composer require innmind/async-operating-system
```

## Usage

```php
use Innmind\Async\OperatingSystem\Factory;
use Innmind\OperatingSystem\Factory as Synchronous;
use Innmind\Filesystem\Name;
use Innmind\Http\{
    Message\Request\Request,
    Message\Method,
    ProtocolVersion,
};
use Innmind\Url\{
    Url,
    Path,
};
use Innmind\Mantle\{
    Source\Predetermined,
    Suspend,
    Forerunner,
};

$synchronous = Synchronous::build();
$factory = Factory::of($synchronous);
$source = Predetermined::of(
    static fn(Suspend $suspend) => $factory
        ->build($suspend)
        ->filesystem()
        ->mount(Path::of('somewhere/'))
        ->get(Name::of('some-file'))
        ->match(
            static fn($file) => doYourStuff($file),
            static fn() => null,
        ),
    static fn(Suspend $suspend) => $factory
        ->build($suspend)
        ->remote()
        ->http()(new Request(
            Url::of('https://wikipedia.org')
            Method::get,
            ProtocolVersion::v11,
        ))
        ->match(
            static fn($success) => doYourStuff($success),
            static fn() => null,
        );
);

Forerunner::of($synchronous->clock())(null, $source);
```

In this example we load a file and call wikipedia asynchronously, but you can use the `OperatingSystem` returned by `$factory->build($suspend)` like you would for its synchronous counterpart.
