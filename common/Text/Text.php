<?php declare(strict_types=1);
namespace Common\Text;

/**
 * Class Text
 *
 * This is the base text template class.
 *
 * @package Common\Text
 * @abstract
 */
abstract class Text
{
	/**
	 * Maximum length for a trimmed message.
	 *
	 * @var int
	 */
	protected const MAX_LENGTH = 30;

	/**
	 * Sets the data object.
	 *
	 * @param object|null $data The data object.
	 * @return void
	 */
	public function __construct(protected ?object $data = null)
	{
	}

	/**
	 * Gets a value from the data object.
	 *
	 * @param string $key The key of the data.
	 * @return mixed The value for the specified key, or null if not set.
	 */
	protected function get(string $key): mixed
	{
		return $this->data->{$key} ?? null;
	}

	/**
	 * Should be overridden to return the message body.
	 *
	 * @return string The message body.
	 */
	abstract protected function setupBody() : string;

	/**
	 * Trims the message length to ensure uniform sizing.
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
	 * @return string The prepared message.
	 */
	protected function prepareMessage(): string
	{
		return $this->setupBody();
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