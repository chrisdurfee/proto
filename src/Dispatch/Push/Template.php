<?php declare(strict_types=1);

namespace Proto\Dispatch\Push;

/**
 * Class Template
 *
 * Creates a push template.
 *
 * @package Proto\Dispatch\Push
 */
class Template
{
	/**
	 * Creates a push template.
	 *
	 * @param string $template The fully qualified class name for the push template.
	 * @param object|null $data Optional data to pass to the push template.
	 * @return object|false Returns an instance of the push template or false if the class does not exist.
	 */
	public static function create(string $template, ?object $data = null): bool|object
	{
		if (!class_exists($template))
		{
			return false;
		}

		return new $template($data);
	}
}