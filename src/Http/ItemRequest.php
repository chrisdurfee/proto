<?php declare(strict_types=1);
namespace Proto\Http;

use Proto\API\Validator;

/**
 * Class ItemRequest
 *
 * Handles validation of request items.
 *
 * @package Proto\Http
 */
class ItemRequest
{
	/**
	 * Initializes a validator for the request item.
	 *
	 * @param object $item Request item.
	 * @param array $settings Validation rules.
	 * @return Validator
	 */
	private function setupValidator(object &$item, array $settings): Validator
	{
		return Validator::create($item, $settings);
	}

	/**
	 * Validates the request item.
	 *
	 * @param object $item Request item.
	 * @return bool Returns true if validation passes, otherwise false.
	 */
	public function validate(object &$item): bool
	{
		$rules = $this->rules($item);
		return $this->validateRequestItem($item, $rules);
	}

	/**
	 * Validates the request item using defined rules.
	 *
	 * @param object $item Request item.
	 * @param array|null $rules Validation rules.
	 * @return bool Returns true if validation passes, otherwise false.
	 */
	protected function validateRequestItem(object &$item, ?array $rules = []): bool
	{
		if (empty($rules))
		{
			return true;
		}

		$validator = $this->setupValidator($item, $rules);
		return $validator->isValid();
	}

	/**
	 * Defines validation rules for the request item.
	 *
	 * @param object|null $item Request item.
	 * @return array Validation rules.
	 */
	protected function rules(?object $item): array
	{
		return [];
	}
}