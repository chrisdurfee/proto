<?php declare(strict_types=1);
namespace Proto\Jobs\Events;

use Proto\Base;

/**
 * JobEvent
 *
 * Event class for job-related events.
 *
 * @package Proto\Jobs\Events
 */
class JobEvent extends Base
{
	/**
	 * @var float $timestamp Event timestamp
	 */
	public float $timestamp;

	/**
	 * Constructor.
	 *
	 * @param string $type Event type
	 * @param array $data Event data
	 */
	public function __construct(
        public string $type,
        public array $data = []
    )
	{
		parent::__construct();
		$this->timestamp = microtime(true);
	}

	/**
	 * Get event type.
	 *
	 * @return string
	 */
	public function getType(): string
	{
		return $this->type;
	}

	/**
	 * Get event data.
	 *
	 * @return array
	 */
	public function getData(): array
	{
		return $this->data;
	}

	/**
	 * Get event timestamp.
	 *
	 * @return float
	 */
	public function getTimestamp(): float
	{
		return $this->timestamp;
	}

	/**
	 * Get data value by key.
	 *
	 * @param string $key Data key
	 * @param mixed $default Default value
	 * @return mixed
	 */
	public function get(string $key, mixed $default = null): mixed
	{
		return $this->data[$key] ?? $default;
	}

	/**
	 * Set data value.
	 *
	 * @param string $key Data key
	 * @param mixed $value Data value
	 * @return self
	 */
	public function set(string $key, mixed $value): self
	{
		$this->data[$key] = $value;
		return $this;
	}

	/**
	 * Check if data key exists.
	 *
	 * @param string $key Data key
	 * @return bool
	 */
	public function has(string $key): bool
	{
		return isset($this->data[$key]);
	}
}
