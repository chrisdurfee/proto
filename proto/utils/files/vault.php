<?php declare(strict_types=1);
namespace Proto\Utils\Files;

use Proto\Utils\Files\Disks\Disk;

/**
 * Vault
 *
 * Manages storage disk access, allowing flexible interactions with file systems.
 *
 * @package Proto\Utils\Files
 */
class Vault
{
	/**
	 * Initializes a new disk instance.
	 *
	 * @param string $disk The storage disk type (default: 'local').
	 * @param string|null $bucket The optional bucket name.
	 * @return Disk
	 */
	public static function disk(string $disk = 'local', ?string $bucket = null): Disk
	{
		return new Disk($disk, $bucket);
	}
}