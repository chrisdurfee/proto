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
	 * Content type of the stream response.
	 *
	 * @var string
	 */
	protected string $contentType = 'text/event-stream';

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

		// Use parent method to ensure consistency.
		parent::sendHeaders($code, $contentType);

		// Additional headers for SSE.
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