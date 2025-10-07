<?php declare(strict_types=1);
namespace Proto\Models;

use Proto\Tests\SimpleFaker;

/**
 * Factory
 *
 * Base factory class for generating model instances with fake data.
 * Provides a fluent API for creating models in tests and seeders.
 *
 * @package Proto\Models
 */
abstract class Factory
{
	/**
	 * @var string $modelClass The model class this factory creates
	 */
	protected string $modelClass;

	/**
	 * @var int $count Number of models to create
	 */
	protected int $count = 1;

	/**
	 * @var array $states Active state modifiers
	 */
	protected array $states = [];

	/**
	 * @var array $attributes Attribute overrides
	 */
	protected array $attributes = [];

	/**
	 * @var SimpleFaker|null $faker Faker instance
	 */
	protected ?SimpleFaker $faker = null;

	/**
	 * @var callable|null $afterMaking Callback after making model (before saving)
	 */
	protected $afterMaking = null;

	/**
	 * @var callable|null $afterCreating Callback after creating model (after saving)
	 */
	protected $afterCreating = null;

	/**
	 * Factory constructor.
	 *
	 * @param int $count Number of models to create
	 * @param array $attributes Attribute overrides
	 */
	public function __construct(int $count = 1, array $attributes = [])
	{
		$this->count = $count;
		$this->attributes = $attributes;
		$this->faker = new SimpleFaker();
	}

	/**
	 * Define the model's default state.
	 * Must be implemented by child factory classes.
	 *
	 * @return array
	 */
	abstract public function definition(): array;

	/**
	 * Get the model class name.
	 *
	 * @return string
	 */
	abstract protected function model(): string;

	/**
	 * Create a new factory instance.
	 *
	 * @param int $count
	 * @param array $attributes
	 * @return static
	 */
	public static function new(int $count = 1, array $attributes = []): static
	{
		return new static($count, $attributes);
	}

	/**
	 * Create multiple model instances (shorthand).
	 *
	 * @param int $count
	 * @return static
	 */
	public static function times(int $count): static
	{
		return new static($count);
	}

	/**
	 * Set the number of models to create.
	 *
	 * @param int $count
	 * @return static
	 */
	public function count(int $count): static
	{
		$this->count = $count;
		return $this;
	}

	/**
	 * Apply a state transformation.
	 *
	 * @param string|callable $state State name or callable
	 * @param mixed ...$parameters
	 * @return static
	 */
	public function state(string|callable $state, ...$parameters): static
	{
		if (is_callable($state))
        {
			$this->states[] = ['callback' => $state, 'parameters' => $parameters];
		}
        else
        {
			$method = 'state' . ucfirst($state);
			if (!method_exists($this, $method))
            {
				throw new \BadMethodCallException("State method [{$method}] not found on factory.");
			}
			$this->states[] = ['method' => $method, 'parameters' => $parameters];
		}
		return $this;
	}

	/**
	 * Set attributes to override in the model.
	 *
	 * @param array $attributes
	 * @return static
	 */
	public function set(array $attributes): static
	{
		$this->attributes = array_merge($this->attributes, $attributes);
		return $this;
	}

	/**
	 * Set a single attribute override.
	 *
	 * @param string $key
	 * @param mixed $value
	 * @return static
	 */
	public function setAttribute(string $key, mixed $value): static
	{
		$this->attributes[$key] = $value;
		return $this;
	}

	/**
	 * Add a callback to run after making a model (before saving).
	 *
	 * @param callable $callback
	 * @return static
	 */
	public function afterMaking(callable $callback): static
	{
		$this->afterMaking = $callback;
		return $this;
	}

	/**
	 * Add a callback to run after creating a model (after saving).
	 *
	 * @param callable $callback
	 * @return static
	 */
	public function afterCreating(callable $callback): static
	{
		$this->afterCreating = $callback;
		return $this;
	}

	/**
	 * Make model instances without persisting to database.
	 *
	 * @param array $attributes Additional attributes
	 * @return Model|array
	 */
	public function make(array $attributes = []): Model|array
	{
		$models = [];
		for ($i = 0; $i < $this->count; $i++)
        {
			$models[] = $this->makeOne($attributes);
		}

		return ($this->count === 1) ? $models[0] : $models;
	}

	/**
	 * Create and persist model instances to database.
	 *
	 * @param array $attributes Additional attributes
	 * @return Model|array
	 */
	public function create(array $attributes = []): Model|array
	{
		$models = [];
		for ($i = 0; $i < $this->count; $i++)
        {
			$model = $this->makeOne($attributes);

			// Persist to database
			if (!$model->add())
            {
				throw new \RuntimeException("Failed to create model: " . $model->getLastError());
			}

			// Run afterCreating callback
			if ($this->afterCreating)
            {
				call_user_func($this->afterCreating, $model);
			}

			$models[] = $model;
		}

		return ($this->count === 1) ? $models[0] : $models;
	}

	/**
	 * Create a single model instance.
	 *
	 * @param array $attributes
	 * @return Model
	 */
	protected function makeOne(array $attributes = []): Model
	{
		// Get base definition
		$data = $this->definition();

		// Apply states
		foreach ($this->states as $state)
        {
			if (isset($state['method']))
            {
				$stateData = call_user_func_array([$this, $state['method']], $state['parameters']);
			}
            else
            {
				$stateData = call_user_func_array($state['callback'], array_merge([$data], $state['parameters']));
			}
			$data = array_merge($data, $stateData);
		}

		// Apply factory attributes
		$data = array_merge($data, $this->attributes);

		// Apply call-time attributes
		$data = array_merge($data, $attributes);

		// Create model instance
		$modelClass = $this->model();
		$model = new $modelClass((object) $data);

		// Run afterMaking callback
		if ($this->afterMaking)
        {
			call_user_func($this->afterMaking, $model);
		}

		return $model;
	}

	/**
	 * Create raw attribute arrays without instantiating models.
	 *
	 * @param array $attributes Additional attributes
	 * @return array
	 */
	public function raw(array $attributes = []): array
	{
		$data = [];
		for ($i = 0; $i < $this->count; $i++)
        {
			$data[] = $this->rawOne($attributes);
		}

		return ($this->count === 1) ? $data[0] : $data;
	}

	/**
	 * Create a single raw attribute array.
	 *
	 * @param array $attributes
	 * @return array
	 */
	protected function rawOne(array $attributes = []): array
	{
		$data = $this->definition();

		// Apply states
		foreach ($this->states as $state)
        {
			if (isset($state['method']))
            {
				$stateData = call_user_func_array([$this, $state['method']], $state['parameters']);
			}
            else
            {
				$stateData = call_user_func_array($state['callback'], array_merge([$data], $state['parameters']));
			}
			$data = array_merge($data, $stateData);
		}

		return array_merge($data, $this->attributes, $attributes);
	}

	/**
	 * Get the faker instance.
	 *
	 * @return SimpleFaker
	 */
	protected function faker(): SimpleFaker
	{
		return $this->faker;
	}

	/**
	 * Create models in a specific sequence.
	 *
	 * @param callable $callback Receives $sequence number (1-indexed)
	 * @return array
	 */
	public function sequence(callable $callback): array
	{
		$models = [];
		for ($i = 0; $i < $this->count; $i++)
        {
			$sequenceAttributes = $callback($i + 1);
			$models[] = $this->makeOne($sequenceAttributes);
		}
		return $models;
	}

	/**
	 * Create models and save them to database in sequence.
	 *
	 * @param callable $callback
	 * @return array
	 */
	public function createSequence(callable $callback): array
	{
		$models = [];
		for ($i = 0; $i < $this->count; $i++)
        {
			$sequenceAttributes = $callback($i + 1);
			$model = $this->makeOne($sequenceAttributes);
			if (!$model->add())
            {
				throw new \RuntimeException("Failed to create model: " . $model->getLastError());
			}

			if ($this->afterCreating)
            {
				call_user_func($this->afterCreating, $model);
			}

			$models[] = $model;
		}
		return $models;
	}
}
