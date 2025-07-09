<?php declare(strict_types=1);
namespace Proto\Dispatch\Email\Unsubscribe;

use Proto\Dispatch\Email\Unsubscribe\Models\Unsubscribe;

/**
 * EmailHelper
 *
 * Provides helper functions for email unsubscribe operations.
 *
 * @package Proto\Dispatch\Email\Unsubscribe
 */
class EmailHelper
{
    /**
	 * This will create a secure unsubscribe URL for the user.
	 *
	 * @param string|null $email
	 * @return string|null
	 */
	public static function getUnsubscribeUrlParams(?string $email): ?string
	{
		if (empty($email))
		{
			return null;
		}

		$requestId = self::getUnsubscribeRequest($email);
		if (!$requestId)
		{
			return null;
		}

		return '?requestId=' . $requestId . '&email=' . urlencode($email);
	}

    /**
	 * This will create a secure unsubscribe URL for the user.
	 *
	 * @param string|null $email
	 * @return string|null
	 */
	public static function createUnsubscribeUrl(?string $email): ?string
	{
		if (empty($email))
		{
			return null;
		}

		$params = self::getUnsubscribeUrlParams($email);
		if (!$params)
		{
			return null;
		}

		$baseUrl = "/user/unsubscribe";
		return envUrl() . $baseUrl . $params;
	}

	/**
	 * Get the unsubscribe request ID for the user.
	 *
	 * @param string $email
	 * @return string|null
	 */
	protected static function getUnsubscribeRequest(string $email): ?string
	{
		$model = Unsubscribe::get($email);
		if ($model)
		{
			return $model->requestId;
		}

		$model = new Unsubscribe((object)[
			'email' => $email
		]);
		$model->add();
		return $model->requestId ?? null;
	}
}