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
	 * @var string $contentType
	 */
	protected string $contentType = 'text/event-stream';

	/**
	 * Sets the headers for the stream response.
	 *
	 * @param int $code
	 * @param string|null $contentType
	 * @return self
	 */
	public function headers(int $code, string $contentType = null): self
	{
		$contentType = $contentType ?? $this->contentType;

		$message = $this->getResponseMessage($code);

		header("HTTP/2.0 {$code} {$message}");
		header("Content-Type: {$contentType}");
		header('Cache-Control: no-cache');

		return $this;
	}

	/**
	 * Flushes the output buffer to send data to the client.
	 *
	 * @return self
	 */
	public function flush(): self
	{
		flush();

		return $this;
	}
}