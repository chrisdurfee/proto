<?php declare(strict_types=1);
namespace Proto\Dispatch\Email\Unsubscribe;

/**
 * EmailCategory
 *
 * Classifies a dispatch so RFC 8058 List-Unsubscribe headers and the
 * footer unsubscribe link attach only to promotional mail. Transactional
 * and internal mail stay header-light: Gmail treats List-Unsubscribe as
 * a promotional signal.
 *
 * @package Proto\Dispatch\Email\Unsubscribe
 */
class EmailCategory
{
	/**
	 * Account, security, invoices, SOS. Default when omitted.
	 *
	 * @var string
	 */
	public const TRANSACTIONAL = 'transactional';

	/**
	 * Product news, blasts, offers.
	 *
	 * @var string
	 */
	public const MARKETING = 'marketing';

	/**
	 * Recurring consumer roundups (news digest).
	 *
	 * @var string
	 */
	public const DIGEST = 'digest';

	/**
	 * Staff / ops mail. No consumer unsubscribe.
	 *
	 * @var string
	 */
	public const INTERNAL = 'internal';

	/**
	 * @param string|null $category
	 * @return string
	 */
	public static function normalize(?string $category): string
	{
		$value = strtolower(trim((string)$category));
		return match ($value)
		{
			self::MARKETING, self::DIGEST, self::INTERNAL, self::TRANSACTIONAL => $value,
			default => self::TRANSACTIONAL
		};
	}

	/**
	 * Marketing and digest may carry one-click unsubscribe.
	 *
	 * @param string $category Normalized category.
	 * @return bool
	 */
	public static function isPromotional(string $category): bool
	{
		return $category === self::MARKETING || $category === self::DIGEST;
	}

	/**
	 * Whether this send should get a header URL and a footer link.
	 *
	 * An explicit `$settings->unsubscribeUrl` always wins (tests and
	 * callers that already built a token). Otherwise only promotional
	 * categories opt in.
	 *
	 * @param object $settings
	 * @return bool
	 */
	public static function wantsUnsubscribe(object $settings): bool
	{
		if (!empty($settings->unsubscribeUrl))
		{
			return true;
		}

		$category = self::normalize(
			isset($settings->emailCategory) ? (string)$settings->emailCategory : null
		);
		return self::isPromotional($category);
	}
}
