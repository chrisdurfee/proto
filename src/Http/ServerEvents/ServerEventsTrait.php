<?php declare(strict_types=1);
namespace Proto\Http\ServerEvents;

use Proto\Http\Router\StreamResponse;
use Proto\Http\Session\SessionInterface;
use Proto\Utils\Filter\Input;

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
	 * The session instance.
	 *
	 * @var SessionInterface|null
	 */
	protected ?SessionInterface $session = null;

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

		// Enable detection of client disconnection.
		// When false, PHP will abort the script when the client disconnects.
		ignore_user_abort(false);

		if (function_exists('apache_setenv'))
		{
			apache_setenv('no-gzip', '1');
		}
	}

	/**
	 * Initializes the session for SSE using Proto's session system.
	 * The session is automatically opened and closed by Proto's session layer
	 * to prevent locking other requests.
	 *
	 * @return void
	 */
	protected function initializeSession(): void
	{
		// Proto's session system automatically handles open/close to prevent locking
		$this->session = session();
	}

	/**
	 * Sets up the StreamResponse instance.
	 * Note: CORS headers are already handled by the Router's Headers class.
	 *
	 * @return void
	 */
	protected function setupResponse(): void
	{
		$this->initializeSession();

		// Now create response and send SSE headers
		$this->response = new StreamResponse();
		$this->response->sendHeaders(200);
	}
}
