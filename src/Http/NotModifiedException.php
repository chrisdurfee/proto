<?php declare(strict_types=1);
namespace Proto\Http;

use Proto\Http\Router\Response;

/**
 * NotModifiedException
 *
 * Terminates the request with a bodyless 304 when a stored validator
 * matches the client's If-None-Match. Distinct from HttpTerminationException
 * so the error path cannot attach a JSON body to a 304.
 *
 * @package Proto\Http
 */
class NotModifiedException extends HttpTerminationException
{
	/**
	 * @param string $etag The stored entity tag to echo back.
	 */
	public function __construct(private readonly string $etag)
	{
		parent::__construct(null, 304);
	}

	/**
	 * @return never
	 */
	public function respond(): never
	{
		(new Response())->sendNotModified($this->etag);
		exit;
	}

	/**
	 * @return string
	 */
	public function getEtag(): string
	{
		return $this->etag;
	}
}
