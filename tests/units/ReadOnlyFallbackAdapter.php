<?php

namespace tests\units\BePark\Flysystem\ReadOnlyFallback;

use League\Flysystem\AdapterInterface;
use League\Flysystem\Config;

class ReadOnlyFallbackAdapter extends \atoum\test
{
	public function testAdapters(AdapterInterface $mainAdapter, AdapterInterface $readOnlyAdapter)
	{
		$this
			->given($this->newTestedInstance($mainAdapter, $readOnlyAdapter))
			->then
				->object($this->testedInstance->getMainAdapter())->isIdenticalTo($mainAdapter)
				->object($this->testedInstance->getReadOnlyAdapter())->isIdenticalTo($readOnlyAdapter);
	}

	public function testHasPath(AdapterInterface $mainAdapter, AdapterInterface $readOnlyAdapter)
	{
		$this
			->assert('test path existence on readOnly')
			->given(
				$this->newTestedInstance($mainAdapter, $readOnlyAdapter),
				$this->calling($readOnlyAdapter)->has = true,
				$this->calling($mainAdapter)->has = false
			)
			->then
				->boolean($this->testedInstance->has('/foo'))->isTrue
				->mock($readOnlyAdapter)->receive('has')->withIdenticalArguments('/foo')->once
				->mock($mainAdapter)->receive('has')->withIdenticalArguments('/foo')->once;

		$this
			->assert('test path existence on mainAdapter')
			->given($this->calling($mainAdapter)->has = true,
					$this->calling($readOnlyAdapter)->has = false)
			->then
				->boolean($this->testedInstance->has('/foo'))->isTrue
				->mock($mainAdapter)->receive('has')->withIdenticalArguments('/foo')->once
				->mock($readOnlyAdapter)->call('has')->never;


		$this
			->assert('test none existing path')
			->given($this->calling($mainAdapter)->has = false,
					$this->calling($readOnlyAdapter)->has = false)
			->then
				->boolean($this->testedInstance->has('/foo'))->isFalse
				->mock($mainAdapter)->receive('has')->withIdenticalArguments('/foo')->once
				->mock($readOnlyAdapter)->receive('has')->withIdenticalArguments('/foo')->once;
	}

	public function testWriteAndWriteStream(AdapterInterface $mainAdapter, AdapterInterface $readOnlyAdapter)
	{
		$this->calling($mainAdapter)->write = true;
		$this->calling($readOnlyAdapter)->write = false;

		$this->calling($mainAdapter)->writeStream = true;
		$this->calling($readOnlyAdapter)->writeStream = false;

		$this
			->assert('test write')
			->given($this->newTestedInstance($mainAdapter, $readOnlyAdapter))
			->then
			->boolean($this->testedInstance->write('/foo', 'bar', new Config()))->isTrue
				->mock($readOnlyAdapter)->wasNotCalled
				->mock($mainAdapter)->receive('write')->withAtLeastArguments(['/foo', 'bar'])->once;

		$this
			->assert('test write stream')
			->given($this->newTestedInstance($mainAdapter, $readOnlyAdapter))
			->then
			->boolean($this->testedInstance->writeStream('/foo', 'bar', new Config()))->isTrue
				->mock($readOnlyAdapter)->wasNotCalled
				->mock($mainAdapter)->receive('writeStream')->withAtLeastArguments(['/foo', 'bar'])->once;
	}

	public function testUpdate(AdapterInterface $mainAdapter, AdapterInterface $readOnlyAdapter)
	{
		$this
			->assert('test update only on main if exist on main')
			->given(
				$this->newTestedInstance($mainAdapter, $readOnlyAdapter),
				$this->calling($mainAdapter)->has = true,
				$this->calling($readOnlyAdapter)->has = true,
				$this->calling($mainAdapter)->update = true
			)
			->then
				->boolean($this->testedInstance->update('/foo', 'bar', new Config()))->isTrue
				->mock($readOnlyAdapter)->wasNotCalled
				->mock($mainAdapter)->receive('update')->withAtLeastArguments(['/foo', 'bar'])->once;

		$this
			->assert('test update only on main but not exist on main')
			->given(
				$this->calling($mainAdapter)->has = false,
				$this->calling($readOnlyAdapter)->has = true,
				$this->calling($mainAdapter)->update = true
			)
			->then
			->boolean($this->testedInstance->update('/foo', 'bar', new Config()))->isTrue
			->mock($readOnlyAdapter)->call('update')->never
			->mock($mainAdapter)->receive('update')->withAtLeastArguments(['/foo', 'bar'])->once;
	}

	public function testDelete(AdapterInterface $mainAdapter, AdapterInterface $readOnlyAdapter)
	{
		$this->newTestedInstance($mainAdapter, $readOnlyAdapter);

		$this->assert('test delete on non existing on main')
			->given(
				$this->calling($mainAdapter)->has = false,
				$this->calling($readOnlyAdapter)->has = true,
				$this->calling($mainAdapter)->delete = true
			)
			->boolean($this->testedInstance->delete('foo/bar'))->isTrue
			->mock($mainAdapter)->receive('delete')->never
			->mock($readOnlyAdapter)->receive('delete')->never;

		$this->assert('test delete on existing on main')
			->given(
				$this->calling($mainAdapter)->has = true,
				$this->calling($mainAdapter)->delete = true
			)
			->boolean($this->testedInstance->delete('foo/bar'))->isTrue
			->mock($mainAdapter)->receive('delete')->withIdenticalArguments('foo/bar')->once;
	}

	public function testRead(AdapterInterface $mainAdapter, AdapterInterface $readOnlyAdapter)
	{
		$this->newTestedInstance($mainAdapter, $readOnlyAdapter);
		$this->assert('test read on existing on main')
			->given(
				$this->calling($readOnlyAdapter)->has = true,
				$this->calling($mainAdapter)->has = true,
				$this->calling($mainAdapter)->read = 'foo'
			)
			->string($this->testedInstance->read('foo/bar'))->isEqualTo('foo')
			->mock($readOnlyAdapter)->wasNotCalled;

		$this->assert('test read on existing only on read only')
			->given(
				$this->calling($readOnlyAdapter)->has = true,
				$this->calling($mainAdapter)->has = false,
				$this->calling($readOnlyAdapter)->read = 'baz',
				$this->calling($mainAdapter)->read = false
			)
			->string($this->testedInstance->read('foo/bar'))->isEqualTo('baz');
	}

	public function testGetMetadata(AdapterInterface $mainAdapter, AdapterInterface $readOnlyAdapter)
	{
		$this->given(
			$this->newTestedInstance($mainAdapter, $readOnlyAdapter),
			$this->calling($mainAdapter)->has = true,
			$this->calling($mainAdapter)->getMetadata = array('metadata')
		)
		->then
			->array($this->testedInstance->getMetadata('foo'))->isEqualTo(['metadata'])
			->mock($readOnlyAdapter)->wasNotCalled
			->mock($mainAdapter)->receive('getMetadata')->once;
	}

	public function testListContent(AdapterInterface $mainAdapter, AdapterInterface $readOnlyAdapter)
	{
		$a = [
			[
				'type' => 'file',
				'path' => 'foo',
				'size' => 0,
				'timestamp' => time(),
			],
			[
				'type' => 'file',
				'path' => 'bar',
				'size' => 100,
				'timestamp' => time() - 100,
			],
			[
				'type' => 'dir',
				'path' => 'baz',
				'size' => 0,
				'timestamp' => time() - 200,
			],
	    ];

		$b = [
			[
				'type' => 'file',
				'path' => 'foo',
				'size' => 10,
				'timestamp' => time(),
			],
			[
				'type' => 'file',
				'path' => 'baz',
				'size' => 150,
				'timestamp' => time() - 150,
			]
		];

		$this->given(
			$this->newTestedInstance($mainAdapter, $readOnlyAdapter),
			$this->calling($mainAdapter)->listContents = $a,
			$this->calling($readOnlyAdapter)->listContents = $a
		)
		->then
			->array($this->testedInstance->listContents())
				->isIdenticalTo($a + $b);
	}
}
