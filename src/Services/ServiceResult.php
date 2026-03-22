<?php declare(strict_types=1);
namespace Proto\Services;

/**
 * ServiceResult
 *
 * A standardized value object for service method return values.
 *
 * Eliminates ambiguity between returning false, null, or error objects
 * from service methods. Controllers can consistently check $result->success
 * and access $result->data or $result->error.
 *
 * Usage in services:
 * ```php
 * return ServiceResult::success($group);
 * return ServiceResult::failure('Group slug already exists');
 * return ServiceResult::failure('Validation error', 'VALIDATION_FAILED');
 * ```
 *
 * Usage in controllers:
 * ```php
 * $result = $this->service->createGroup($userId, $data);
 * if (!$result->success)
 * {
 *     return $this->error($result->error);
 * }
 * return $this->response($result->data);
 * ```
 *
 * @package Proto\Services
 */
class ServiceResult
{
	/**
	 * Whether the operation was successful.
	 *
	 * @var bool
	 */
	public bool $success;

	/**
	 * The result data on success, null on failure.
	 *
	 * @var mixed
	 */
	public mixed $data;

	/**
	 * The error message on failure, null on success.
	 *
	 * @var string|null
	 */
	public ?string $error;

	/**
	 * An optional error code for programmatic handling.
	 *
	 * @var string|null
	 */
	public ?string $code;

	/**
	 * Creates a new ServiceResult instance.
	 *
	 * @param bool $success Whether the operation succeeded.
	 * @param mixed $data The result data.
	 * @param string|null $error The error message.
	 * @param string|null $code The error code.
	 */
	protected function __construct(
		bool $success,
		mixed $data = null,
		?string $error = null,
		?string $code = null
	)
	{
		$this->success = $success;
		$this->data = $data;
		$this->error = $error;
		$this->code = $code;
	}

	/**
	 * Creates a successful result.
	 *
	 * @param mixed $data The result data.
	 * @return static
	 */
	public static function success(mixed $data = null): static
	{
		return new static(true, $data);
	}

	/**
	 * Creates a failure result.
	 *
	 * @param string $message The error message.
	 * @param string|null $code An optional error code.
	 * @return static
	 */
	public static function failure(string $message, ?string $code = null): static
	{
		return new static(false, null, $message, $code);
	}

	/**
	 * Checks if the result represents a failure.
	 *
	 * @return bool
	 */
	public function failed(): bool
	{
		return !$this->success;
	}
}
