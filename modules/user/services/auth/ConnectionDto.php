<?php declare(strict_types=1);
namespace Modules\User\Services\Auth;

/**
 * ConnectionDto
 *
 * Immutable value object representing a new authenticated connection.
 *
 * @package Modules\User\Services\Auth
 */
class ConnectionDto
{
	/**
	 * @param DeviceDto $device Authenticated device DTO
	 * @param string $ipAddress IP address
	 * @param string $accessedAt Timestamp of access
	 */
	public function __construct(
		public readonly DeviceDto $device,
		public readonly string $ipAddress,
		public readonly string $accessedAt
	)
	{
	}

	/**
	 * Factory to build from raw device, user and IP.
	 *
	 * @param object $device Raw device object
	 * @param int|string $userId User ID
	 * @param string $ipAddress IP address
	 * @param string|null $accessedAt Optional timestamp (defaults to now)
	 * @return self
	 */
	public static function create(object $device, int|string $userId, string $ipAddress, ?string $accessedAt = null): self
	{
		$dateTime = $accessedAt ?? date('Y-m-d H:i:s');
		$devDto = DeviceDto::fromRaw($device, $userId, $dateTime);

		return new self(
			$devDto,
			$ipAddress,
			$dateTime
		);
	}
}