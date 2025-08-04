<?php declare(strict_types=1);
namespace Modules\Auth\Storage;

use Proto\Storage\Storage;

/**
 * PasswordRequestStorage
 *
 * This will set up the password request storage.
 *
 * @package Modules\Auth\Storage
 */
class PasswordRequestStorage extends Storage
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
	 * This will check the request.
	 *
	 * @param string $requestId
	 * @param mixed $userId
	 * @return string|null
	 */
	public function checkRequest(string $requestId, mixed $userId): ?string
	{
		$result = $this->table()
			->select('id')
			->join(function($joins)
			{
				$joins->left('users', 'u')
					->on('pr.user_id = u.id')
					->fields('username');
			})
			->where(
				"{$this->alias}.user_id = ?",
				"request_id = ?",
				"{$this->alias}.status = 'pending'",
				"{$this->alias}.created_at <= DATE_ADD({$this->alias}.created_at, INTERVAL 1 DAY)")
			->first([$userId, $requestId]);

		return ($result->username ?? null);
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

	/**
	 * This will update the status by request ID.
	 *
	 * @param string $requestId
	 * @param string $status
	 * @return bool
	 */
	public function updateStatusByRequest(string $requestId, string $status = 'complete'): bool
	{
		return $this->table()
			->update("status = ?")
			->where("request_id = ?")
			->execute([
				$status,
				$requestId
			]);
	}
}