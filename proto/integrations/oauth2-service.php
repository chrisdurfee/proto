<?php declare(strict_types=1);
namespace Proto\Integrations;

/**
 * Oauth2Service class
 *
 * This will setup the oauth2 service.
 *
 * @package Proto\Integrations
 * @abstract
 */
abstract class Oauth2Service extends RestService
{
	/**
	 * @var string $clientId
	 */
	protected $clientId;

	/**
	 * @var string $clientSecret
	 */
	protected $clientSecret;

	/**
	 * @var string $redirectUrl
	 */
	protected $redirectUrl;

	/**
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
	 * This will set the client id.
	 *
	 * @param string $clientId
	 * @return void
	 */
	public function setClientId(string $clientId)
	{
		$this->clientId = $clientId;
	}

	/**
	 * This will set the clientSecret.
	 *
	 * @param string $clientSecret
	 * @return void
	 */
	public function setClientSecret(string $clientSecret)
	{
		$this->clientSecret = $clientSecret;
	}

	/**
	 * This will set the redirect url.
	 *
	 * @param string $redirectUrl
	 * @return void
	 */
	public function setRedirectUrl(string $redirectUrl)
	{
		$this->redirectUrl = $redirectUrl;
	}
}