<?php declare(strict_types=1);
namespace Proto\Http\Router;

use Proto\Http\Request as HttpRequest;
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
	public function sendHeaders(int $code, ?string $contentType = null): self
	{
		if (headers_sent())
		{
			return $this;
		}

		$contentType = $contentType ?? $this->contentType;
		$this->sendStatus($code);
		header("Content-Type: {$contentType}; charset=utf-8");

		return $this;
	}

	/**
	 * Sends the status line without declaring a content type.
	 *
	 * @param int $code
	 * @return void
	 */
	protected function sendStatus(int $code): void
	{
		if (headers_sent())
		{
			return;
		}

		$message = $this->getResponseMessage($code);
		header("HTTP/2.0 {$code} {$message}");
	}

	/**
	 * Renders the response headers.
	 *
	 * @param int $code
	 * @param string|null $contentType
	 * @return self
	 */
	public function render(int $code, ?string $contentType = null): self
	{
		return $this->sendHeaders($code, $contentType);
	}

	/**
	 * Sets the cache directive for the response being built.
	 *
	 * ```php
	 * Response::cache(CacheDirective::privateFor(60));
	 * ```
	 *
	 * @param CacheDirective $directive
	 * @return void
	 */
	public static function cache(CacheDirective $directive): void
	{
		Headers::cache($directive);
	}

	/**
	 * Sends a JSON response.
	 *
	 * When the response is a cacheable representation, it is fingerprinted
	 * with an ETag. A client that returns the same tag in `If-None-Match`
	 * gets a bodyless 304, which is what makes a repeat request cost one
	 * round trip and a few bytes instead of the whole payload.
	 *
	 * @param mixed $data
	 * @param int $code
	 * @return void
	 */
	public function json(mixed $data, int $code = 200): void
	{
		$body = ($data === null) ? null : Formatter::encode($data);
		$etag = ($body !== null && $this->isValidatable($code)) ? EntityTag::generate($body) : null;

		if ($etag !== null && EntityTag::matches($etag, EntityTag::requestedTag()))
		{
			$this->sendNotModified($etag);
			return;
		}

		$this->sendHeaders($code, 'application/json');
		Headers::sendCacheHeaders();

		if ($etag !== null)
		{
			$this->sendEntityTag($etag);
		}

		if ($data === null)
		{
			return;
		}

		echo $body ?? 'Unable to encode the data to JSON.';
	}

	/**
	 * Determines whether a response may carry a validator.
	 *
	 * Only a successful representation of a safe request is worth
	 * revalidating: error bodies and the results of mutations are not
	 * reusable, and a `no-store` directive forbids the client from keeping
	 * the copy a 304 would refer to.
	 *
	 * @param int $code
	 * @return bool
	 */
	protected function isValidatable(int $code): bool
	{
		if ($code !== 200 || !Headers::conditionalRequestsEnabled())
		{
			return false;
		}

		$method = strtoupper(HttpRequest::method());
		if ($method !== 'GET' && $method !== 'HEAD')
		{
			return false;
		}

		return Headers::directive()->isStorable();
	}

	/**
	 * Sends a 304 Not Modified with no body.
	 *
	 * A 304 describes a representation the client already holds, so it
	 * carries no content type and no payload: only the validator and the
	 * refreshed cache directive.
	 *
	 * @param string $etag
	 * @return void
	 */
	protected function sendNotModified(string $etag): void
	{
		if (!headers_sent())
		{
			header_remove('Content-Type');
		}

		$this->sendStatus(304);
		Headers::sendCacheHeaders();
		$this->sendEntityTag($etag);
	}

	/**
	 * @param string $etag
	 * @return void
	 */
	protected function sendEntityTag(string $etag): void
	{
		if (!headers_sent())
		{
			header('ETag: ' . $etag);
		}
	}
}