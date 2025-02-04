<?php declare(strict_types=1);
namespace Proto\Integrations;

use Proto\Http\Jwt;

/**
 * JwtService class
 *
 * This will setup the JWT service.
 *
 * @package Proto\Integrations
 * @abstract
 */
abstract class JwtService extends Oauth2Service
{
    /**
     * The product
     *
     * @var string $product
     */
    protected string $product;

    /**
     * @var string $authUrl
     */
    protected string $authUrl;

    /**
     * @var string $token
     */
    protected string $token;

    /**
     * This will be the returned JWT
     *
     * @var string $jwt
     */
    protected string $jwt;

    /**
     * This will set up the JWT service.
     *
     * @param string|null $clientSecret
     * @return void
     */
    public function __construct(?string $clientSecret = null)
	{
        $this->clientSecret = $clientSecret;
        $this->authUrl = $this->authUrl ?? $this->url;
        $this->setupToken();
        $this->getJwt();

		parent::__construct();
	}

    /**
     * This will setup the JWT token.
     *
     * @return void
     */
    protected function setupToken(): void
    {
        $payload = $this->setupPayload();
        $this->token = Jwt::encode($payload, $this->clientSecret);
    }

    /**
     * This will setup the payload
     *
     * @return array
     */
    protected function setupPayload(): array
    {
        return [];
    }

    /**
     * This will get the JWT and set it for future request
     *
     * @return void
     */
    protected function getJwt(): void
    {
		$headers = $this->setupJwtHeaders();
		$api = new Request($this->authUrl, $headers);
        $result = $api->send("POST");
        $this->setupJwt($result);
    }

    /**
     * This will setup the token for the JWT request
     *
     * @return array
     */
    protected function setupJwtHeaders(): array
    {
        return [
            'Content-Type' => 'application/json',
            'Authorization' => 'Bearer ' . $this->token
		];
    }

    /**
     * This will setup the JWT
     *
     * @param object $result
     * @return void
     */
    protected function setupJwt(object $result)
    {
        if($result->code === 201 || $result->code === 200)
        {
            $this->jwt = $result->data->token;
        }
        else
        {
            $this->reportError($result);
            die();
        }
    }

    /**
     * This will report the error
     *
     * @param object $result
     * @return void
     */
    protected function reportError($result)
    {
        var_dump($result);
    }

    /**
     * This will setup the headers.
     *
     * @return array
     */
	protected function setupHeaders(): array
	{
		return [
            'Content-Type' => 'application/json',
            'Authorization' => 'Bearer ' . $this->jwt
		];
	}
}