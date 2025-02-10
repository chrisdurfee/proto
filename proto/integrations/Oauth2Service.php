<?php declare(strict_types=1);
namespace Proto\Integrations;

/**
 * Class Oauth2Service
 *
 * This will set up a service that uses OAuth2.
 *
 * @package Proto\Integrations
 * @abstract
 */
abstract class Oauth2Service extends RestService
{
	/**
	 * Client ID.
	 *
	 * @var string|null
	 */
	protected ?string $clientId;

	/**
	 * Client Secret.
	 *
	 * @var string|null
	 */
	protected ?string $clientSecret;

	/**
	 * Redirect URL.
	 *
	 * @var string|null
	 */
	protected ?string $redirectUrl;

	/**
	 * Constructor.
	 *
	 * @param string|null $clientId
	 * @param string|null $clientSecret
	 * @param string|null $redirectUrl
	 * @return void
	 */
	public function __construct(
		?string $clientId = null,
		?string $clientSecret = null,
		?string $redirectUrl = null
	)
	{
		$this->clientId = $clientId;
		$this->clientSecret = $clientSecret;
		$this->redirectUrl = $redirectUrl;

		parent::__construct();
	}

	/**
	 * Sets the client ID.
	 *
	 * @param string $clientId
	 * @return void
	 */
	public function setClientId(string $clientId): void
	{
		$this->clientId = $clientId;
	}

	/**
	 * Sets the client secret.
	 *
	 * @param string $clientSecret
	 * @return void
	 */
	public function setClientSecret(string $clientSecret): void
	{
		$this->clientSecret = $clientSecret;
	}

	/**
	 * Sets the redirect URL.
	 *
	 * @param string $redirectUrl
	 * @return void
	 */
	public function setRedirectUrl(string $redirectUrl): void
	{
		$this->redirectUrl = $redirectUrl;
	}
}