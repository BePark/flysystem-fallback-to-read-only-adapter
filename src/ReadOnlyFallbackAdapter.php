<?php

namespace BePark\Flysystem\ReadOnlyFallback;

use League\Flysystem\AdapterInterface;
use League\Flysystem\Config;
use League\Flysystem\Util;

class ReadOnlyFallbackAdapter implements AdapterInterface
{
	protected $_readOnlyAdapter;

	protected $_mainAdapter;

	public function __construct(AdapterInterface $mainAdapter, AdapterInterface $readOnlyAdapter)
	{
		$this->_mainAdapter = $mainAdapter;
		$this->_readOnlyAdapter = $readOnlyAdapter;
	}

	/**
	 * Returns the main adapter
	 *
	 * @return AdapterInterface
	 */
	public function getMainAdapter()
	{
		return $this->_mainAdapter;
	}

	/**
	 * Returns the fallback adapter
	 *
	 * @return AdapterInterface
	 */
	public function getReadOnlyAdapter()
	{
		return $this->_readOnlyAdapter;
	}

	/**
	 * {@inheritdoc}
	 */
	public function write($path, $contents, Config $config)
	{
		return $this->_mainAdapter->write($path, $contents, $config);
	}

	/**
	 * {@inheritdoc}
	 */
	public function writeStream($path, $resource, Config $config)
	{
		return $this->_mainAdapter->writeStream($path, $resource, $config);
	}

	/**
	 * {@inheritdoc}
	 */
	public function update($path, $contents, Config $config)
	{
		$this->_backportFromReadOnly($path);

		return $this->_mainAdapter->update($path, $contents, $config);
	}

	/**
	 * {@inheritdoc}
	 */
	public function updateStream($path, $resource, Config $config)
	{
		$this->_backportFromReadOnly($path);

		return $this->_mainAdapter->updateStream($path, $resource, $config);
	}

	/**
	 * {@inheritdoc}
	 */
	public function rename($path, $newpath)
	{
		// XXX we could probably make some improvements here without duplicate the source
		$this->_backportFromReadOnly($path);

		return $this->_mainAdapter->rename($path, $newpath);
	}

	/**
	 * {@inheritdoc}
	 */
	public function copy($path, $newpath)
	{
		// XXX we could probably make some improvements here without duplicate the source
		$this->_backportFromReadOnly($path);

		return $this->_mainAdapter->copy($path, $newpath);
	}

	/**
	 * {@inheritdoc}
	 */
	public function delete($path)
	{
		if ($this->_readOnlyAdapter->has($path) && !$this->_mainAdapter->has($path))
		{
			// will always be find but yeah except if we have an adapter that retains the delete information, it's impossible to
			// do something. So enjoy a weird thing
			return true;
		}

		return $this->_mainAdapter->delete($path);
	}

	/**
	 * {@inheritdoc}
	 */
	public function deleteDir($dirname)
	{
		if ($this->_readOnlyAdapter->has($dirname) && !$this->_mainAdapter->has($dirname))
		{
			return true;
		}

		return $this->_mainAdapter->deleteDir($dirname);
	}

	/**
	 * {@inheritdoc}
	 */
	public function createDir($dirname, Config $config)
	{
		return $this->_mainAdapter->createDir($dirname, $config);
	}

	/**
	 * {@inheritdoc}
	 */
	public function setVisibility($path, $visibility)
	{
		$this->_backportFromReadOnly($path);

		return $this->_mainAdapter->setVisibility($path, $visibility);
	}

	/**
	 * {@inheritdoc}
	 */
	public function has($path)
	{
		return $this->_mainAdapter->has($path) || $this->_readOnlyAdapter->has($path);
	}

	/**
	 * {@inheritdoc}
	 */
	public function read($path)
	{
		$result = $this->_mainAdapter->read($path);
		if (false !== $result)
		{
			return $result;
		}

		return $this->_readOnlyAdapter->read($path);
	}

	/**
	 * {@inheritdoc}
	 */
	public function readStream($path)
	{
		$result = $this->_mainAdapter->readStream($path);
		if (false !== $result)
		{
			return $result;
		}

		return $this->_readOnlyAdapter->readStream($path);
	}

	//TODO
	/**
	 * {@inheritdoc}
	 * @see https://github.com/Litipk/flysystem-fallback-adapter/blob/master/src/FallbackAdapter.php#L259
	 */
	public function listContents($directory = '', $recursive = false)
	{
		// taken from https://github.com/Litipk/flysystem-fallback-adapter/blob/master/src/FallbackAdapter.php#L259
		// listContents
		$tmpResult = $this->_mainAdapter->listContents($directory, $recursive);

		$inverseRef = [];
		foreach ($tmpResult as $index => $mainContent)
		{
			$inverseRef[ $mainContent[ 'path' ] ] = $index;
		}

		$fallbackContents = $this->_readOnlyAdapter->listContents($directory, $recursive);
		foreach ($fallbackContents as $fallbackContent)
		{
			if (!isset($inverseRef[ $fallbackContent[ 'path' ] ]))
			{
				$tmpResult[] = $fallbackContent;
			}
		}

		return $tmpResult;
	}

	/**
	 * {@inheritdoc}
	 */
	public function getMetadata($path)
	{
		$result = $this->_mainAdapter->getMetadata($path);
		if (false !== $result)
		{
			return $result;
		}

		return $this->_readOnlyAdapter->getMetadata($path);
	}

	/**
	 * {@inheritdoc}
	 */
	public function getSize($path)
	{
		$result = $this->_mainAdapter->getSize($path);
		if (false !== $result)
		{
			return $result;
		}

		return $this->_readOnlyAdapter->getSize($path);
	}

	/**
	 * {@inheritdoc}
	 */
	public function getMimetype($path)
	{
		$result = $this->_mainAdapter->getMimetype($path);
		if (false !== $result)
		{
			return $result;
		}

		return $this->_readOnlyAdapter->getMimetype($path);
	}

	/**
	 * {@inheritdoc}
	 */
	public function getTimestamp($path)
	{
		$result = $this->_mainAdapter->getTimestamp($path);
		if (false !== $result)
		{
			return $result;
		}

		return $this->_readOnlyAdapter->getTimestamp($path);
	}

	/**
	 * {@inheritdoc}
	 */
	public function getVisibility($path)
	{
		$result = $this->_mainAdapter->getVisibility($path);
		if (false !== $result)
		{
			return $result;
		}

		return $this->_readOnlyAdapter->getVisibility($path);
	}

	/**
	 * Make resource available for modification on the new adapter
	 * @param string $path
	 * @return bool
	 */
	protected function _backportFromReadOnly($path)
	{
		// do nothing if it exist on the main or if the read only have none
		if ($this->_mainAdapter->has($path) || !$this->_readOnlyAdapter->has($path))
		{
			return true;
		}

		// because we change something we need to be sure to have it on the main adapter before anything
		$buffer = $this->_readOnlyAdapter->readStream($path);
		if ($buffer === $buffer)
		{
			return false;
		}

		$result = $this->_mainAdapter->writeStream($path, $buffer['stream'], new Config());
		if (is_resource($buffer['stream']))
		{
			fclose($buffer['stream']);
		}

		return (false !== $result);
	}
}
