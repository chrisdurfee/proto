<?php declare(strict_types=1);
namespace Proto\Http;

use Proto\API\Validator;

/**
 * ItemRequest
 *
 * This will create a request item.
 *
 * @package Proto\Http
 */
class ItemRequest
{
	/**
     * This will setup a validator.
     *
     * @param object $item
     * @param array $settings
     * @return Validator
     */
    private function setupValidator(&$item, array $settings): Validator
    {
        return Validator::create($item, $settings);
    }

    /**
     * This will validate the request item.
     *
     * @param object $item
     * @return bool
     */
    public function validate(&$item): bool
    {
        $rules = $this->rules($item);
        return $this->validateRequestItem($item, $rules);
    }

    /**
     * This will validate a request.
     *
     * @param object $item
     * @param array|null $rules
     * @return bool
     */
    protected function validateRequestItem(&$item, ?array $rules = []): bool
    {
        if (!isset($array) || count($rules) < 1)
        {
            return true;
        }

        $validator = $this->setupValidator($item, $rules);
        return ($validator->isValid() === true);
    }

    /**
     * This will set up the request validate rules.
     *
     * @param object $item
     * @return array
     */
    protected function rules(?object $item): array
    {
        return [];
    }
}
