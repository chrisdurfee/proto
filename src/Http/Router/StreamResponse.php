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

		// Disable all output buffering before sending headers
		while (ob_get_level())
		{
			ob_end_flush();
		}

		// Send status line
		header("HTTP/2.0 {$code} {$message}");

		// Send SSE-specific headers (no charset for text/event-stream)
		// Force explicit Content-Type without charset
		header("Content-Type: {$contentType}", true);
		header('Cache-Control: no-cache, no-store, must-revalidate');
		header('Pragma: no-cache');
		header('Expires: 0');
		header('Connection: keep-alive');
		header('X-Accel-Buffering: no'); // For Nginx, prevents buffering.

		// PHP-FPM and Apache specific headers to prevent buffering
		header('X-Content-Type-Options: nosniff');
		header('Content-Encoding: identity'); // Prevent gzip compression

		// Force immediate header sending
		if (function_exists('fastcgi_finish_request'))
		{
			// This doesn't finish the request, just flushes headers in FPM
			flush();
		}

		// Disable implicit output buffering
		ini_set('output_buffering', 'off');
		ini_set('zlib.output_compression', 'off');

		// Send initial SSE comment to establish connection
		echo ": SSE Connection Established\n\n";
		$this->flush();

		return $this;
	}

	/**
	 * Flushes the output buffer to send data to the client.
	 *
	 * @return self
	 */
	public function flush(): self
	{
		// Only flush if output buffering is active
		$levels = ob_get_level();
		if ($levels > 0)
		{
			// Flush each buffer level with safety limit
			$maxLevels = min($levels, 10); // Prevent infinite loops
			for ($i = 0; $i < $maxLevels; $i++)
			{
				@ob_flush();
			}
		}

		// System flush - always safe to call
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

		// Split multi-line data into multiple data: lines (SSE spec requirement)
		$lines = explode("\n", $data);
		foreach ($lines as $line)
		{
			echo "data: {$line}\n";
		}

		// Send the required empty line to complete the event
		echo "\n";

		// Aggressive flushing
		$this->flush();

		return $this;
	}
}