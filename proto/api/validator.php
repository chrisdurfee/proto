<?php declare(strict_types=1);
namespace Proto\API;

use Proto\Utils\Filter\Validate;
use Proto\Utils\Filter\Sanitize;

/**
 * Validator
 *
 * This will validate the data.
 *
 * @package Proto\API
 */
class Validator
{
    /**
     * @var array $errors
     */
    protected array $errors = [];

    /**
     * @var bool $isValid
     */
    protected bool $isValid = true;

    /**
     * This will set up the validator.
     *
     * @param array|object $data
     * @param array $settings
     * @return void
     */
    public function __construct(
        protected array|object &$data,
        array $settings
    )
    {
        $this->validate($settings);
    }

    /**
     * This will validate the data.
     *
     * @param array $settings
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
     * This will get the errors.
     *
     * @return array
     */
    public function getErrors(): array
    {
        return $this->errors;
    }

    /**
     * This will get the error message.
     *
     * @return string
     */
    public function getMessage(): string
    {
        return implode(', ', $this->errors);
    }

    /**
     * This will check if the data is valid.
     *
     * @return bool
     */
    public function isValid(): bool
    {
        return $this->isValid;
    }

    /**
     * This will check a data value by settings.
     *
     * @param string $key
     * @param string $details
     * @return bool
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
        }

        $type = $this->getType($details);
        $value = $this->sanitizeValue($key, $value, $type[0]);
        return $this->validateByType($key, $value, $type);
    }

    /**
     * This will get the data key value.
     *
     * @param string $key
     * @return mixed
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
     * This will set the data key value.
     *
     * @param string $key
     * @param mixed $value
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
     * This will sanitize the value.
     *
     * @param string $key
     * @param mixed $value
     * @param string $method
     * @return mixed
     */
    protected function sanitizeValue(string $key, mixed $value, string $method): mixed
    {
        $value = Sanitize::$method($value);
        $this->setValue($key, $value);
        return $value;
    }

    /**
     * This will validate by type.
     *
     * @param string $key
     * @param mixed $value
     * @param array $type
     * @return bool
     */
    protected function validateByType($key, $value, array $type): bool
    {
        $method = $type[0];
        $isValid = Validate::$method($value);
        if ($isValid === false)
        {
            $this->addError("The key {$key} is not correct.");
            return false;
        }

        $limit = $type[1];
        if ($limit > -1)
        {
            $result = (count($limit) <= $limit);
            if ($result === false)
            {
                $this->addError("The key {$key} is over the max size.");
            }
        }

        return true;
    }

    /**
     * This will get the filter type.
     *
     * @param string $details
     * @return array
     */
    protected function getType(string $details): array
    {
        $parts = explode('|', $details);
        $type = $parts[0] ?? 'string';
        return (strpos($type, ':') !== false)? explode(':', $type) : [$type, -1];
    }

    /**
     * This will check if the field is required.
     *
     * @param string $details
     * @return bool
     */
    protected function isRequired(string $details): bool
    {
        return (strpos($details, 'required') !== false);
    }

    /**
     * This will add an error.
     *
     * @param string $message
     * @return self
     */
    protected function addError(string $message): self
    {
        $this->isValid = false;
        array_push($this->errors, $message);

        return $this;
    }

    /**
     * This will create a validator.
     *
     * @param array|object $data
     * @param array $settings
     * @return Validator
     */
    public static function create(&$data, array $settings): Validator
    {
        return new static($data, $settings);
    }
}
