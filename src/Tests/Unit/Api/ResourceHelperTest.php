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

	/**
	 * Numeric ids are not part of the resolution cache key.
	 *
	 * @return void
	 */
	public function testGetResourceCacheIgnoresNumericIds(): void
	{
		$helper = new class extends ResourceHelper
		{
			public static function exposeCache(): array
			{
				return self::$resolvedCache;
			}

			public static function resetCache(): void
			{
				self::$resolvedCache = [];
			}
		};

		$helper::resetCache();
		$first = $helper::getResource('post/1');
		$cacheAfterFirst = $helper::exposeCache();
		$second = $helper::getResource('post/2');
		$cacheAfterSecond = $helper::exposeCache();

		$this->assertSame($first, $second);
		$this->assertArrayHasKey('Post', $cacheAfterFirst);
		$this->assertSame($cacheAfterFirst, $cacheAfterSecond);
		$this->assertCount(1, $cacheAfterSecond);
	}
}
