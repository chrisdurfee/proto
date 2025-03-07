<?php declare(strict_types=1);
namespace Common\Push;

/**
 * Class Push
 *
 * Base abstract class for push notifications.
 *
 * @package Common\Push
 * @abstract
 */
abstract class Push
{
	/**
	 * Maximum length for a trimmed message.
	 *
	 * @var int
	 */
	protected const MAX_LENGTH = 30;

	/**
	 * Constructor.
	 *
	 * @param object|null $data The notification data.
	 */
	public function __construct(
		protected ?object $data = null
	)
	{
	}

	/**
	 * Retrieves a value from the data object.
	 *
	 * @param string $key The key of the data value.
	 * @return mixed The value associated with the key, or an empty string if not set.
	 */
	protected function get(string $key): mixed
	{
		return $this->data->{$key} ?? '';
	}

	/**
	 * Should be overridden to return the message body.
	 *
	 * @return string|array The message body.
	 */
	abstract protected function setupBody(): string|array;

	/**
	 * Trims the message length for uniform sizing.
	 *
	 * @param string $message The message to trim.
	 * @return string The trimmed message.
	 */
	protected function trimMessage(string $message): string
	{
		if (strlen($message) > self::MAX_LENGTH)
		{
			$message = substr($message, 0, self::MAX_LENGTH) . '...';
		}
		return $message;
	}

	/**
	 * Encodes the notification data or message string.
	 *
	 * @return string The JSON encoded message.
	 */
	protected function prepareMessage(): string
	{
		$body = $this->setupBody();
		if (is_string($body))
		{
			$body = [
				'message' => $body
			];
		}

		$message = (object) $body;
		return json_encode($message);
	}

	/**
	 * Returns the message as a JSON encoded string.
	 *
	 * @return string The JSON encoded message.
	 */
	public function __toString(): string
	{
		return $this->prepareMessage();
	}
}