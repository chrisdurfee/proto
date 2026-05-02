<?php declare(strict_types=1);
namespace Proto\Http\Router;

use Proto\Http\ServerEvents\StreamWriter;

/**
 * StreamResponse
 *
 * Represents a Server-Sent Events (SSE) stream response.
 *
 * All client output goes through `Proto\Http\ServerEvents\StreamWriter`,
 * which writes via `fwrite(php://output, ...)` instead of `echo` so
 * broken pipes are surfaced as boolean failures (and SSE streams can
 * exit instead of looping forever into a dead socket).
 *
 * @package Proto\Http\Router
 */
class StreamResponse extends Response
{
	/**
	 * Constructor.
	 *
	 * @param string $contentType Default response content type.
	 */
	public function __construct(string $contentType = 'text/event-stream')
	{
		parent::__construct($contentType);
	}

	/**
	 * Sends headers for the stream response.
	 *
	 * @param int $code
	 * @param string|null $contentType
	 * @return self
	 */
	public function sendHeaders(int $code, ?string $contentType = null): self
	{
		$contentType = $contentType ?? $this->contentType;
		$message = parent::getResponseMessage($code);

		while (ob_get_level())
		{
			ob_end_flush();
		}

		header("HTTP/2.0 {$code} {$message}");

		header("Content-Type: {$contentType}", true);
		header('Cache-Control: no-cache, no-store, must-revalidate');
		header('Pragma: no-cache');
		header('Expires: 0');
		header('Connection: keep-alive');
		header('X-Accel-Buffering: no');

		header('X-Content-Type-Options: nosniff');
		header('Content-Encoding: identity');

		if (function_exists('fastcgi_finish_request'))
		{
			flush();
		}

		ini_set('output_buffering', 'off');
		ini_set('zlib.output_compression', 'off');

		StreamWriter::writeAndFlush(": SSE Connection Established\n\n");

		return $this;
	}

	/**
	 * Flushes the output buffer to send data to the client.
	 *
	 * @return self
	 */
	public function flush(): self
	{
		StreamWriter::flush();
		return $this;
	}

	/**
	 * True if the most recent client write succeeded and the connection
	 * still appears healthy.
	 *
	 * @return bool
	 */
	public function isAlive(): bool
	{
		return StreamWriter::isAlive();
	}

	/**
	 * Sends an event to the SSE client.
	 *
	 * Maintains the original fluent API for backward compatibility. Use
	 * `writeEvent()` instead when callers need to know whether the write
	 * actually succeeded.
	 *
	 * @param string $data
	 * @param string|null $event
	 * @return self
	 */
	public function sendEvent(string $data, ?string $event = null): self
	{
		$this->writeEvent($data, $event);
		return $this;
	}

	/**
	 * Sends an event and reports success/failure. Returns false when the
	 * client has disconnected or the underlying socket write fails.
	 *
	 * @param string $data
	 * @param string|null $event
	 * @return bool
	 */
	public function writeEvent(string $data, ?string $event = null): bool
	{
		$payload = '';

		if ($event !== null)
		{
			$payload .= "event: {$event}\n";
		}

		$lines = explode("\n", $data);
		foreach ($lines as $line)
		{
			$payload .= "data: {$line}\n";
		}

		$payload .= "\n";

		return StreamWriter::writeAndFlush($payload);
	}
}
