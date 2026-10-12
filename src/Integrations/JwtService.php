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
	 * @throws IntegrationException When the JWT cannot be obtained.
	 */
	public function __construct(?string $clientSecret = null)
	{
		parent::__construct(
			clientSecret: $clientSecret
		);

		$this->authUrl = $this->authUrl ?? $this->url;
		$this->setupToken();
		$this->getJwt();
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
	 * @throws IntegrationException When the upstream token request fails.
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
	 * @throws IntegrationException When the upstream token request fails.
	 */
	protected function setupJwt(object $result): void
	{
		$code = (int)($result->code ?? 0);
		$token = $result->data->token ?? null;
		if (($code === 201 || $code === 200) && is_string($token) && $token !== '')
		{
			$this->jwt = $token;
			return;
		}

		// Previously die()d here, which killed long-running workers.
		$this->reportError($result);
		throw new IntegrationException(
			static::class . ': JWT token request failed (HTTP ' . $code . ').',
			$code
		);
	}

	/**
	 * Reports an error.
	 *
	 * @param object $result The result object.
	 * @return void
	 */
	protected function reportError(object $result): void
	{
		$message = $result->message
			?? ('JWT token request failed (HTTP ' . (int)($result->code ?? 0) . ').');

		error(
			(string)$message,
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