<?php declare(strict_types=1);
namespace Modules\Developer\Text\Test;

use Common\Text\Text;

/**
 * TestText
 *
 * Sends a test message via text.
 *
 * @package Modules\Developer\Text\Test
 */
class TestText extends Text
{
	/**
	 * This should be overridden to return the message body.
	 *
	 * @return string
	 */
	protected function setupBody(): string
	{
		return <<<EOT
This is a test message.
EOT;
	}
}