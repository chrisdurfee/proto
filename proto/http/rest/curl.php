<?php declare(strict_types=1);
namespace Proto\Http\Rest;

use Proto\Http\Request as HttpRequest;

/**
 * Curl
 *
 * This will handle the curl request.
 *
 * @package Proto\Http\Rest
 */
class Curl
{
    /**
     * @var object $curl
     */
    protected $curl;

    /**
     * This will setup the curl.
     *
     * @param bool $debug
     * @return void
     */
	public function __construct(
        protected bool $debug = false
    )
	{
		$this->curl = curl_init();
	}

    /**
     * This will activet debugging.
     *
     * @return void
     */
    public function debug(): void
    {
        $this->debug = true;
    }

    /**
     * This will add cookie support to the curl request.
     *
     * @param string $path
     * @return void
     */
    public function addCookies(string $path = 'cookie.txt'): void
    {
        $curl = $this->curl;
        curl_setopt($curl, CURLOPT_COOKIEJAR, $path);
		curl_setopt($curl, CURLOPT_COOKIEFILE, $path);
    }

    /**
     * This will get the server url.
     *
     * @return string
     */
    protected function getServerUrl(): string
    {
        $serverUrl = HttpRequest::fullUrl();
		return "http://{$serverUrl}";
    }

    /**
     * This will add the curl header.
     *
     * @return self
     */
    protected function addHeader(): self
    {
        $curl = $this->curl;
        $debug = $this->debug;

        curl_setopt($curl, CURLOPT_VERBOSE, $debug);
		curl_setopt($curl, CURLOPT_CUSTOMREQUEST, 'GET');

        $serverUrl = $this->getServerUrl();
		curl_setopt($curl, CURLOPT_REFERER, $serverUrl);
		curl_setopt($curl, CURLOPT_HEADER, $debug);

        return $this;
    }

    /**
     * This will se thte curl to return a body.
     *
     * @return self
     */
    protected function noBody(): self
    {
        curl_setopt($this->curl, CURLOPT_NOBODY, FALSE);
        return $this;
    }

    /**
     * This will set the curl encoding.
     *
     * @param string $type
     * @return self
     */
    protected function encoding(string $type = 'gzip'): self
    {
        curl_setopt($this->curl, CURLOPT_ENCODING , $type);
        return $this;
    }

    /**
     * This will set the return transfer.
     *
     * @param bool $return
     * @return self
     */
    protected function returnTransfer(bool $return = true): self
    {
        curl_setopt($this->curl, CURLOPT_RETURNTRANSFER, $return);
        return $this;
    }

    /**
     * This will set the follow locaiton.
     *
     * @param bool $follow
     * @return self
     */
    protected function followLocation(bool $follow = true): self
    {
        curl_setopt($this->curl, CURLOPT_FOLLOWLOCATION, $follow);
        return $this;
    }

    /**
     * This will set the ssl verify peer.
     *
     * @param bool $verify
     * @return self
     */
    protected function sslVerifyPeer(bool $verify = false): self
    {
        curl_setopt($this->curl, CURLOPT_SSL_VERIFYPEER, $verify);
        curl_setopt($this->curl, CURLOPT_SSL_VERIFYHOST, $verify);
        return $this;
    }

    /**
     * This will set the authentication.
     *
     * @param string $username
     * @param string $password
     * @return self
     */
    public function setAuthentication(string $username, string $password): self
    {
        curl_setopt($this->curl, CURLOPT_USERPWD, $username . ':' . $password);
        return $this;
    }

    /**
     * This will set the url.
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
     * This will get the http response code.
     *
     * @return int
     */
    protected function getHttpCode(): int
    {
        return curl_getinfo($this->curl, CURLINFO_HTTP_CODE);
    }

    /**
     * This will execute the curl.
     *
     * @return mixed
     */
    protected function execute(): mixed
    {
        return curl_exec($this->curl);
    }

    /**
     * This will close the curl.
     *
     * @return void
     */
    protected function close(): void
    {
        curl_close($this->curl);
    }

	/**
	 * This will make the request.
	 *
	 * @param string $url
	 * @param string $method
	 * @param string $params
	 * @return object
	 */
	public function request(string $url, string $method = 'post', mixed $params = null): object
	{
		$this->addHeader();
        $this->noBody();
		$this->returnTransfer();
		$this->followLocation();
        $this->sslVerifyPeer();
		$this->encoding();

        $curl = $this->curl;

		switch (strtolower($method))
		{
			case 'get':
				if (isset($params))
				{
					/* we want to add the query params to the end
					of the url */
					$url = $this->addParamsToUrl($url, $params);
				}
				break;
			case 'post':
				curl_setopt($curl, CURLOPT_CUSTOMREQUEST, "POST");
				curl_setopt($curl, CURLOPT_POSTFIELDS, $params);
				break;
			case 'put':
				curl_setopt($curl, CURLOPT_CUSTOMREQUEST, "PUT");
				curl_setopt($curl, CURLOPT_POSTFIELDS, $params);
				break;
			case 'delete':
				curl_setopt($curl, CURLOPT_CUSTOMREQUEST, "DELETE");
				curl_setopt($curl, CURLOPT_POSTFIELDS, $params);
				break;
		}

        $this->setUrl($url);

		try
		{
			$results = $this->execute();
			$httpCode = $this->getHttpCode(); // http response code
		}
		catch (\Exception $e)
		{

		}

		$this->close();

		return (object)[
			'code' => $httpCode,
			'data' => $results
		];
	}

	/**
	 * This will add the headers to the curl.
	 *
	 * @param array $headers
	 * @return self
	 */
	public function addHeaders(array $headers = []): self
	{
		if (count($headers) < 1)
        {
            return $this;
        }

        $curlHeaders = [];
        foreach ($headers as $key => $value)
        {
            array_push($curlHeaders, $key . ": " . $value);
        }

        curl_setopt($this->curl, CURLOPT_HTTPHEADER, $curlHeaders);
        return $this;
	}

	/**
	 * This will add the params to the url.
	 *
	 * @param string $url
	 * @param mixed $params
	 * @return string
	 */
	protected function addParamsToUrl(string $url, mixed $params = null): string
	{
		if (!isset($params))
		{
            return $url;
		}

        if (is_array($params))
        {
            $params = http_build_query($params);
        }

        $hasQuestionMark = (strpos($url, "?"));
        $url .= (!$hasQuestionMark)? "?" . $params : "&" . $params;
		return $url;
	}

	/**
	 * This will make a get request.
	 *
	 * @param string|null $url
	 * @param mixed $params
	 * @return object
	 */
	public function get(?string $url = null, mixed $params = null): object
	{
		return $this->request($url, 'GET', $params);
	}

	/**
	 * This will make a post request.
	 *
	 * @param string|null $url
	 * @param mixed $params
	 * @return object
	 */
	public function post(?string $url = null, mixed $params = null): object
	{
		return $this->request($url, 'POST', $params);
	}

	/**
	 * This will make a patch request.
	 *
	 * @param string|null $url
	 * @param mixed $params
	 * @return object
	 */
	public function patch(?string $url = null, mixed $params = null): object
	{
		return $this->request($url, 'PATCH', $params);
	}

	/**
	 * This will make a put request.
	 *
	 * @param string|null $url
	 * @param mixed $params
	 * @return object
	 */
	public function put(?string $url = null, mixed $params = null): object
	{
		return $this->request($url, 'PUT', $params);
	}

	/**
	 * This will make a delete request.
	 *
	 * @param string|null $url
	 * @param mixed $params
	 * @return object
	 */
	public function delete(?string $url = null, mixed $params = null): object
	{
		return $this->request($url, 'DELETE', $params);
	}
}