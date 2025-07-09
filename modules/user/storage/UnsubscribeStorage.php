<?php declare(strict_types=1);
namespace Modules\User\Storage;

use Proto\Storage\Storage;

/**
 * UnsubscribeStorage
 *
 * Handles the storage operations for unsubscribe requests.
 *
 * @package Modules\Auth\Storage
 */
class UnsubscribeStorage extends Storage
{
	/**
	 * This will create a request ID.
	 *
	 * @return string
	 */
	protected static function createRequestId(): string
	{
		$length = 64;
		return bin2hex(random_bytes($length));
	}

	/**
	 * This will get a validation request by request ID and email.
	 *
	 * @param string $requestId
	 * @param string $email
	 * @return object|null
	 */
	public function getByRequest(string $requestId, string $email): ?object
	{
		$params = [$requestId, $email];

		return $this->select()
			->where(
				'requestId = ?',
				'email = ?'
			)
			->first($params);
	}

	/**
	 * This will check if the row exists.
	 *
	 * @param mixed $data
	 * @return bool
	 */
	protected function exists(object $data): bool
	{
		$rows = $this->select('email')
			->where('email = ?')
			->limit(1)
			->fetch([$data->email]);

		return $this->checkExistCount($rows);
	}

	/**
	 * This will add a new unsubscribe request.
	 *
	 * @return bool
	 */
	public function add(): bool
	{
		$model = $this->model;
		$model->set('requestId', self::createRequestId());

		return parent::add();
	}
}