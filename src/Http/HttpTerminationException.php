<?php declare(strict_types=1);
namespace Proto\Http;

use Proto\Utils\Format\JsonFormat;

/**
 * HttpTerminationException
 *
 * Thrown to terminate the current HTTP request with a specific JSON
 * response instead of calling `die`/`exit` directly deep in the call
 * stack (validation failures, policy denials, rate limiting, ...).
 *
 * Benefits over calling `die`/`exit` directly:
 *  - The error path is unit-testable: tests can catch this exception
 *    instead of killing the PHPUnit process.
 *  - `finally` blocks and transaction rollback wrappers around the
 *    call site actually run before the request terminates.
 *  - One shared mechanism instead of several independent
 *    "render JSON + terminate" implementations.
 *
 * This exception is caught exactly once, at the framework's single
 * request-dispatch entry point ({@see \Proto\Http\Router\Router::activateRoute()}),
 * which calls {@see respond()} to render the same JSON body/status
 * code a real HTTP request produced before this refactor.
 *
 * Code that throws this exception outside of a request dispatched
 * through `Router` (e.g. a standalone script) will see it propagate
 * as an uncaught exception, since there is no other catch point.
 *
 * @package Proto\Http
 */
class HttpTerminationException extends \RuntimeException
{
	/**
	 * @param mixed $payload Response body to JSON-encode.
	 * @param int $statusCode HTTP status code to send.
	 */
	public function __construct(
		protected mixed $payload,
		protected int $statusCode = 400
	)
	{
		parent::__construct('HTTP request terminated with status ' . $statusCode);
	}

	/**
	 * The response body to JSON-encode.
	 *
	 * @return mixed
	 */
	public function getPayload(): mixed
	{
		return $this->payload;
	}

	/**
	 * The HTTP status code to send.
	 *
	 * @return int
	 */
	public function getStatusCode(): int
	{
		return $this->statusCode;
	}

	/**
	 * Renders the JSON response and terminates the script.
	 *
	 * Matches the pre-refactor `die`/`exit` behavior of the call sites
	 * that now throw this exception instead: HTTP status code, then a
	 * JSON body, then termination. Any headers the throwing code already
	 * sent (e.g. `Retry-After` on a rate limit) are left untouched.
	 *
	 * @return never
	 */
	public function respond(): never
	{
		if (!headers_sent())
		{
			http_response_code($this->statusCode);
			header('Content-Type: application/json');
		}

		JsonFormat::encodeAndRender($this->payload);
		exit;
	}
}
