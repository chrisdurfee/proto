<?php declare(strict_types=1);
namespace Proto\Utils\Files;

use Proto\Utils\Files\Disks\Disk;

/**
 * Vault
 *
 * This will handle the vault.
 *
 * @package Proto\Utils\Files
 */
class Vault
{
    /**
     * This will initialize a new disk.
     *
     * @param string $disk
     * @param string|null $bucket
     * @return Disk
     */
    public static function disk(
        string $disk = 'local',
        ?string $bucket = null
    ): Disk
    {
        return new Disk($disk, $bucket);
    }
}
