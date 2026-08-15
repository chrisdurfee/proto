<?php declare(strict_types=1);
namespace Proto\Services;

/**
 * Service
 *
 * Base class for application services. Provides the result envelope,
 * field stripping, and UUID helpers so integrating apps do not
 * reimplement them in Common.
 *
 * @package Proto\Services
 */
abstract class Service
{
	/**
	 * Successful service result.
	 *
	 * @param mixed $data
	 * @return ServiceResult
	 */
	protected function success(mixed $data = null): ServiceResult
	{
		return ServiceResult::success($data);
	}

	/**
	 * Failed service result.
	 *
	 * @param string $message
	 * @param string|null $code
	 * @return ServiceResult
	 */
	protected function failure(string $message, ?string $code = null): ServiceResult
	{
		return ServiceResult::failure($message, $code);
	}

	/**
	 * Strip fields from a data object (mass-assignment / response shaping).
	 *
	 * @param object $data
	 * @param array<int, string> $fields
	 * @return void
	 */
	protected function restrictFields(object &$data, array $fields = []): void
	{
		foreach ($fields as $field)
		{
			unset($data->$field);
		}
	}

	/**
	 * Generate a UUID v4.
	 *
	 * @return string
	 */
	protected function generateUuid(): string
	{
		$data = random_bytes(16);
		$data[6] = chr(ord($data[6]) & 0x0f | 0x40);
		$data[8] = chr(ord($data[8]) & 0x3f | 0x80);
		return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
	}
}