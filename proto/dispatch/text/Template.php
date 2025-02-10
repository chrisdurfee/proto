<?php declare(strict_types=1);
namespace Proto\Dispatch\Text;

/**
 * Class Template
 *
 * Creates a text template.
 *
 * @package Proto\Dispatch\Text
 */
class Template
{
	/**
	 * Creates a text template.
	 *
	 * @param string $text The fully qualified class name for the template.
	 * @param object|null $data Optional data to pass to the template.
	 * @return object|false Returns an instance of the template or false if the class does not exist.
	 */
	public static function create(string $text, ?object $data = null): bool|object
	{
		if (!class_exists($text))
		{
			return false;
		}

		return new $text($data);
	}
}