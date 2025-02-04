<?php declare(strict_types=1);
namespace Proto\Storage;

/**
 * NotificationTypeStorage
 */
class NotificationTypeStorage extends Storage
{
	/**
	 * This will get the notifcation type by name.
	 *
	 * @param string|null $name
	 * @return object|null
	 */
	public function getByName(?string $name): ?object
	{
		if (!isset($name))
		{
			return null;
		}

		$row = $this->select()
			->where('name = ?')
			->first([$name]);

		return $row ?? null;
    }
}