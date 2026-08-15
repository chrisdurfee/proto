<?php declare(strict_types=1);

namespace Proto\Tests\Unit\Storage;

use Proto\Storage\ModifierUtil;
use Proto\Tests\Test;

/**
 * ModifierUtilTest
 *
 * @package Proto\Tests\Unit\Storage
 */
final class ModifierUtilTest extends Test
{
	/**
	 * @var bool
	 */
	protected bool $useTransactions = false;

	/**
	 * LIKE terms must escape % and _ so clients cannot widen the match.
	 *
	 * @return void
	 */
	public function testAddSearchModifierEscapesLikeWildcards(): void
	{
		$where = [];
		$params = [];
		ModifierUtil::addSearchModifier('a%b_c', $where, $params, false, '', ['title']);

		$this->assertNotEmpty($where);
		$this->assertSame(['%a\\%b\\_c%'], $params);
	}
}
