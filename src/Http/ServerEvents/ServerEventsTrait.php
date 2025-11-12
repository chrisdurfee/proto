<?php declare(strict_types=1);
namespace Proto\Http\ServerEvents;

use Proto\Http\Router\StreamResponse;

/**
 * ServerEventsTrait
 *
 * Shared functionality for SSE implementations.
 *
 * @package Proto\Http\ServerEvents
 */
trait ServerEventsTrait
{
	/**
	 * The StreamResponse instance.
	 *
	 * @var StreamResponse
	 */
	protected StreamResponse $response;

	/**
	 * Configure PHP settings for optimal SSE streaming.
	 *
	 * @return void
	 */
	protected function configureStreaming(): void
	{
		ini_set('output_buffering', 'off');
		ini_set('zlib.output_compression', 'off');
		ini_set('implicit_flush', '1');
		set_time_limit(0);

		if (function_exists('apache_setenv'))
		{
			apache_setenv('no-gzip', '1');
		}
	}

	/**
	 * Sets up the StreamResponse instance.
	 *
	 * @return void
	 */
	protected function setupResponse(): void
	{
		$this->response = new StreamResponse();
		$this->response->sendHeaders(200);
	}
}
