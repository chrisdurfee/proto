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
	 */
	public function __construct(
		private mixed $data,
		protected bool $formatted = false
	)
	{
		$this->render();
	}

	/**
	 * Encodes the given data as JSON.
	 *
	 * @param mixed $data The data to be encoded.
	 * @return string The JSON-encoded data.
	 */
	public static function json(mixed $data): string
	{
		if (!isset($data))
		{
			return '';
		}

		return json_encode($data ?? [], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
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
			echo (string)$this->data;
		}
		else
		{
			echo "data: " . self::json($this->data) . "\n\n";
		}

		// Use aggressive flushing like StreamResponse
		$this->aggressiveFlush();
	}

	/**
	 * Aggressively flushes all output buffers to ensure real-time delivery.
	 *
	 * @return void
	 */
	protected function aggressiveFlush(): void
	{
		// Only flush if output buffering is active
		$levels = ob_get_level();
		if ($levels > 0)
		{
			// Flush each buffer level with safety limit
			$maxLevels = min($levels, 10); // Prevent infinite loops
			for ($i = 0; $i < $maxLevels; $i++)
			{
				@ob_flush();
			}
		}

		// System flush - always safe to call
		flush();
	}
}