<?php declare(strict_types=1);
namespace Proto\Api;

use Proto\Utils\Filter\Validate;
use Proto\Utils\Filter\Sanitize;

/**
 * Class Validator
 *
 * Provides functionality for validating and sanitizing data based on a set of rules.
 *
 * @package Proto\Api
 */
class Validator
{
	/**
	 * @var array List of error messages.
	 */
	protected array $errors = [];

	/**
	 * @var bool Indicates whether the data is valid.
	 */
	protected bool $isValid = true;

	/**
	 * Constructor.
	 *
	 * @param array|object $data The data to validate.
	 * @param array $settings Validation settings.
	 */
	public function __construct(protected array|object &$data, array $settings)
	{
		$this->validate($settings);
	}

	/**
	 * Validates the data against the provided settings.
	 *
	 * @param array $settings The validation rules.
	 * @return self
	 */
	protected function validate(array $settings): self
	{
		if (empty($settings))
		{
			return $this;
		}

		foreach ($settings as $key => $value)
		{
			$this->checkValue($key, $value);
		}

		return $this;
	}

	/**
	 * Returns the list of validation errors.
	 *
	 * @return array
	 */
	public function getErrors(): array
	{
		return $this->errors;
	}

	/**
	 * Returns a concatenated string of all error messages.
	 *
	 * @return string
	 */
	public function getMessage(): string
	{
		return implode(', ', $this->errors);
	}

	/**
	 * Indicates whether the data is valid (i.e., no errors).
	 *
	 * @return bool
	 */
	public function isValid(): bool
	{
		return $this->isValid;
	}

	/**
	 * Checks a single data value against its validation settings.
	 *
	 * @param string $key The data key to validate.
	 * @param string $details The validation rule string.
	 * @return bool True if validation passes, false otherwise.
	 */
	protected function checkValue(string $key, string $details): bool
	{
		$value = $this->getValue($key);
		if ($value === null)
		{
			if ($this->isRequired($details))
			{
				$this->addError("The key {$key} is not set.");
				return false;
			}

			return true;
		}

		$type = $this->getType($details);
		$value = $this->sanitizeValue($key, $value, $type[0]);
		return $this->validateByType($key, $value, $type);
	}

	/**
	 * Retrieves the value from the data array or object.
	 *
	 * @param string $key The key to retrieve.
	 * @return mixed The value or null if not set.
	 */
	protected function getValue(string $key): mixed
	{
		if (is_array($this->data))
		{
			return $this->data[$key] ?? null;
		}
		return $this->data->{$key} ?? null;
	}

	/**
	 * Sets the value in the data array or object.
	 *
	 * @param string $key The key to set.
	 * @param mixed $value The value to assign.
	 * @return self
	 */
	protected function setValue(string $key, mixed $value): self
	{
		if (is_array($this->data))
		{
			$this->data[$key] = $value;
		}
		else
		{
			$this->data->{$key} = $value;
		}
		return $this;
	}

	/**
	 * Sanitizes the value using the specified method and updates the data.
	 *
	 * @param string $key The data key.
	 * @param mixed $value The value to sanitize.
	 * @param string $method The sanitization method name.
	 * @return mixed The sanitized value.
	 */
	protected function sanitizeValue(string $key, mixed $value, string $method): mixed
	{
		$value = Sanitize::$method($value);
		$this->setValue($key, $value);
		return $value;
	}

	/**
	 * Validates the value using a specified validation type and limit.
	 *
	 * @param string $key The data key.
	 * @param mixed $value The sanitized value.
	 * @param array $type An array containing [method, limit].
	 * @return bool True if valid, false otherwise.
	 */
	protected function validateByType(string $key, mixed $value, array $type): bool
	{
		$method = $type[0];
		$isValid = Validate::$method($value);

		if ($isValid === false)
		{
			$this->addError("The value {$key} is not correct.");
			return false;
		}

		$limit = $type[1];
		if ($limit > -1)
		{
			$result = (strlen($value) <= $limit);
			if ($result === false)
			{
				$this->addError("The value {$key} is over the max size.");
			}
		}

		return true;
	}

	/**
	 * Parses the validation rule string (e.g., "string:255|required").
	 *
	 * @param string $details The rule string.
	 * @return array [typeMethod, limit].
	 */
	protected function getType(string $details): array
	{
		$parts = explode('|', $details);
		$type = $parts[0] ?? 'string';
		return (strpos($type, ':') !== false) ? explode(':', $type) : [$type, -1];
	}

	/**
	 * Checks if the field is required based on the rule string.
	 *
	 * @param string $details The rule string.
	 * @return bool True if required, false otherwise.
	 */
	protected function isRequired(string $details): bool
	{
		return (strpos($details, 'required') !== false);
	}

	/**
	 * Adds an error message and marks the data as invalid.
	 *
	 * @param string $message The error message.
	 * @return self
	 */
	protected function addError(string $message): self
	{
		$this->isValid = false;
		$this->errors[] = $message;
		return $this;
	}

	/**
	 * Creates a new Validator instance.
	 *
	 * @param array|object $data The data to validate.
	 * @param array $settings The validation settings.
	 * @return Validator
	 */
	public static function create(array|object &$data, array $settings): Validator
	{
		return new static($data, $settings);
	}
}