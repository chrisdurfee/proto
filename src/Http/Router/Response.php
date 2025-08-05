<?php declare(strict_types=1);
namespace Proto\Http\Router;

use Proto\Utils\Format\JsonFormat as Formatter;

/**
 * Response
 *
 * Represents an HTTP router response.
 *
 * @package Proto\Http\Router
 */
class Response
{
	/**
	 * HTTP response codes and their messages.
	 *
	 * @var array<int, string>
	 */
	protected static array $responseCodes = [
		200 => 'OK',
		201 => 'Created',
		202 => 'Accepted',
		203 => 'Non-Authoritative Information',
		204 => 'No Content',
		205 => 'Reset Content',
		300 => 'Multiple Choice',
		301 => 'Moved Permanently',
		302 => 'Found',
		303 => 'See Other',
		304 => 'Not Modified',
		400 => 'Bad Request',
		401 => 'Unauthorized',
		403 => 'HTTPS Required',
		404 => 'Not Found',
		405 => 'Method Not Allowed',
		409 => 'Conflict',
		429 => 'Too Many Requests',
		500 => 'Internal Server Error'
	];

	/**
	 * Constructor.
	 *
	 * @param string $contentType Default response content type.
	 */
	public function __construct(protected string $contentType = 'application/json')
	{
	}

	/**
	 * Sets the content type for the response.
	 *
	 * @param string $contentType
	 * @return self
	 */
	public function setContentType(string $contentType): self
	{
		$this->contentType = $contentType;
		return $this;
	}

	/**
	 * Gets the response message for a given response code.
	 *
	 * @param int $code
	 * @return string
	 */
	protected function getResponseMessage(int $code): string
	{
		return self::$responseCodes[$code] ?? 'Unknown Status';
	}

	/**
	 * Sends HTTP headers for the response.
	 *
	 * @param int $code
	 * @param string|null $contentType
	 * @return self
	 */
	public function sendHeaders(int $code, string $contentType = null): self
	{
		$contentType = $contentType ?? $this->contentType;
		$message = $this->getResponseMessage($code);

		header("HTTP/2.0 {$code} {$message}");
		header("Content-Type: {$contentType}; charset=utf-8");

		return $this;
	}

	/**
	 * Renders the response headers.
	 *
	 * @param int $code
	 * @param string|null $contentType
	 * @return self
	 */
	public function render(int $code, string $contentType = null): self
	{
		return $this->sendHeaders($code, $contentType);
	}

	/**
	 * Sends a JSON response.
	 *
	 * @param mixed $data
	 * @param int $code
	 * @return void
	 */
	public function json(mixed $data, int $code = 200): void
	{
		$this->sendHeaders($code, 'application/json');

		if ($data !== null)
		{
			Formatter::encodeAndRender($data);
		}
	}
}