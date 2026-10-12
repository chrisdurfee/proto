<?php declare(strict_types=1);

namespace Proto\Tests\Unit\Auth\Fixtures;

use Proto\Auth\Policies\Policy;

/**
 * GroupPostPolicy
 *
 * Named fixture so Policy::resolveType() has a real short class name.
 *
 * @package Proto\Tests\Unit\Auth\Fixtures
 */
final class GroupPostPolicy extends Policy
{
	/**
	 * @return string|null
	 */
	public function exposedType(): ?string
	{
		return $this->type;
	}
}
