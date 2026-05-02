<?php declare(strict_types=1);
namespace Proto\Http\ServerEvents;

use Proto\Http\Router\StreamResponse;
use Proto\Http\Session\SessionInterface;

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
	 * SSE configuration (max duration, heartbeat interval, etc.).
	 *
	 * @var SseConfig
	 */
	protected SseConfig $sseConfig;

	/**
	 * Wall-clock start time of the stream.
	 *
	 * @var float
	 */
	protected float $startedAt = 0.0;

	/**
	 * Whether a shutdown function has been registered for this stream.
	 *
	 * @var bool
	 */
	protected bool $shutdownRegistered = false;

	/**
	 * Configure PHP settings for optimal SSE streaming.
	 *
	 * Critically, this no longer calls `set_time_limit(0)` — instead it
	 * caps the script at `maxDuration + shutdownGrace` so PHP can always
	 * recycle a worker as a last-resort safety net.
	 *
	 * @return void
	 */
	protected function configureStreaming(): void
	{
		ini_set('output_buffering', 'off');
		ini_set('zlib.output_compression', 'off');
		ini_set('implicit_flush', '1');

		// Bounded time limit: if everything else fails, PHP will still kill
		// the script and FPM can reclaim the worker. Apps that genuinely
		// need unbounded streams can set sse.maxDuration to a very large
		// number, but should be aware of the FPM saturation risk.
		set_time_limit($this->sseConfig->scriptTimeLimit());

		// We still ignore user abort because connection_aborted() is
		// unreliable behind nginx/Vite proxies. The bounded duration above
		// + write-failure detection in StreamWriter give us reliable
		// termination without depending on it.
		ignore_user_abort(true);

		while (ob_get_level() > 0)
		{
			ob_end_flush();
		}

		if (function_exists('apache_setenv'))
		{
			apache_setenv('no-gzip', '1');
		}

		$this->startedAt = microtime(true);
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

		$this->response = new StreamResponse();
		$this->response->sendHeaders(200);
	}

	/**
	 * True once the stream has exceeded its configured maxDuration.
	 *
	 * @return bool
	 */
	protected function isOverDeadline(): bool
	{
		if ($this->startedAt <= 0.0)
		{
			return false;
		}

		return (microtime(true) - $this->startedAt) >= $this->sseConfig->maxDuration;
	}

	/**
	 * Number of seconds remaining before the stream's deadline. Returns
	 * 0 when the deadline has passed.
	 *
	 * @return int
	 */
	protected function secondsRemaining(): int
	{
		if ($this->startedAt <= 0.0)
		{
			return $this->sseConfig->maxDuration;
		}

		$remaining = $this->sseConfig->maxDuration - (int)floor(microtime(true) - $this->startedAt);
		return max(0, $remaining);
	}

	/**
	 * Registers a shutdown function once for this stream. The callback is
	 * invoked even on fatal errors and `request_terminate_timeout` kills
	 * (which `__destruct` does NOT survive).
	 *
	 * @param callable $callback
	 * @return void
	 */
	protected function registerShutdownHandler(callable $callback): void
	{
		if ($this->shutdownRegistered)
		{
			return;
		}

		register_shutdown_function($callback);
		$this->shutdownRegistered = true;
	}
}
