<?php declare(strict_types=1);
namespace Proto\Html;

/**
 * Buffer
 *
 * This will create a buffer.
 *
 * @package Proto\Html
 */
class Buffer
{
	/**
	 * This will start the buffer.
	 *
	 * @return void
	 */
    public function start(): void
	{
		ob_start();
	}

	/**
	 * This will end the buffer and return the contents.
	 *
	 * @return string
	 */
	public function getContentsAndEnd(): string
	{
		return ob_get_clean();
	}

	/**
	 * This will get the buffer contents.
	 *
	 * @return string
	 */
	public function getContents(): string
	{
		return ob_get_contents();
	}

	/**
	 * This will stop the buffer.
	 *
	 * @return void
	 */
	public function stop(): void
	{
		ob_end_clean();
	}
}
