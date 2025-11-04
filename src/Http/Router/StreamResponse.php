<?php declare(strict_types=1);
namespace Proto\Http\Router;

/**
 * StreamResponse
 *
 * Represents a Server-Sent Events (SSE) stream response.
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

		// Send status line
		header("HTTP/2.0 {$code} {$message}");

		// Send SSE-specific headers (no charset for text/event-stream)
		// Force explicit Content-Type without charset
		header("Content-Type: {$contentType}", true);
		header('Cache-Control: no-cache');
		header('Connection: keep-alive');
		header('X-Accel-Buffering: no'); // For Nginx, prevents buffering.

		// Disable output buffering for real-time streaming.
		while (@ob_end_flush());
		return $this;
	}

	/**
	 * Flushes the output buffer to send data to the client.
	 *
	 * @return self
	 */
	public function flush(): self
	{
		if (function_exists('ob_flush'))
		{
			@ob_flush();
		}

		flush();
		return $this;
	}

	/**
	 * Sends an event to the SSE client.
	 *
	 * @param string $data
	 * @param string|null $event
	 * @return self
	 */
	public function sendEvent(string $data, ?string $event = null): self
	{
		if ($event !== null)
		{
			echo "event: {$event}\n";
		}

		echo "data: {$data}\n\n";
		$this->flush();
		return $this;
	}
}