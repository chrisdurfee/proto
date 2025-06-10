<?php declare(strict_types=1);
namespace Modules\Auth\Auth\Gates;

use Modules\Auth\Models\SecureRequest;
use Proto\Auth\Gates\Gate;

/**
 * SecureRequestGate
 *
 * This will set up the secure request gate.
 *
 * @package Modules\Auth\Auth\Gates
 */
class SecureRequestGate extends Gate
{
	/**
	 * @var string|null $requestId
	 */
	protected ?string $requestId = null;

	/**
	 * This will setup the model class.
	 *
	 * @param string $model by using the magic constant ::class
	 * @return void
	 */
	public function __construct(
		protected string $model = SecureRequest::class
	)
	{
		parent::__construct();
	}

	/**
	 * This will get the request.
	 *
	 * @return object|null
	 */
	public function getRequest(string $requestId, int $userId): ?object
	{
		return ($this->model::getByRequest($requestId, $userId));
	}

	/**
	 * This will get the request.
	 *
	 * @return SecureRequest|null
	 */
	public function create(int $userId): ?SecureRequest
	{
		$model = new $this->model((object)[
			'userId' => $userId
		]);
		$result = $model->add();
		return $result ? $model : null;
	}

       /**
        * This will update the current request status based on `$this->requestId`.
        *
        * @return bool
        */
       public function updateRequest(): bool
	{
		if ($this->requestId === null)
		{
			return false;
		}

		$model = $this->model;
		return (new $model((object)[
			'id' => $this->requestId,
			'status' => 'complete'
		]))->updateStatus();
	}

	/**
	 * This will check if the request is valid.
	 *
	 * @param string $requestId
	 * @param int $userId
	 * @return bool
	 */
	public function isValid(string $requestId, int $userId): bool
	{
		$request = $this->getRequest($requestId, $userId);
		if (empty($request))
		{
			return false;
		}

		$this->requestId = $request->requestId;
		return true;
	}
}