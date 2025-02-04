<?php declare(strict_types=1);
namespace Proto\Models;

/**
 * SmsModelInterface
 *
 * This is the interface sms number models should follow.
 *
 * @package Proto\Models
 * @interface SmsModelInterface
 */
interface SmsModelInterface
{
	/**
	 * This will get the phone number for the given phone sid.
	 *
	 * @param string $sid
	 * @return string|null
	 */
	public function getNumberBySid(string $sid): ?string;
}