<?php declare(strict_types=1);
namespace Proto\Http;

use Proto\Utils\Filter\Validate;
use Proto\Utils\Filter\Input;

/**
 * PublicIp
 *
 * A class for obtaining the public IP address.
 *
 * @package Proto\Http
 */
class PublicIp
{
	/**
	 * @var string|null $ipAddress
	 */
	protected static ?string $ipAddress = null;

	/**
	 * Returns the public IP address.
	 *
	 * If the IP address is already cached, it will be returned.
	 * Otherwise, the IP address will be fetched and cached before being returned.
	 *
	 * @return string|null
	 */
	public static function get(): ?string
	{
		return static::$ipAddress ?? (static::$ipAddress = static::getPublicIp());
	}

	/**
	 * Fetches the public IP address.
	 *
	 * @return string|null
	 */
	public static function getPublicIp(): ?string
	{
		$headers = [
			"HTTP_CLIENT_IP",
			"HTTP_X_FORWARDED_FOR",
			"REMOTE_ADDR"
		];

		$ip = null;
		foreach ($headers as $key)
		{
			if (array_key_exists($key, $_SERVER))
			{
				$ip = Input::server($key);
				break;
			}
		}

		return static::validateIp($ip);
	}

	/**
	 * Validates the IP address.
	 *
	 * @param string|null $ip
	 * @return string
	 */
	public static function validateIp(?string $ip): string
	{
		return Validate::ip($ip) ? $ip : '';
	}
}