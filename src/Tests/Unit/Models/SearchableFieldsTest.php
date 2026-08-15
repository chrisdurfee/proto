<?php declare(strict_types=1);

namespace Proto\Tests\Unit\Models;

use Proto\Models\Model;
use Proto\Tests\Test;

/**
 * SearchableFieldsTest
 *
 * @package Proto\Tests\Unit\Models
 */
final class SearchableFieldsTest extends Test
{
	/**
	 * @var bool
	 */
	protected bool $useTransactions = false;

	/**
	 * @return void
	 */
	public function testDefaultSearchableFieldsAreOff(): void
	{
		$model = new class extends Model
		{
			protected static ?string $tableName = 'searchable_fields_test';
			protected static array $fields = ['id', 'name', 'email', 'title'];
		};

		$this->assertEquals([], $model->getSearchableFields());
	}

	/**
	 * @return void
	 */
	public function testStarInfersMinusSecretsAndAudit(): void
	{
		$model = new class extends Model
		{
			protected static ?string $tableName = 'searchable_fields_infer';
			protected static array $fields = ['id', 'name', 'email', 'phone', 'secret', 'apiKey', 'title'];
			protected static array $searchableFields = ['*'];
		};

		$this->assertEquals(['name', 'title'], $model->getSearchableFields());
	}

	/**
	 * @return void
	 */
	public function testExplicitListIsUsedAsIs(): void
	{
		$model = new class extends Model
		{
			protected static ?string $tableName = 'searchable_fields_explicit';
			protected static array $fields = ['id', 'name', 'email'];
			protected static array $searchableFields = ['name', 'email'];
		};

		$this->assertEquals(['name', 'email'], $model->getSearchableFields());
	}
}
