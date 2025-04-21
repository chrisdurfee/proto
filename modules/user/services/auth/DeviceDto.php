<?php declare(strict_types=1);
namespace Modules\User\Services\Auth;

/**
 * DeviceDto
 *
 * Immutable value object representing a user’s authenticated device.
 *
 * @package Modules\User\Services\Auth
 */
class DeviceDto
{
	/**
	 * @param int $userId User ID
	 * @param string $accessedAt Timestamp of access
	 * @param string $guid Device GUID
	 * @param string|null $platform Device platform
	 * @param string|null $brand Device brand
	 * @param string|null $vendor Device vendor
	 * @param string|null $version Device version
	 * @param bool|null $touch Touch support
	 * @param bool|null $mobile Mobile browser
	 */
	public function __construct(
		public readonly int $userId,
		public readonly string $accessedAt,
		public readonly string $guid,
		public readonly ?string $platform,
		public readonly ?string $brand,
		public readonly ?string $vendor,
		public readonly ?string $version,
		public readonly ?bool $touch,
		public readonly ?bool $mobile
	) {
	}

	/**
	 * Create a DTO from raw device data.
	 *
	 * @param object $device Raw device object
	 * @param int $userId User ID
	 * @param string $accessedAt Timestamp of access
	 * @return self
	 */
	public static function fromRaw(object $device, int $userId, string $accessedAt): self
	{
		return new self(
			$userId,
			$accessedAt,
			$device->guid,
			$device->platform ?? null,
			$device->brand ?? null,
			$device->vendor ?? null,
			$device->version ?? null,
			isset($device->touch) ? (bool) $device->touch : null,
			isset($device->mobile) ? (bool) $device->mobile : null
		);
	}
}