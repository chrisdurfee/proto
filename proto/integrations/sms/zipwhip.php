<?php declare(strict_types=1);

namespace Proto\Integrations\Sms;

use Proto\Integrations\RestService;
use Proto\Config;

/**
 * Zipwhip
 *
 * This is the class used to interact with the zipwhip api.
 *
 * Link to docs: https://www.zipwhip.com/api/
 *
 * @package Proto\Integrations\Sms
 */
class Zipwhip extends RestService
{
	/**
	 * @var string $url
	 */
	protected $url = "https://api.zipwhip.com/";

	/**
	 * This will login a user.
	 *
	 * @param string|null $username
	 * @param string|null $password
	 * @return object
	 */
	public function login(?string $username = null, ?string $password = null): object
	{
		$params = "username=" . $username .
				  "&password=" . $password;

		$url = "user/login";

		return $this->fetch('POST', $url, $params);
	}

	/**
	 * This will setup the headers.
	 *
	 * @return array
	 */
	protected function setupHeaders(): array
	{
		return [
			'Content-Type' => 'application/x-www-form-urlencoded; charset=UTF-8'
		];
	}

	/**
	 * This will send an sms message.
	 *
	 * @param string $sessionKey
	 * @param string $to
	 * @param string $message
	 * @param string|null $file
	 * @return object
	 */
	public function message(
		string $sessionKey,
		string $to,
		string $message,
		?string $file = ''
	): object
	{
		$message = $this->encodeMessage($message);
		$params = "session=" . $sessionKey .
				  "&to=+1" . $to .
				  "&body=" . $message .
				  "&file=" . $file;

		$url = "messaging/send?session=" . $sessionKey;

		return $this->fetch('POST', $url, $params);
	}

	/**
	 * This will send an sms message.
	 *
	 * @param string $sessionKey
	 * @param string $to
	 * @param string $message
	 * @return object
	 */
	public function send(string $sessionKey, string $to, string $message): object
	{
		$message = $this->encodeMessage($message);
		if(strlen($message) > 250)
		{
			$message = substr($message, 0, 250);
		}

		$params = "session=" . $sessionKey .
				  "&contacts=" . $to .
				  "&body=" . $message;

		$url = "message/send";

		return $this->fetch('POST', $url, $params);
	}

	/**
	 * This will url encode and format emojis.
	 *
	 * @param string $str
	 * @return string
	 */
	protected function encodeMessage(string $str): string
	{
		$str = urlencode($str);
		$str = str_replace("%5Cn", '%0D', $str);
		return $str;
	}

	/**
	 * This will create a message and format emojis.
	 *
	 * @param string $str
	 * @return string
	 */
	protected function createMessage(string $str): string
	{
		$self = $this;
		$str = preg_replace_callback('/([[:^print:]])/u', function($matches) use($self)
		{
			$item = $matches[1];
			if(empty($item) || $item === "\n")
			{
				return $item;
			}
			return $self->encodeEmojis($item);
		}, $str);
		return $str;
	}

	/**
	 * This will encode an emoji to zipwhip spec.
	 *
	 * @param string $str
	 * @return string
	 */
	protected function encodeEmojis(string $str = ''): string
	{
		$str = json_decode('"' . $str . '"');
		$bytes = str_split($str);
		for ($i = 0; $i < count($bytes); $i++)
		{
			$bytes[$i] = "%" . dechex(ord($bytes[$i]));
		}
		return implode('',$bytes);
	}

	/**
	 * This will add a hook to listen to message progress updates.
	 *
	 * @param string $sessionKey
	 * @param string $app
	 * @return object
	 */
	public function addProgressHook(string $sessionKey, string $app): object
	{
		$event = 'progress';
		$url = 'sms-queue-update.php';

		return $this->setupHook($sessionKey, $event, $url, $app);
	}

	/**
	 * This will setup a hook.
	 *
	 * @param string $sessionKey
	 * @param string $event
	 * @param string $url
	 * @param string $app
	 * @return object
	 */
	protected function setupHook(
		string $sessionKey,
		string $event,
		string $url,
		string $app
	)
	{
		$settings = Config::getInstance();
		$url = $settings->baseUrl;
		$params = "session=" . $sessionKey .
				  "&event={$event}" .
				  "&url=" . urlencode('https://' . $url . '/scripts/bp-reminders/cron/' . $url . '?app=' . $app);

		$url = "webhook/add";

		return $this->fetch('POST', $url, $params);
	}

	/**
	 * This will add a hook.
	 *
	 * @param string $sessionKey
	 * @param string $event
	 * @param string $app
	 * @return object
	 */
	public function addHook(string $sessionKey, string $event, string $app)
	{
		$url = 'sms-responses.php';

		return $this->setupHook($sessionKey, $event, $url, $app);
	}
}