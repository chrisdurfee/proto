<?php declare(strict_types=1);
namespace App\Text\Auth;

use App\Text\Text;

/**
 * AuthMultiFactorText
 *
 * This is a multi factor auth message.
 *
 * @package App\Text\Auth
 */
class AuthMultiFactorText extends Text
{
	/**
     * This should be overriden to return the message body.
     *
     * @abstract
     * @return string
     */
    protected function setupBody()
	{
		$code = $this->get('code');

		return <<<EOT
This is the auth code {$code}.
EOT;
	}
}