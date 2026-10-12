<?php declare(strict_types=1);

namespace Proto\Tests\Unit\Models;

use Proto\Database\QueryBuilder\AdapterProxy;
use Proto\Database\QueryBuilder\Select;
use Proto\Models\Model;
use Proto\Tests\Test;

/**
 * UnknownMethodTest
 *
 * Misspelled model/storage/query-builder methods used to return null
 * (or silently drop a query clause). They now throw so the mistake is
 * caught in development instead of looking like "not found".
 *
 * @package Proto\Tests\Unit\Models
 */
final class UnknownMethodTest extends Test
{
	/**
	 * @var bool
	 */
	protected bool $useTransactions = false;

	/**
	 * @return void
	 */
	public function testUnknownStaticModelMethodThrows(): void
	{
		$this->expectException(\BadMethodCallException::class);
		$this->expectExceptionMessage('fetchWher');
		UnknownMethodModel::fetchWher(['id' => 1]); // @phpstan-ignore staticMethod.notFound
	}

	/**
	 * @return void
	 */
	public function testUnknownInstanceModelMethodThrows(): void
	{
		$this->expectException(\BadMethodCallException::class);
		(new UnknownMethodModel())->doesNotExist();
	}

	/**
	 * @return void
	 */
	public function testQueryBuilderTypoThrowsInsteadOfDroppingClause(): void
	{
		$this->expectException(\BadMethodCallException::class);
		(new AdapterProxy(new Select('items', 'i')))->wher('i.id = ?');
	}

	/**
	 * @return void
	 */
	public function testModelWithoutJoinsStillConstructs(): void
	{
		$model = new UnknownMethodModel();
		$this->assertSame([], $model->getJoins());
	}
}

/**
 * @package Proto\Tests\Unit\Models
 */
final class UnknownMethodModel extends Model
{
	/**
	 * @var string|null
	 */
	protected static ?string $tableName = 'unknown_method';

	/**
	 * @var array
	 */
	protected static array $fields = ['id', 'name'];
}
