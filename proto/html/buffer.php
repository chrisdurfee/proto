<?php declare(strict_types=1);
namespace Proto\Html;

/**
 * Class Buffer
 *
 * Handles output buffering operations.
 *
 * @package Proto\Html
 */
class Buffer
{
	/**
	 * Starts output buffering.
	 *
	 * @return void
	 */
	public function start(): void
	{
		if (!ob_start())
		{
			throw new \RuntimeException('Failed to start output buffering.');
		}
	}

	/**
	 * Returns the current buffer contents and ends the buffer.
	 *
	 * @return string The buffered output.
	 */
	public function getContentsAndEnd(): string
	{
		return ob_get_level() > 0 ? ob_get_clean() : '';
	}

	/**
	 * Returns the current buffer contents without ending the buffer.
	 *
	 * @return string The buffered output.
	 */
	public function getContents(): string
	{
		return ob_get_level() > 0 ? ob_get_contents() ?: '' : '';
	}

	/**
	 * Stops output buffering and discards the contents.
	 *
	 * @return void
	 */
	public function stop(): void
	{
		if (ob_get_level() > 0)
		{
			ob_end_clean();
		}
	}
}