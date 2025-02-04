<?php declare(strict_types=1);
namespace Proto\Utils\Files\Disks\Drivers\Helpers\Aws\Cloudfront\Storage;

use Proto\Utils\Files\Disks\Drivers\Helpers\Aws\AwsStorage as Storage;

/**
 * CloudfrontSignedUrlStorage
 *
 * This will handle the cloudfront signed url storage.
 *
 * @package Proto\Utils\Files\Disks\Drivers\Helpers\Aws\Cloudfront\Storage
 */
class CloudfrontSignedUrlStorage extends Storage
{
	/**
     * This will get a row using the objectKey.
     *
     * @param int $id
     * @return object|null
     */
    public function getByObjectId(int $id): ?object
    {
        $result = $this->select()
            ->where("{$this->alias}.s3_object_id = ?")
            ->first([$id]);

        return ($result) ? $this->normalize($result) : null;
    }

    /**
	 * This will check if the table aready has the model data.
	 *
	 * @param object $data
	 * @return bool
	 */
	protected function exists($data): bool
	{
		$id = $data->s3_object_id ?? null;
		if (!isset($id))
		{
			return false;
		}

		$rows = $this->select('id')
			->where("{$this->alias}.s3_object_id = ?")
			->limit(1)
			->fetch([$id]);

		return $this->checkExistCount($rows);
	}
}