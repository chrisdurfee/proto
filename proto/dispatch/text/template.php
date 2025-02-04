<?php declare(strict_types=1);
namespace Proto\Dispatch\Text;

/**
 * Template
 *
 * This will create a text template.
 *
 * @package Proto\Dispatch\Text
 */
class Template
{
	/**
	 * This will create a text template.
	 *
	 * @param string $text
	 * @param object|null $data
	 * @return object|false
	 */
	public static function create(string $text, ?object $data = null)
	{
		if (!isset($text))
		{
			return false;
		}

		/**
		 * @var string $text
		 */
		$class = $text;
		return new $class($data);
	}
}