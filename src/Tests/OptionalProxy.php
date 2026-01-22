<?php declare(strict_types=1);
namespace Proto\Tests;

/**
 * OptionalProxy
 *
 * Provides optional value generation with probability-based null returns.
 * Used by SimpleFaker::optional() to conditionally return values.
 *
 * @package Proto\Tests
 */
class OptionalProxy
{
	/**
	 * @var SimpleFaker
	 */
	protected SimpleFaker $faker;

	/**
	 * @var float
	 */
	protected float $weight;

	/**
	 * @var bool
	 */
	protected bool $shouldReturnValue;

	/**
	 * Constructor.
	 *
	 * @param SimpleFaker $faker
	 * @param float $weight Probability of returning value (0.0 to 1.0)
	 */
	public function __construct(SimpleFaker $faker, float $weight = 0.5)
	{
		$this->faker = $faker;
		$this->weight = max(0.0, min(1.0, $weight));
		$this->shouldReturnValue = $this->faker->boolean((int)($this->weight * 100));
	}

	/**
	 * Magic method to proxy calls to SimpleFaker or return null.
	 *
	 * @param string $method
	 * @param array $arguments
	 * @return mixed
	 */
	public function __call(string $method, array $arguments): mixed
	{
		if (!$this->shouldReturnValue)
		{
			return null;
		}

		if (!method_exists($this->faker, $method))
		{
			throw new \BadMethodCallException("Method {$method} does not exist on SimpleFaker");
		}

		return $this->faker->$method(...$arguments);
	}

	/**
	 * Magic getter for direct property access pattern.
	 *
	 * @param string $name
	 * @return mixed
	 */
	public function __get(string $name): mixed
	{
		if (!$this->shouldReturnValue)
		{
			return null;
		}

		// Allow access like $faker->optional()->value where value is any faker method
		if (method_exists($this->faker, $name))
		{
			return $this->faker->$name();
		}

		return null;
	}
}
