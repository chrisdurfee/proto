<?php declare(strict_types=1);
namespace Modules\Example\Gateway;

/**
 * Gateway
 *
 * This will handle the example module gateway. This example show how you
 * can create a module with multiple versions.
 *
 * @package Modules\Example\Gateway
 */
class Gateway
{
	/**
	 * This is a direct method on the gateway example.
	 *
	 * This can be called in other modules like this:
	 * modules()->example()->add();
	 *
	 * @return void
	 */
	public function add(): void
	{
		// implementation for adding an example
	}

	/**
	 * Versioned gateways
	 *
	 * This will return the versioned gateways.
	 *
	 * This allows you to call the module in other modules
	 * like this:
	 *
	 * // version 1
	 * modules()->example()->v1()->add();
	 *
	 * // version 2
	 * modules()->example()->v2()->add();
	 */

	/**
	 * This will return the v1 gateway.
	 *
	 * @return V1\Gateway
	 */
	public function v1(): V1\Gateway
	{
		return new \Modules\Example\Gateway\V1\Gateway();
	}

	/**
	 * This will return the v2 gateway.
	 *
	 * @return V2\Gateway
	 */
	public function v2(): V2\Gateway
	{
		return new \Modules\Example\Gateway\V2\Gateway();
	}
}