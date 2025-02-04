<?php
namespace App\Push;

abstract class Push
{
	/**
	 * This is the notification data.
	 *
	 * @var object|null
	 */
	protected $data;

    /**
     *
     * @param object|null $data
     */
	public function __construct(?object $data = null)
	{
        $this->data = $data;
    }

    /**
     * This will get a value from the data object.
     *
     * @param string $key
     * @return mixed
     */
    protected function get(string $key): mixed
    {
        return $this->data->{$key} ?? '';
    }

    /**
     * This should be overriden to return the message body.
     *
     * @abstract
     * @return string|array
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
        $body = $this->setupBody();
        if (is_string($body))
        {
            $body = [
                'message' => $body
            ];
        }

        $message = (object)$body;
        return json_encode($message);
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