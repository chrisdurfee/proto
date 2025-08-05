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
	 * @return self
	 */
	protected function configureOptions(): self
	{
		curl_setopt($this->curl, CURLOPT_NOBODY, false);
		curl_setopt($this->curl, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($this->curl, CURLOPT_FOLLOWLOCATION, true);
		curl_setopt($this->curl, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($this->curl, CURLOPT_SSL_VERIFYHOST, false);
		curl_setopt($this->curl, CURLOPT_ENCODING, 'gzip');

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
		return $response ?: null;
	}

	/**
	 * Closes the cURL session.
	 *
	 * @return void
	 */
	protected function close(): void
	{
		curl_close($this->curl);
	}

	/**
	 * Performs an HTTP request.
	 *
	 * @param string $url Request URL.
	 * @param string $method HTTP method.
	 * @param mixed $params Request parameters.
	 * @return object Response object.
	 */
	public function request(string $url, string $method = 'POST', mixed $params = null): object
	{
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