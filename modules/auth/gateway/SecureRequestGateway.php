<?php declare(strict_types=1);
namespace Modules\Auth\Gateway;

use Modules\Auth\Auth\Gates\SecureRequestGate;

/**
 * SecureRequestGateway
 *
 * This will handle the secure request gateway.
 *
 * @package Modules\Auth\Gateway
 */
class SecureRequestGateway
{
	/**
	 * This will set up the gate.
	 *
	 * @param SecureRequestGate $gate
	 */
	public function __construct(
		protected SecureRequestGate $gate = new SecureRequestGate()
	)
	{
	}

	/**
	 * Sends a web push notification to the user.
	 *
	 * @param mixed $userId The user ID
	 * @return object|null The send request model or null
	 */
	public function create(mixed $userId): ?object
	{
		return $this->gate->create($userId);
	}

	/**
	 * Checks if the request is valid.
	 *
	 * @param string $requestId The request ID
	 * @param int $userId The user ID
	 * @return bool True if valid, false otherwise
	 */
	public function isValid(string $requestId, int $userId): bool
	{
		return $this->gate->isValid($requestId, $userId);
	}
}