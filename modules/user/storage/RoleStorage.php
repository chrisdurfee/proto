<?php declare(strict_types=1);
namespace Modules\User\Storage;

use Proto\Storage\Storage;

/**
 * RoleStorage
 *
 * This is the storage class for table "roles".
 *
 * @package Modules\User\Storage
 */
class RoleStorage extends Storage
{
    /**
	 * This will get a role by name.
	 *
	 * @param string $name
	 * @return object|null
	 */
	public function getByName(string $name): ?object
	{
		if (!$name)
		{
			return null;
		}

		$params = ['name' => $name];

		return $this->select()
			->where(
				'name = ?'
			)
			->first($params);
	}

    /**
     * This will get a role by slug.
     *
     * @param string $slug
     * @return object|null
     */
    public function getBySlug(string $slug): ?object
    {
        if (!$slug)
        {
            return null;
        }

        $params = ['slug' => $slug];

        return $this->select()
            ->where(
                'slug = ?'
            )
            ->first($params);
    }
}