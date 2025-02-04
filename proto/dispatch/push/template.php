<?php declare(strict_types=1);
namespace Proto\Dispatch\Push;

/**
 * Template
 *
 * This will create a push template.
 *
 * @package Proto\Dispatch\Push
 */
class Template
{
	/**
	 * This will create a push template.
	 *
	 * @param string $template
	 * @param array|null $data
	 * @return object|false
	 */
	public static function create(string $push, ?object $data = null)
	{
		if (!isset($push))
		{
			return false;
		}

		/**
		 * @var object $push
		 */
		$class = $push;
		return new $class($data);
	}
}