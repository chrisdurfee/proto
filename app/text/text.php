<?php declare(strict_types=1);
namespace App\Text;

/**
 * Text
 *
 * This is the base text template class.
 *
 * @abstract
 */
abstract class Text
{
	/**
     * This will set the data object.
     *
     * @param object|null $data
     * @return void
     */
	public function __construct(
        protected ?object $data = null
    )
	{
    }

    /**
     * This will get a value from the data object.
     *
     * @param string $key
     * @return mixed
     */
    protected function get(string $key): mixed
    {
        return $this->data->{$key} ?? null;
    }

    /**
     * This should be overriden to return the message body.
     *
     * @abstract
     * @return string
     */
    abstract protected function setupBody();

    /**
	 * This trims the message length to fit nicely and insure
     * uniform sizing.
	 *
	 * @param string $message
	 * @return string
	 */
	protected function trimMessage(string $message): string
	{
		$MAX_LENGTH = 30;
		if (strlen($message) > $MAX_LENGTH)
		{
			$message = substr($message, 0, $MAX_LENGTH) . '...';
		}

		return $message;
	}

    /**
     * this will encode our notification data or message string.
     *
     * @return string
     */
    protected function prepareMessage(): string
    {
        return $this->setupBody();
    }

    /**
     * This will override the to string method to return the
     * message as a json encoded string.
     *
     * @return string
     */
    public function __toString(): string
    {
        return $this->prepareMessage();
    }
}