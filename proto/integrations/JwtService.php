<?php declare(strict_types=1);
namespace Proto\Integrations;

use Proto\Http\Jwt;

/**
 * Class JwtService
 *
 * This will setup the JWT service.
 *
 * @package Proto\Integrations
 * @abstract
 */
abstract class JwtService extends Oauth2Service
{
	/**
	 * The product.
	 *
	 * @var string
	 */
	protected string $product;

	/**
	 * Authentication URL.
	 *
	 * @var string
	 */
	protected string $authUrl;

	/**
	 * Token.
	 *
	 * @var string
	 */
	protected string $token;

	/**
	 * Returned JWT.
	 *
	 * @var string
	 */
	protected string $jwt;

	/**
	 * Sets up the JWT service.
	 *
	 * @param string|null $clientSecret The client secret.
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
	 * Sets up the JWT token.
	 *
	 * @return void
	 */
	protected function setupToken(): void
	{
		$payload = $this->setupPayload();
		$this->token = Jwt::encode($payload, $this->clientSecret);
	}

	/**
	 * Sets up the payload.
	 *
	 * @return array
	 */
	protected function setupPayload(): array
	{
		return [];
	}

	/**
	 * Gets the JWT and sets it for future requests.
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
	 * Sets up the headers for the JWT request.
	 *
	 * @return array
	 */
	protected function setupJwtHeaders(): array
	{
		return [
			'Content-Type'  => 'application/json',
			'Authorization' => 'Bearer ' . $this->token
		];
	}

	/**
	 * Sets up the JWT.
	 *
	 * @param object $result The result object.
	 * @return void
	 */
	protected function setupJwt(object $result): void
	{
		if ($result->code === 201 || $result->code === 200)
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
	 * Reports an error.
	 *
	 * @param object $result The result object.
	 * @return void
	 */
	protected function reportError(object $result): void
	{
		error(
			$result->message,
			__FILE__,
			__LINE__
		);
	}

	/**
	 * Sets up the headers for future requests.
	 *
	 * @return array
	 */
	protected function setupHeaders(): array
	{
		return [
			'Content-Type'  => 'application/json',
			'Authorization' => 'Bearer ' . $this->jwt
		];
	}
}