<?php declare(strict_types=1);
namespace Proto\Realtime\Server;

use Amp\Http\Server\Request;

/**
 * OriginRequest
 *
 * The parts of the browser's `GET .../sync` request that PHP needs to
 * re-run the same route and policy: path, query, and the identity
 * headers. Everything else (bodies, arbitrary headers) is dropped.
 *
 * @package Proto\Realtime\Server
 */
final class OriginRequest
{
	/**
	 * Headers forwarded to PHP. Cookie carries the session; the rest keep
	 * client IP, CSRF origin checks, and logging accurate.
	 *
	 * @var array<int, string>
	 */
	public const FORWARDED_HEADERS = [
		'cookie',
		'host',
		'user-agent',
		'accept-language',
		'origin',
		'referer',
		'x-forwarded-for',
		'x-forwarded-proto',
		'x-forwarded-host',
		'x-real-ip',
		'cf-connecting-ip'
	];

	/**
	 * @param string $path
	 * @param string $query
	 * @param array<string, string> $headers Lower-case names.
	 */
	public function __construct(
		public readonly string $path,
		public readonly string $query,
		public readonly array $headers
	)
	{
	}

	/**
	 * @param Request $request
	 * @return self
	 */
	public static function fromServerRequest(Request $request): self
	{
		$headers = [];
		foreach (self::FORWARDED_HEADERS as $name)
		{
			$value = $request->getHeader($name);
			if ($value !== null && $value !== '')
			{
				$headers[$name] = $value;
			}
		}

		// Our own caller's address is the proxy in front; record it so PHP
		// sees the same client IP chain it would have seen directly.
		$remote = $request->getClient()->getRemoteAddress()->toString();
		$remoteIp = preg_replace('/:\d+$/', '', trim($remote, '[]')) ?? '';
		if (!isset($headers['x-forwarded-for']) && $remoteIp !== '')
		{
			$headers['x-forwarded-for'] = $remoteIp;
		}

		$uri = $request->getUri();
		return new self($uri->getPath(), $uri->getQuery(), $headers);
	}

	/**
	 * Path plus query, the key that identifies "the same stream" for
	 * shared hydration.
	 *
	 * @return string
	 */
	public function target(): string
	{
		return $this->query !== '' ? $this->path . '?' . $this->query : $this->path;
	}
}
