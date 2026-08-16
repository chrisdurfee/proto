<?php declare(strict_types=1);
namespace Proto\Services\Traits;

use Proto\Services\ServiceResult;

/**
 * ServiceResultFactory
 *
 * Lets any class adopt {@see ServiceResult} without extending
 * {@see \Proto\Services\Service} as a base class.
 *
 * Some app-level base service classes (e.g. a hand-copied
 * `Common\Services\Service`) cannot extend `Proto\Services\Service`
 * because they already define their own `success()` method with a
 * different, incompatible signature/meaning (e.g. an HTTP response
 * envelope), which would collide with `Proto\Services\Service::success()`.
 *
 * `ok()` / `fail()` are deliberately named differently from
 * `success()` / `failure()` to avoid that exact collision, so a class
 * can `use ServiceResultFactory;` directly and adopt `ServiceResult`
 * incrementally, independent of its base class.
 *
 * Usage:
 * ```php
 * use Proto\Services\Traits\ServiceResultFactory;
 * use Proto\Services\ServiceResult;
 *
 * class LegacyService extends Common\Services\Service
 * {
 * 	use ServiceResultFactory;
 *
 * 	public function createGroup(array $data): ServiceResult
 * 	{
 * 		$group = new Group($data);
 * 		return $group->add() ? $this->ok($group) : $this->fail('Unable to create group');
 * 	}
 * }
 * ```
 *
 * @package Proto\Services\Traits
 */
trait ServiceResultFactory
{
	/**
	 * Creates a successful {@see ServiceResult}.
	 *
	 * @param mixed $data The result data.
	 * @return ServiceResult
	 */
	protected function ok(mixed $data = null): ServiceResult
	{
		return ServiceResult::success($data);
	}

	/**
	 * Creates a failed {@see ServiceResult}.
	 *
	 * @param string $message The error message.
	 * @param string|null $code An optional error code.
	 * @return ServiceResult
	 */
	protected function fail(string $message, ?string $code = null): ServiceResult
	{
		return ServiceResult::failure($message, $code);
	}
}
