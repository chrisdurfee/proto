<?php declare(strict_types=1);
namespace Modules\User\Gateway;

/**
 * Gateway
 *
 * This will handle the user module gateway.
 *
 * @package Modules\User\Gateway
 */
class Gateway
{
	/**
	 * This will add the user.
	 *
	 * @return void
	 */
	public function add(): void
	{
		// implementation for adding a user
	}

	/**
	 * This will return the push gateway.
	 *
	 * @return PushGateway
	 */
	public function push(): PushGateway
	{
		return new PushGateway();
	}
}