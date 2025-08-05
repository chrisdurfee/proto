<?php declare(strict_types=1);
namespace Proto\Dispatch\Email;

/**
 * Class Template
 *
 * Creates an email template.
 *
 * @package Proto\Dispatch\Email
 */
class Template
{
	/**
	 * Creates an email template.
	 *
	 * @param string $email The fully qualified class name for the email template.
	 * @param object|null $data Optional data to pass to the email template.
	 * @return object|false Returns an instance of the email template or false if the class does not exist.
	 */
	public static function create(string $email, ?object $data = null): bool|object
	{
		if (!class_exists($email))
		{
			return false;
		}

		return new $email($data);
	}
}