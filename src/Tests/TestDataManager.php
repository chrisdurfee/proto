<?php declare(strict_types=1);
namespace Proto\Tests;

/**
 * TestDataManager
 *
 * Manages test data and provides simple fake data generation.
 *
 * @package Proto\Tests
 */
class TestDataManager
{
	/**
	 * @var array $data
	 */
	protected array $data = [];

	/**
	 * @var SimpleFaker|null $faker
	 */
	protected ?SimpleFaker $faker = null;

	/**
	 * Gets data by key.
	 *
	 * @param string $key
	 * @param mixed $default
	 * @return mixed
	 */
	public function get(string $key, mixed $default = null): mixed
	{
		return $this->data[$key] ?? $default;
	}

	/**
	 * Sets data by key.
	 *
	 * @param string $key
	 * @param mixed $value
	 * @return void
	 */
	public function set(string $key, mixed $value): void
	{
		$this->data[$key] = $value;
	}

	/**
	 * Checks if data exists for key.
	 *
	 * @param string $key
	 * @return bool
	 */
	public function has(string $key): bool
	{
		return array_key_exists($key, $this->data);
	}

	/**
	 * Removes data by key.
	 *
	 * @param string $key
	 * @return void
	 */
	public function remove(string $key): void
	{
		unset($this->data[$key]);
	}

	/**
	 * Clears all data.
	 *
	 * @return void
	 */
	public function clear(): void
	{
		$this->data = [];
	}

	/**
	 * Gets the faker instance.
	 *
	 * @return SimpleFaker
	 */
	public function faker(): SimpleFaker
	{
		if ($this->faker === null) {
			$this->faker = new SimpleFaker();
		}
		return $this->faker;
	}
}