<?php declare(strict_types=1);
namespace Proto\Utils\Files\Disks\Drivers\Helpers\Aws\Cloudfront;

use Proto\Utils\Files\Disks\Drivers\Helpers\Aws\Cloudfront\Models\CloudfrontSignedUrl;
use Aws\Exception\AwsException;
use Aws\CloudFront\CloudFrontClient;

/**
 * This will hold the expire at time in seconds.
 */
const EXPIRE_DURATION = 14400; // 4 hours

/**
 * This will hold the expiration offset in seconds.
 */
const EXPIRE_OFFSET = 3600; // 1 hour

/**
 * CloudFront
 *
 * This will handle the cloud front integration.
 *
 * @package Proto\Utils\Files\Disks\Drivers\CloudFront
 */
class CloudfrontIntegration
{
	/**
	 * This holds the config.
	 *
	 * @var object $config
	 */
	protected object $config;

	/**
	 * This holds the CloudFront instance.
	 *
	 * @var CloudFrontClient $cloudFront
	 */
	protected CloudFrontClient $cloudFront;

	/**
	 * This constructs the cloud front.
	 *
	 * @return void
	 */
	public function __construct()
	{
		$config = $this->getConfig();
		if (isset($config) === false)
		{
			throw new \Exception("The cloud front config was not set.");
		}

		$this->config = $config;

		$params = $this->getRequiredParams();
		$this->cloudFront = new CloudFrontClient($params);
	}

	/**
	 * This gets the required CloudFrontClient params.
	 *
	 * @return array
	 */
	public function getRequiredParams(): array
	{
		$config = $this->config;
		$credentials = $config->credentials;

		return [
			'credentials' => [
				'key' => $credentials->accessKey,
				'secret' => $credentials->secretKey
			],
			'region'  => $config->region,
			'version' => $config->version
		];
	}

	/**
	 * This will get the config.
	 *
	 * @return object|null
	 */
	protected static function getConfig(): ?object
	{
		return env('files')->amazon->cloudFront ?? null;
	}

	/**
	 * This will get an item with CloudFront.
	 *
	 * @param object $file
	 * @return string
	 */
	public function getSignedUrl(object $file): string
	{
		$row = $this->getUrlRow($file->id);
		if (!isset($row))
		{
			return $this->generate($file);
		}

		/**
		 * If url is expired, generate a new one.
		 */
		if (static::isExpired($row->expires))
		{
			return $this->generate($file);
		}

		return $row->signedUrl;
	}

	/**
	 * This will get the url row.
	 *
	 * @param int $objectId
	 * @return object|null
	 */
	protected function getUrlRow(int $objectId): ?object
	{
		$model = new CloudfrontSignedUrl();
		return $model->getByObjectId($objectId);
	}

	/**
	 * This returns if the url is expired or will be soon.
	 *
	 * @param int $expires
	 * @return boolean
	 */
	protected static function isExpired(int $expires): bool
	{
		if (!isset($expires))
		{
			return true;
		}

		$cutoff = time() + EXPIRE_OFFSET;
		return $expires < $cutoff;
	}

	/**
	 * This will generate a signed url.
	 *
	 * @param object $file
	 * @return string
	 */
	protected function generate(object $file): string
	{
		$key = dirname(__DIR__) . '/../../../../../../../app/integrations/aws/cloudfront/cloudfront_private_key.pem';
		$expires = time() + EXPIRE_DURATION;
		$url = $this->config->url . $file->objectKey;

		try
		{
			$signedUrl = $this->cloudFront->getSignedUrl([
				'key_pair_id' => $this->config->keyPairId,
				'private_key' => $key,
				'url' => $url,
				'expires' => $expires
			]);

			$this->save($file, $signedUrl, $expires);
			return $signedUrl;
		}
		catch (AwsException $e)
		{
			error(
				'Error: ' . $e->getAwsErrorMessage(),
				__FILE__,
				__LINE__
			);
			return '';
		}
	}

	/**
	 * This will save the signed url.
	 *
	 * @param object $file
	 * @param string $signedUrl
	 * @param int $expire
	 * @return bool
	 */
	protected function save(object $file, string $signedUrl, int $expire): bool
	{
		$model = new CloudfrontSignedUrl((object)[
			's3ObjectId' => $file->id,
			'signedUrl' => $signedUrl,
			'expires' => $expire
		]);
		return $model->setup();
	}
}