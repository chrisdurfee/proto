<?php declare(strict_types=1);

namespace Proto\Tests\Unit\Api;

use Proto\Api\ResourceHelper;
use Proto\Tests\Test;

/**
 * ResourceHelperTest
 *
 * @package Proto\Tests\Unit\Api
 */
final class ResourceHelperTest extends Test
{
	/**
	 * @var bool
	 */
	protected bool $useTransactions = false;

	/**
	 * @return void
	 */
	public function testSingularizeCommonFolderNames(): void
	{
		$helper = new class extends ResourceHelper
		{
			public static function expose(string $word): string
			{
				return self::singularize($word);
			}
		};

		$this->assertEquals('Storefront', $helper::expose('Storefronts'));
		$this->assertEquals('Category', $helper::expose('Categories'));
		$this->assertEquals('Box', $helper::expose('Boxes'));
		$this->assertEquals('Address', $helper::expose('Addresses'));
		$this->assertEquals('Class', $helper::expose('Class'));
	}
}
