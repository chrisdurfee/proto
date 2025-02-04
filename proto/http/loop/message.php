<?php declare(strict_types=1);
namespace Proto\Http\Loop;

/**
 * Message
 *
 * Represents a message to be sent to the client.
 *
 * @package Proto\Http\Loop
 */
class Message
{
	/**
	 * Constructs a Message instance with the given data and renders it.
	 *
	 * @param mixed $data The data to be sent in the message.
	 * @param bool $formatted Whether or not the data is already formatted.
	 * @return void
	 */
	public function __construct(
		private mixed $data,
		protected bool $formatted = false
	)
	{
		$this->data = $data;
		$this->render();
	}

	/**
	 * Encodes the given data as JSON.
	 *
	 * @param mixed|null $data The data to be encoded.
	 * @return string The JSON-encoded data.
	 */
	public static function json(mixed $data = null): string
	{
		if (!isset($data))
		{
			return '';
		}

		return json_encode($data);
	}

	/**
	 * Displays the message to the client.
	 *
	 * @return void
	 */
	protected function render(): void
	{
		if ($this->formatted)
		{
			echo $this->data;
			return;
		}

		echo "data: " . self::json($this->data) . "\n\n";
	}
}
