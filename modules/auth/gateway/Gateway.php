<?php declare(strict_types=1);
namespace Modules\Auth\Gateway;

/**
 * Gateway
 *
 * This will handle the auth module gateway.
 *
 * @package Modules\Auth\Gateway
 */
class Gateway
{
    /**
	 * This will return the secure request gateway.
	 *
	 * @return SecureRequestGateway
	 */
	public function secureRequest(): SecureRequestGateway
	{
		return new SecureRequestGateway();
	}
}