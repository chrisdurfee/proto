<?php declare(strict_types=1);
namespace Modules\User\Storage;

use Proto\Storage\Storage;

/**
 * EmailVerificationStorage
 *
 * Handles email verification storage operations.
 *
 * @package Modules\User\Storage
 */
class EmailVerificationStorage extends Storage
{
	/**
	 * This will create a request ID.
	 *
	 * @return string
	 */
	protected static function createRequestId(): string
	{
		$length = 128;
		return bin2hex(random_bytes($length));
	}

	/**
	 * This will get a validation request by request ID and user ID.
	 *
	 * @param string $requestId
	 * @param mixed $userId
	 * @return object|null
	 */
	public function getByRequest(string $requestId, mixed $userId): ?object
	{
		$params = [$requestId, $userId];

		return $this->select()
			->where(
				'request_id = ?',
				'user_id = ?',
				'status = "pending"'
			)
			->first($params);
	}

	/**
	 * This will add a new password request.
	 *
	 * @return bool
	 */
	public function add(): bool
	{
		$model = $this->model;
		$model->set('requestId', self::createRequestId());

		return parent::add();
	}

	/**
	 * This will get the update status.
	 *
	 * @return object
	 */
	public function updateStatus(): bool
	{
		$data = $this->getUpdateData();

		return $this->db->update($this->tableName, (object)[
			'id' => $data->id,
			'status' => $data->status ?? 'complete'
		]);
	}
}