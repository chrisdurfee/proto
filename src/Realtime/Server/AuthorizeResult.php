<?php declare(strict_types=1);
namespace Proto\Realtime\Server;

/**
 * AuthorizeResult
 *
 * Either a descriptor to stream, or PHP's own response to relay.
 *
 * @package Proto\Realtime\Server
 */
final class AuthorizeResult
{
	/**
	 * @param StreamDescriptor|null $descriptor
	 * @param int $status
	 * @param string $body
	 * @param string $contentType
	 */
	private function __construct(
		public readonly ?StreamDescriptor $descriptor,
		public readonly int $status,
		public readonly string $body,
		public readonly string $contentType
	)
	{
	}

	/**
	 * @param StreamDescriptor $descriptor
	 * @return self
	 */
	public static function allowed(StreamDescriptor $descriptor): self
	{
		return new self($descriptor, 200, '', 'text/event-stream');
	}

	/**
	 * @param int $status
	 * @param string $body
	 * @param string $contentType
	 * @return self
	 */
	public static function failed(int $status, string $body, string $contentType = 'application/json'): self
	{
		if ($status < 400 || $status > 599)
		{
			$status = 502;
		}

		if ($body !== '' && !str_contains($contentType, 'json'))
		{
			$body = (string)json_encode(['message' => 'Stream unavailable.']);
			$contentType = 'application/json';
		}

		return new self(null, $status, $body, $contentType);
	}
}
