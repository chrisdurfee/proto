<?php declare(strict_types=1);
namespace Proto\Validation;

/**
 * PasswordPolicy
 *
 * Configurable password-strength checks shared by flows that set a
 * password (initial set, reset, account change). Defaults are reasonable
 * for modern apps; override via {@see configure()} or subclass constants.
 *
 * @package Proto\Validation
 */
class PasswordPolicy
{
	/**
	 * @var int
	 */
	protected static int $minLength = 12;

	/**
	 * @var int
	 */
	protected static int $maxLength = 64;

	/**
	 * @var bool
	 */
	protected static bool $requireUpper = true;

	/**
	 * @var bool
	 */
	protected static bool $requireLower = true;

	/**
	 * @var bool
	 */
	protected static bool $requireDigit = true;

	/**
	 * @var bool
	 */
	protected static bool $requireSpecial = true;

	/**
	 * @var int Minimum name fragment length to reject inside the password.
	 */
	protected static int $nameFragmentLength = 3;

	/**
	 * @var string
	 */
	protected static string $requirements = 'Password must be at least 12 characters and include an uppercase letter, lowercase letter, number, and special character. It must not contain parts of your name.';

	/**
	 * Override policy defaults (typically once at bootstrap).
	 *
	 * @param array<string, mixed> $options
	 * @return void
	 */
	public static function configure(array $options): void
	{
		if (isset($options['minLength']))
		{
			self::$minLength = (int)$options['minLength'];
		}
		if (isset($options['maxLength']))
		{
			self::$maxLength = (int)$options['maxLength'];
		}
		if (isset($options['requireUpper']))
		{
			self::$requireUpper = (bool)$options['requireUpper'];
		}
		if (isset($options['requireLower']))
		{
			self::$requireLower = (bool)$options['requireLower'];
		}
		if (isset($options['requireDigit']))
		{
			self::$requireDigit = (bool)$options['requireDigit'];
		}
		if (isset($options['requireSpecial']))
		{
			self::$requireSpecial = (bool)$options['requireSpecial'];
		}
		if (isset($options['nameFragmentLength']))
		{
			self::$nameFragmentLength = (int)$options['nameFragmentLength'];
		}
		if (isset($options['requirements']) && is_string($options['requirements']))
		{
			self::$requirements = $options['requirements'];
		}
	}

	/**
	 * Reset static config to framework defaults (useful in tests).
	 *
	 * @return void
	 */
	public static function reset(): void
	{
		self::$minLength = 12;
		self::$maxLength = 64;
		self::$requireUpper = true;
		self::$requireLower = true;
		self::$requireDigit = true;
		self::$requireSpecial = true;
		self::$nameFragmentLength = 3;
		self::$requirements = 'Password must be at least 12 characters and include an uppercase letter, lowercase letter, number, and special character. It must not contain parts of your name.';
	}

	/**
	 * Human-readable requirements message.
	 *
	 * @return string
	 */
	public static function requirements(): string
	{
		return self::$requirements;
	}

	/**
	 * Checks a password against the policy.
	 *
	 * @param string $password
	 * @param string $firstName Reject passwords containing name fragments.
	 * @param string $lastName
	 * @return bool
	 */
	public static function isStrong(string $password, string $firstName = '', string $lastName = ''): bool
	{
		$length = strlen($password);
		if ($length < self::$minLength || $length > self::$maxLength)
		{
			return false;
		}

		if (self::$requireUpper && !preg_match('/[A-Z]/', $password))
		{
			return false;
		}
		if (self::$requireLower && !preg_match('/[a-z]/', $password))
		{
			return false;
		}
		if (self::$requireDigit && !preg_match('/[0-9]/', $password))
		{
			return false;
		}
		if (self::$requireSpecial && !preg_match('/[^A-Za-z0-9]/', $password))
		{
			return false;
		}

		return !self::containsNameFragment($password, $firstName, $lastName);
	}

	/**
	 * @param string $password
	 * @param string $firstName
	 * @param string $lastName
	 * @return bool
	 */
	protected static function containsNameFragment(string $password, string $firstName, string $lastName): bool
	{
		$fragmentLen = self::$nameFragmentLength;
		if ($fragmentLen < 1)
		{
			return false;
		}

		$nameParts = array_filter([$firstName, $lastName], fn($n) => strlen($n) >= $fragmentLen);
		$lowerPassword = strtolower($password);

		foreach ($nameParts as $part)
		{
			$partLower = strtolower($part);
			for ($i = 0; $i <= strlen($partLower) - $fragmentLen; $i++)
			{
				if (str_contains($lowerPassword, substr($partLower, $i, $fragmentLen)))
				{
					return true;
				}
			}
		}

		return false;
	}
}
