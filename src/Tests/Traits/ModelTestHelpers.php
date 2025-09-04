<?php declare(strict_types=1);
namespace Proto\Tests\Traits;

use Proto\Models\Model;

/**
 * ModelTestHelpers
 *
 * Provides model testing utilities for test cases.
 *
 * @package Proto\Tests\Traits
 */
trait ModelTestHelpers
{
	/**
	 * @var array $createdModels Track created models for cleanup
	 */
	protected array $createdModels = [];

	/**
	 * Creates a model instance and persists it to the database.
	 *
	 * @param string $modelClass
	 * @param array $attributes
	 * @return Model
	 */
	protected function createModel(string $modelClass, array $attributes = []): Model
	{
		$model = new $modelClass((object) $attributes);
		$model->add();

		$this->createdModels[] = $model;
		return $model;
	}

	/**
	 * Creates a model instance without persisting to database.
	 *
	 * @param string $modelClass
	 * @param array $attributes
	 * @return Model
	 */
	protected function makeModel(string $modelClass, array $attributes = []): Model
	{
		return new $modelClass((object) $attributes);
	}

	/**
	 * Creates multiple model instances.
	 *
	 * @param string $modelClass
	 * @param int $count
	 * @param array $attributes
	 * @return array
	 */
	protected function createMultiple(string $modelClass, int $count, array $attributes = []): array
	{
		$models = [];
		for ($i = 0; $i < $count; $i++)
		{
			$models[] = $this->createModel($modelClass, $attributes);
		}
		return $models;
	}

	/**
	 * Asserts that a model exists in the database.
	 *
	 * @param Model $model
	 * @return void
	 */
	protected function assertModelExists(Model $model): void
	{
		$tableName = $model->getTableName();
		$idKeyName = $model::idKeyName();
		$id = $model->{$idKeyName};

		$this->assertDatabaseHas($tableName, [$idKeyName => $id]);
	}

	/**
	 * Asserts that a model does not exist in the database.
	 *
	 * @param Model $model
	 * @return void
	 */
	protected function assertModelMissing(Model $model): void
	{
		$tableName = $model->getTableName();
		$idKeyName = $model::idKeyName();
		$id = $model->{$idKeyName};

		$this->assertDatabaseMissing($tableName, [$idKeyName => $id]);
	}

	/**
	 * Asserts that two models are equal.
	 *
	 * @param Model $expected
	 * @param Model $actual
	 * @return void
	 */
	protected function assertModelEquals(Model $expected, Model $actual): void
	{
		$this->assertEquals(get_class($expected), get_class($actual));

		$expectedData = $expected->getMappedData();
		$actualData = $actual->getMappedData();

		$this->assertEquals($expectedData, $actualData);
	}

	/**
	 * Asserts that a model has specific attributes.
	 *
	 * @param Model $model
	 * @param array $attributes
	 * @return void
	 */
	protected function assertModelHasAttributes(Model $model, array $attributes): void
	{
		foreach ($attributes as $key => $expectedValue)
		{
			$this->assertEquals($expectedValue, $model->{$key},
				"Failed asserting that model has attribute [{$key}] with value [{$expectedValue}]"
			);
		}
	}

	/**
	 * Asserts that a model does not have specific attributes.
	 *
	 * @param Model $model
	 * @param array $attributes
	 * @return void
	 */
	protected function assertModelMissingAttributes(Model $model, array $attributes): void
	{
		foreach ($attributes as $key => $value)
		{
			$this->assertNotEquals($value, $model->{$key} ?? null,
				"Failed asserting that model does not have attribute [{$key}] with value [{$value}]"
			);
		}
	}

	/**
	 * Refreshes a model from the database.
	 *
	 * @param Model $model
	 * @return Model|null
	 */
	protected function refreshModel(Model $model): ?Model
	{
		$modelClass = get_class($model);
		$idKeyName = $model::idKeyName();
		$id = $model->{$idKeyName};

		return $modelClass::get($id);
	}

	/**
	 * Cleanup created models.
	 *
	 * @return void
	 */
	protected function cleanupModels(): void
	{
		foreach ($this->createdModels as $model)
		{
			try
			{
				$model->delete();
			}
			catch (\Exception $e)
			{
				// Ignore cleanup errors
			}
		}
		$this->createdModels = [];
	}
}