<?php declare(strict_types=1);
namespace Modules\User\Auth\Gates;

use Modules\User\Models\EmailVerification;
use Proto\Auth\Gates\Gate;

/**
 * EmailVerificationGate
 *
 * This will set up the email verification gate.
 *
 * @package Modules\User\Auth\Gates
 */
class EmailVerificationGate extends Gate
{
	/**
	 * @var string|null $requestId
	 */
	protected ?string $requestId = null;

	/**
	 * This will setup the model class.
	 *
	 * @param string $modelClass by using the magic constant ::class
	 * @return void
	 */
	public function __construct(
		protected $modelClass = EmailVerification::class
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
		return ($this->modelClass::getByRequest($requestId, $userId));
	}

	/**
	 * This will update the request status.
	 *
	 * @param string $requestId
	 * @param int $userId
	 * @return bool
	 */
	public function updateRequest(): bool
	{
		if ($this->requestId === null)
		{
			return false;
		}

		$modelClass = $this->modelClass;
		return (new $modelClass((object)[
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