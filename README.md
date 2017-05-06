# Flysystem adapter with a fallback to a readonly adapter

[![Author](http://img.shields.io/badge/author-@Grummfy-blue.svg?style=flat-square)](https://twitter.com/Grummfy)
[![Build Status](https://img.shields.io/travis/BePark/flysystem-fallback-to-read-only-adapter/master.svg?style=flat-square)](https://travis-ci.org/BePark/flysystem-fallback-to-read-only-adapter)
[![Coverage Status](https://img.shields.io/scrutinizer/coverage/g/BePark/flysystem-fallback-to-read-only-adapter.svg?style=flat-square)](https://scrutinizer-ci.com/g/BePark/flysystem-fallback-to-read-only-adapter/code-structure)
[![Coverage Status](https://coveralls.io/repos/github/BePark/flysystem-fallback-to-read-only-adapter/badge.svg?branch=master)](https://coveralls.io/github/BePark/flysystem-fallback-to-read-only-adapter?branch=master)
[![Quality Score](https://img.shields.io/scrutinizer/g/BePark/flysystem-fallback-to-read-only-adapter.svg?style=flat-square)](https://scrutinizer-ci.com/g/BePark/flysystem-fallback-to-read-only-adapter)
[![Software License](https://img.shields.io/badge/license-MIT-brightgreen.svg?style=flat-square)](LICENSE)
[![Packagist Version](https://img.shields.io/packagist/v/BePark/flysystem-fallback-to-read-only-adapter.svg?style=flat-square)](https://packagist.org/packages/BePark/flysystem-fallback-to-read-only-adapter)
[![Total Downloads](https://img.shields.io/packagist/dt/BePark/flysystem-fallback-to-read-only-adapter.svg?style=flat-square)](https://packagist.org/packages/BePark/flysystem-fallback-to-read-only-adapter)

## Description

Imagine that you need to read files from a system but when you change/delete it
you need to assure that everything is still untouch on a first system and that the new version is available.

We develop it to allow us to easier manage some of non private data from development environment with prod data.

On the read only adapter you can:
* read
* list

On the write only adapter you can do whatever you want, as the limits is in relation with the given adapter itself.

So this adpater will not magical convert a normal adapter to read only. It's just blocking the usage of write or delete.

So when you have two adapters (A & B (the read only one)), the reading will be first made from the first one (A),
if it is not found fallback to the second one (B). On write, it will first duplicate the data if not exist on the first one (A)
and then write it (on A but nothing will change on B).

### Strange behaviour

Some strange behaviour can appears ;) If you remove a file that exist only on the read only adapter, it will still be readable by
the adapter.

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

## Inspiration & similar adapters

* [Flysystem replica adapter](https://github.com/Litipk/flysystem-fallback-adapter)
* [Flysystem fallback adapter](https://github.com/thephpleague/flysystem-replicate-adapter)

## Run the test

```bash
composer install
vendor/bin/atoum
```
