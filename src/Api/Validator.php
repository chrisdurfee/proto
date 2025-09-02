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

		// Skip sanitization for image files
		if ($type[0] !== 'image')
		{
			$value = $this->sanitizeValue($key, $value, $type[0]);
		}

		return $this->validateByType($key, $value, $type, $details);
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
	 * @param string $details The full validation rule string.
	 * @return bool True if valid, false otherwise.
	 */
	protected function validateByType(string $key, mixed $value, array $type, string $details = ''): bool
	{
		$method = $type[0];

		// Handle image validation specially
		if ($method === 'image')
		{
			return $this->validateImage($key, $value, $type[1], $details);
		}

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
	 * Validates an image file with specific rules.
	 *
	 * @param string $key The data key.
	 * @param mixed $value The image file value.
	 * @param int $maxSizeKb Maximum file size in KB.
	 * @param string $details The full validation rule string.
	 * @return bool True if valid, false otherwise.
	 */
	protected function validateImage(string $key, mixed $value, int $maxSizeKb, string $details): bool
	{
		// First check if it's a valid image file format
		if (!Validate::image($value))
		{
			$this->addError("The value {$key} is not a valid image file.");
			return false;
		}

		// Parse additional rules from details
		$allowedMimes = $this->parseImageMimes($details);

		// Use ImageValidator for comprehensive validation
		$validation = ImageValidator::validate($value, $maxSizeKb, $allowedMimes);
		if (!$validation['valid'])
		{
			foreach ($validation['errors'] as $error)
			{
				$this->addError("The image {$key}: {$error}");
			}
			return false;
		}

		return true;
	}

	/**
	 * Parses MIME types from the validation details string.
	 *
	 * @param string $details The validation rule string.
	 * @return array|null Array of allowed MIME types or null for defaults.
	 */
	protected function parseImageMimes(string $details): ?array
	{
		$parts = explode('|', $details);
		foreach ($parts as $part)
		{
			$part = trim($part);
			if (str_starts_with($part, 'mimes:'))
			{
				$mimeString = substr($part, 6); // Remove 'mimes:' prefix
				return ImageValidator::parseMimeTypes($mimeString);
			}
		}

		return null; // Use defaults
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