<?php declare(strict_types=1);
namespace Proto\Http\Rest;

use CurlHandle;
use Proto\Http\Request as HttpRequest;

/**
 * Curl
 *
 * Handles HTTP requests using cURL.
 *
 * @package Proto\Http\Rest
 */
class Curl
{
	/**
	 * The cURL resource handle.
	 */
	protected CurlHandle $curl;

	/**
	 * Whether to reject requests whose host resolves to a private,
	 * loopback, or link-local IP address (SSRF guard). Opt-in only: no
	 * framework helper currently accepts a client-supplied URL, so this
	 * is disabled by default for backward compatibility. Enable via
	 * {@see denyPrivateNetworks()} on any helper you build that accepts
	 * a URL from an untrusted source.
	 *
	 * @var bool
	 */
	protected bool $denyPrivateNetworksGuard = false;

	/**
	 * Initializes the cURL session.
	 *
	 * @param bool $debug Whether to enable debugging.
	 */
	public function __construct(
		protected bool $debug = false
	)
	{
		$this->curl = curl_init();
	}

	/**
	 * Opts this instance into rejecting requests whose target host
	 * resolves to a private, loopback, or link-local IP address after
	 * DNS resolution (SSRF guard). Not enabled by default — call this
	 * before {@see request()} / {@see get()} / {@see post()} / etc. on
	 * any helper you build that accepts a URL from an untrusted
	 * (client-supplied) source.
	 *
	 * @return self
	 */
	public function denyPrivateNetworks(): self
	{
		$this->denyPrivateNetworksGuard = true;
		return $this;
	}

	/**
	 * Whether a URL's host resolves to a private, loopback, link-local,
	 * or otherwise non-public IP address.
	 *
	 * Accepts a literal IP directly, or resolves a hostname's A/AAAA
	 * records via DNS. A host that cannot be resolved at all is treated
	 * as unsafe (fails closed).
	 *
	 * @param string $url
	 * @return bool
	 */
	public static function isPrivateNetworkUrl(string $url): bool
	{
		$host = parse_url($url, PHP_URL_HOST);
		if (!is_string($host) || $host === '')
		{
			return true;
		}

		$host = trim($host, '[]');
		$ips = self::resolveHostIps($host);
		if (empty($ips))
		{
			return true;
		}

		foreach ($ips as $ip)
		{
			if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) === false)
			{
				return true;
			}
		}

		return false;
	}

	/**
	 * Resolves a hostname to its IPv4/IPv6 addresses, or returns a
	 * literal IP as a single-element list.
	 *
	 * @param string $host
	 * @return array<int, string>
	 */
	protected static function resolveHostIps(string $host): array
	{
		if (filter_var($host, FILTER_VALIDATE_IP) !== false)
		{
			return [$host];
		}

		$ips = [];
		$records = @dns_get_record($host, DNS_A + DNS_AAAA) ?: [];
		foreach ($records as $record)
		{
			if (isset($record['ip']))
			{
				$ips[] = $record['ip'];
			}
			elseif (isset($record['ipv6']))
			{
				$ips[] = $record['ipv6'];
			}
		}

		return $ips;
	}

	/**
	 * Enables debugging.
	 *
	 * @return void
	 */
	public function enableDebug(): void
	{
		$this->debug = true;
	}

	/**
	 * Adds cookie support to the request.
	 *
	 * @param string $path Path to the cookie file.
	 * @return void
	 */
	public function addCookies(string $path = 'cookie.txt'): void
	{
		curl_setopt($this->curl, CURLOPT_COOKIEJAR, $path);
		curl_setopt($this->curl, CURLOPT_COOKIEFILE, $path);
	}

	/**
	 * Retrieves the current server URL.
	 *
	 * @return string
	 */
	protected function getServerUrl(): string
	{
		$serverUrl = HttpRequest::fullUrl();
		return "http://{$serverUrl}";
	}

	/**
	 * Configures cURL headers.
	 *
	 * @return self
	 */
	protected function configureHeaders(): self
	{
		curl_setopt($this->curl, CURLOPT_VERBOSE, $this->debug);
		curl_setopt($this->curl, CURLOPT_CUSTOMREQUEST, 'GET');
		curl_setopt($this->curl, CURLOPT_REFERER, $this->getServerUrl());
		curl_setopt($this->curl, CURLOPT_HEADER, $this->debug);

		return $this;
	}

	/**
	 * Configures cURL basic options.
	 *
	 * SSL peer and host verification are enabled by default to prevent
	 * man-in-the-middle attacks.  Call {@see disableSslVerification()} on
	 * an instance if you explicitly need to bypass verification for local
	 * or test-only environments.
	 *
	 * @return self
	 */
	protected function configureOptions(): self
	{
		curl_setopt($this->curl, CURLOPT_NOBODY, false);
		curl_setopt($this->curl, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($this->curl, CURLOPT_FOLLOWLOCATION, true);
		curl_setopt($this->curl, CURLOPT_SSL_VERIFYPEER, true);
		curl_setopt($this->curl, CURLOPT_SSL_VERIFYHOST, 2);
		curl_setopt($this->curl, CURLOPT_ENCODING, 'gzip');

		return $this;
	}

	/**
	 * Disables SSL certificate verification.
	 *
	 * WARNING: Only use this in local/test environments.  Disabling
	 * verification in production exposes all outbound requests to
	 * man-in-the-middle attacks.
	 *
	 * @return self
	 */
	public function disableSslVerification(): self
	{
		curl_setopt($this->curl, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($this->curl, CURLOPT_SSL_VERIFYHOST, false);
		return $this;
	}

	/**
	 * Sets authentication for the request.
	 *
	 * @param string $username
	 * @param string $password
	 * @return self
	 */
	public function setAuthentication(string $username, string $password): self
	{
		curl_setopt($this->curl, CURLOPT_USERPWD, "{$username}:{$password}");
		return $this;
	}

	/**
	 * Sets the request URL.
	 *
	 * @param string $url
	 * @return self
	 */
	protected function setUrl(string $url): self
	{
		curl_setopt($this->curl, CURLOPT_URL, $url);
		return $this;
	}

	/**
	 * Retrieves the HTTP response code.
	 *
	 * @return int
	 */
	protected function getHttpCode(): int
	{
		return curl_getinfo($this->curl, CURLINFO_HTTP_CODE);
	}

	/**
	 * Executes the cURL request and returns the response.
	 *
	 * @return mixed
	 */
	protected function executeRequest(): mixed
	{
		$response = curl_exec($this->curl);
		if ($response === false)
		{
			error(
				curl_error($this->curl),
				__FILE__,
				__LINE__
			);
		}
		return $response ?: null;
	}

	/**
	 * Closes the cURL session.
	 *
	 * @return void
	 */
	protected function close(): void
	{
		unset($this->curl);
	}

	/**
	 * Performs an HTTP request.
	 *
	 * When {@see denyPrivateNetworks()} has been enabled, requests whose
	 * host resolves to a private/loopback/link-local address are
	 * blocked before the cURL request is ever sent, and a `code: 0`
	 * response is returned instead (no request is made; the cURL
	 * session is closed).
	 *
	 * @param string $url Request URL.
	 * @param string $method HTTP method.
	 * @param mixed $params Request parameters.
	 * @return object Response object.
	 */
	public function request(string $url, string $method = 'POST', mixed $params = null): object
	{
		if ($this->denyPrivateNetworksGuard && self::isPrivateNetworkUrl($url))
		{
			error(
				'Curl: request blocked by denyPrivateNetworks(); host resolves to a private/loopback/link-local address: ' . $url,
				__FILE__,
				__LINE__
			);

			$this->close();

			return (object)[
				'code' => 0,
				'data' => null
			];
		}

		$this->configureHeaders();
		$this->configureOptions();

		$curl = $this->curl;

		switch (strtolower($method))
		{
			case 'get':
				if (!empty($params))
				{
					$url = $this->addParamsToUrl($url, $params);
				}
				break;
			case 'post':
			case 'put':
			case 'delete':
			case 'patch':
				curl_setopt($curl, CURLOPT_CUSTOMREQUEST, strtoupper($method));
				curl_setopt($curl, CURLOPT_POSTFIELDS, $params);
				break;
		}

		$this->setUrl($url);
		$response = $this->executeRequest();
		$httpCode = $this->getHttpCode();
		$this->close();

		return (object)[
			'code' => $httpCode,
			'data' => $response
		];
	}

	/**
	 * Adds HTTP headers to the request.
	 *
	 * @param array $headers Associative array of headers.
	 * @return self
	 */
	public function addHeaders(array $headers = []): self
	{
		if (!empty($headers))
		{
			$curlHeaders = array_map(fn($key, $value) => "{$key}: {$value}", array_keys($headers), $headers);
			curl_setopt($this->curl, CURLOPT_HTTPHEADER, $curlHeaders);
		}

		return $this;
	}

	/**
	 * Appends query parameters to the URL.
	 *
	 * @param string $url The base URL.
	 * @param mixed $params Query parameters.
	 * @return string The updated URL.
	 */
	protected function addParamsToUrl(string $url, mixed $params = null): string
	{
		if (empty($params))
		{
			return $url;
		}

		$query = is_array($params) ? http_build_query($params) : $params;
		$separator = (str_contains($url, '?')) ? '&' : '?';

		return "{$url}{$separator}{$query}";
	}

	/**
	 * Sends a GET request.
	 *
	 * @param string|null $url
	 * @param mixed $params
	 * @return object
	 */
	public function get(?string $url = null, mixed $params = null): object
	{
		return $this->request($url ?? '', 'GET', $params);
	}

	/**
	 * Sends a POST request.
	 *
	 * @param string|null $url
	 * @param mixed $params
	 * @return object
	 */
	public function post(?string $url = null, mixed $params = null): object
	{
		return $this->request($url ?? '', 'POST', $params);
	}

	/**
	 * Sends a PATCH request.
	 *
	 * @param string|null $url
	 * @param mixed $params
	 * @return object
	 */
	public function patch(?string $url = null, mixed $params = null): object
	{
		return $this->request($url ?? '', 'PATCH', $params);
	}

	/**
	 * Sends a PUT request.
	 *
	 * @param string|null $url
	 * @param mixed $params
	 * @return object
	 */
	public function put(?string $url = null, mixed $params = null): object
	{
		return $this->request($url ?? '', 'PUT', $params);
	}

	/**
	 * Sends a DELETE request.
	 *
	 * @param string|null $url
	 * @param mixed $params
	 * @return object
	 */
	public function delete(?string $url = null, mixed $params = null): object
	{
		return $this->request($url ?? '', 'DELETE', $params);
	}
}