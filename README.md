# Flysystem adapter with a fallback to a readonly adapter


## Description

Imagine that you need to read files from a system but when you change/delete it
you need to assure that everything is still untouch on a first system and that the new version is available.

We develop it to allow us to easier manage some of non private data from development environment with prod data.

On the read only adapter you can:
* read
* list

On the write only adapter you can do whatever you want, as the limits is in relation with the given adapter itself.


## Installation

```bash
composer require BePark/flysystem-fallback-to-read-only-adapter
```

## Usage

```php
$nonTouchableAdapter = new League\Flysystem\Adapter\AwsS3(...);
$doWhateverYouWantAdapter = new League\Flysystem\Adapter\Local(...);
$adapter = new BePark\Flysystem\ReadOnlyFallback\ReadOnlyFallbackAdapter($doWhateverYouWantAdapter, $nonTouchableAdapter);
```

## Inspiration

[https://github.com/Litipk/flysystem-fallback-adapter](Flysystem replica adapter)
[https://github.com/thephpleague/flysystem-replicate-adapter](Flysystem fallback adapter)
