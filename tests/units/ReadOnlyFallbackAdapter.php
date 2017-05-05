<?php

namespace tests\units\BePark\Flysystem\ReadOnlyFallback;

use League\Flysystem\Config;

class ReadOnlyFallbackAdapter extends \atoum\test
{
	public function testAdapters()
	{
		$readOnlyAdapter = $this->newMockInstance('\League\Flysystem\AdapterInterface');
		$mainAdapter = $this->newMockInstance('\League\Flysystem\AdapterInterface');

		$this
			->given($this->newTestedInstance($mainAdapter, $readOnlyAdapter))
			->then
				->object($this->testedInstance->getMainAdapter())->isIdenticalTo($mainAdapter)
				->object($this->testedInstance->getReadOnlyAdapter())->isIdenticalTo($readOnlyAdapter);
	}

	public function testHasPath()
	{
		$readOnlyAdapter = $this->newMockInstance('\League\Flysystem\AdapterInterface');
		$mainAdapter = $this->newMockInstance('\League\Flysystem\AdapterInterface');

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

	public function testWrite()
	{
		$readOnlyAdapter = $this->newMockInstance('\League\Flysystem\AdapterInterface');
		$mainAdapter = $this->newMockInstance('\League\Flysystem\AdapterInterface');
		$this->calling($mainAdapter)->write = true;
		$this->calling($readOnlyAdapter)->write = true;

		$this
			->given($this->newTestedInstance($mainAdapter, $readOnlyAdapter))
			->then
				->boolean($this->testedInstance->write('/foo', 'bar', new Config()))->isTrue
					->mock($readOnlyAdapter)->wasNotCalled
					->mock($mainAdapter)->receive('write')->withAtLeastArguments(['/foo', 'bar'])->once;
	}
}
