<?php declare(strict_types=1);
namespace Proto\Api;

/**
 * ResponseMessage
 *
 * This will get the response message.
 *
 * @package Proto\Api
 */
class ResponseMessage
{
	/**
	 * This will get the error message.
	 *
	 * @param string $code
	 * @param string|null $message
	 * @return object
	 */
	public static function getMessage(
		string $code = '0',
		?string $message = null
	): object
	{
		$errors = [
			'0' => ['An unknown error occured', 500],
			'1' => ['No child API was found', 404],
			'2' => ['The api name is not correct or misspelled', 404],
			'3' => ['No action was specified', 404],
			'4' => ['Action was not found in API', 404],
			'5' => ['Model not setup in child api class', 404],
			'6' => ['The user is missing the required role', 403],
			'7' => ['The user is missing the required permission', 401],
			'8' => ['The user session is not setup.', 403],
			'9' => ['The CSRF token does not match.', 403],
			'10' => ['The requested resource is not found.', 404],
			'11' => ['The requested resource is missing details to validate access.', 403],
			'12' => ['The api service has encountered an error', 500],
			'13' => ['The required package level is not enabled.', 403],
			'14' => ['The client account is inactive.', 401],
			'15' => ['The access key is invalid.', 403],
			'16' => ['No client id or access key was set.', 403],
			'17' => ['The sync is being blocked and is not permitted.', 403],
		];

		$settings = $errors[$code] ?? $errors[0];

		return (object)[
			'code' => $code,
			'response' => $settings[1],
			'message' => $message ?? $settings[0],
			'success' => false
		];
	}
}