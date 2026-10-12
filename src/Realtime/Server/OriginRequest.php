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
		'x-forwarded-host'
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

		// Append our own caller's address, as a compliant proxy does, so
		// PHP's right-to-left trusted-proxy walk sees the real chain. Keeping
		// a client-sent X-Forwarded-For as-is let any client choose its IP.
		// Pass-through headers (X-Real-IP, CF-Connecting-IP) are not
		// forwarded: a client could set them when connecting directly.
		$remoteIp = self::remoteIp($request->getClient()->getRemoteAddress()->toString());
		if ($remoteIp !== '')
		{
			$headers['x-forwarded-for'] = isset($headers['x-forwarded-for'])
				? $headers['x-forwarded-for'] . ', ' . $remoteIp
				: $remoteIp;
		}

		$uri = $request->getUri();
		return new self($uri->getPath(), $uri->getQuery(), $headers);
	}

	/**
	 * Strips the port (and IPv6 brackets) from a socket address.
	 *
	 * @param string $address e.g. "10.0.0.5:51234" or "[2001:db8::1]:443".
	 * @return string
	 */
	public static function remoteIp(string $address): string
	{
		if (preg_match('/^\[([^\]]+)\](?::\d+)?$/', $address, $match))
		{
			return $match[1];
		}

		// A bare IPv6 address has several colons and no port.
		if (substr_count($address, ':') > 1)
		{
			return $address;
		}

		return preg_replace('/:\d+$/', '', $address) ?? '';
	}

	/**
	 * Query parameters that identify the browser tab, not the stream.
	 * They never change what a shared stream sends.
	 *
	 * @var array<int, string>
	 */
	private const TAB_PARAMS = ['sseClient'];

	/**
	 * Path plus query as the browser sent it (used for upstream calls).
	 *
	 * @return string
	 */
	public function target(): string
	{
		return $this->query !== '' ? $this->path . '?' . $this->query : $this->path;
	}

	/**
	 * The key that makes two viewers "the same stream" for shared
	 * hydration: the path plus the query without per-tab parameters,
	 * sorted so parameter order does not split a group.
	 *
	 * @return string
	 */
	public function sharedKey(): string
	{
		parse_str($this->query, $params);
		foreach (self::TAB_PARAMS as $name)
		{
			unset($params[$name]);
		}

		if ($params === [])
		{
			return $this->path;
		}

		ksort($params);
		return $this->path . '?' . http_build_query($params);
	}
}
