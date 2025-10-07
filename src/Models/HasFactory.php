<?php declare(strict_types=1);
namespace Proto\Models;

/**
 * HasFactory Trait
 *
 * Provides factory capabilities to models.
 * Add this trait to your model and define a newFactory() method.
 *
 * @package Proto\Models
 */
trait HasFactory
{
	/**
	 * Create a new factory instance for the model.
	 *
	 * @param int $count
	 * @param array $attributes
	 * @return Factory
	 */
	public static function factory(int $count = 1, array $attributes = []): Factory
	{
		$factoryClass = static::factoryClass();

		if (!class_exists($factoryClass))
        {
			throw new \RuntimeException(
				"Factory class [{$factoryClass}] not found. " .
				"Create a factory class or override the factoryClass() method."
			);
		}

		return new $factoryClass($count, $attributes);
	}

	/**
	 * Get the factory class name for the model.
	 * Override this method to specify a custom factory class.
	 *
	 * @return string
	 */
	protected static function factoryClass(): string
	{
		$modelClass = static::class;

		// Try to find factory in same namespace
		$factoryClass = $modelClass . 'Factory';
		if (class_exists($factoryClass))
        {
			return $factoryClass;
		}

		// Try to find in Database\Factories namespace
		$parts = explode('\\', $modelClass);
		$className = array_pop($parts);
		$factoryClass = implode('\\', $parts) . '\\Factories\\' . $className . 'Factory';

		return $factoryClass;
	}
}
