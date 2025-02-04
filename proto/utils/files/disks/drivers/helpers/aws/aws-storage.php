<?php declare(strict_types=1);
namespace Proto\Utils\Files\Disks\Drivers\Helpers\Aws;

use Proto\Storage\Storage;

/**
 * AwsStorage
 *
 * This will handle the aws storage.
 *
 * @package Proto\Utils\Files\Disks\Drivers\Helpers\Aws
 */
class AwsStorage extends Storage
{
    /**
	 * This will get the database connection.
	 *
	 * @return string|bool
	 */
	protected function getConnection(): string|false
	{
		return env('files')->amazon->connection ?? 'proto';
	}
}
