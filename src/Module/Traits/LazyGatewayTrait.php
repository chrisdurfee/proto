<?php declare(strict_types=1);
namespace Proto\Module\Traits;

/**
 * LazyGatewayTrait
 *
 * Memoized child gateway / service resolution, keyed on class name plus
 * constructor arguments. Extracted from {@see \Proto\Module\Gateway} so
 * facade gateways that front multiple models (no single primary model,
 * so they cannot satisfy `Gateway::model()`) can compose the same
 * memoization without inheriting a class that forces an abstract
 * `model()` method.
 *
 * Usage:
 * ```php
 * class NewsGateway
 * {
 *     use LazyGatewayTrait;
 *
 *     public function article(): ArticleGateway
 *     {
 *         return $this->gateway(ArticleGateway::class);
 *     }
 *
 *     public function comment(): CommentGateway
 *     {
 *         return $this->gateway(CommentGateway::class);
 *     }
 * }
 * ```
 *
 * @package Proto\Module\Traits
 */
trait LazyGatewayTrait
{
	/**
	 * Memoized child gateway / service instances.
	 *
	 * @var array<string, object>
	 */
	private array $lazyGatewayInstances = [];

	/**
	 * Return a memoized instance of a child gateway or service.
	 *
	 * Keyed on class + constructor args. Different args get different
	 * instances; do not pass a different arg and expect the same object.
	 *
	 * @template T of object
	 * @param class-string<T> $class
	 * @param mixed ...$constructorArgs
	 * @return T
	 */
	protected function gateway(string $class, mixed ...$constructorArgs): object
	{
		$key = $class;
		if ($constructorArgs !== [])
		{
			$key .= ':' . md5(serialize($constructorArgs));
		}

		if (!isset($this->lazyGatewayInstances[$key]))
		{
			$this->lazyGatewayInstances[$key] = new $class(...$constructorArgs);
		}

		/** @var T $instance */
		$instance = $this->lazyGatewayInstances[$key];
		return $instance;
	}
}
