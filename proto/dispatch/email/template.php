<?php declare(strict_types=1);
namespace Proto\Dispatch\Email;

/**
 * Template
 *
 * This will create an email template.
 *
 * @package Proto\Dispatch\Email
 */
class Template
{
	/**
	 * This will create an email template.
	 *
	 * @param string $email
	 * @param object|null $data
	 * @return object|false
	 */
	public static function create(string $email, ?object $data = null)
	{
		if (!isset($email))
		{
			return false;
		}

		/**
		 * @var object $email
		 */
		$class = $email;
		return new $class($data);
	}
}