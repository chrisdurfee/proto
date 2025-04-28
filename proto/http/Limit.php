<?php declare(strict_types=1);
namespace Proto\Http;

/**
 * Class Limit
 *
 * Handles request rate limiting.
 *
 * @package Proto\Http
 */
class Limit
{
	/**
	 * Expiration time in seconds.
	 *
	 * @var int
	 */
	protected int $expireInSeconds = 60;

	/**
	 * Request identifier (IP or custom).
	 *
	 * @var string
	 */
	protected string $requestId;

	/**
	 * Maximum number of requests allowed.
	 *
	 * @var int
	 */
	protected int $requestLimit;

	/**
	 * Limit constructor.
	 *
	 * @param int $requestLimit Number of allowed requests (default: 0 = unlimited).
	 */
	public function __construct(int $requestLimit = 0)
	{
		$this->requestLimit = $requestLimit;
		$this->requestId = self::getIp() ?? 'unknown';
	}

	/**
	 * This will get the request limit.
	 *
	 * @return int
	 */
	public function getRequestLimit(): int
	{
		return $this->requestLimit;
	}

	/**
	 * Checks if the request count exceeds the limit.
	 *
	 * @param int $requests Number of requests made.
	 * @return bool True if over limit, false otherwise.
	 */
	public function isOverLimit(int $requests): bool
	{
		return ($this->requestLimit > 0 && $requests > $this->requestLimit);
	}

	/**
	 * Creates a limit with no restrictions.
	 *
	 * @return self
	 */
	public static function none(): self
	{
		return new self();
	}

	/**
	 * Creates a per-minute limit.
	 *
	 * @param int $requestLimit Number of requests allowed per minute.
	 * @return self
	 */
	public static function perMinute(int $requestLimit): self
	{
		return new self($requestLimit);
	}

	/**
	 * Creates a per-hour limit.
	 *
	 * @param int $requestLimit Number of requests allowed per hour.
	 * @return self
	 */
	public static function perHour(int $requestLimit): self
	{
		return (new self($requestLimit))->setTimeLimit(3600);
	}

	/**
	 * Creates a per-day limit.
	 *
	 * @param int $requestLimit Number of requests allowed per day.
	 * @return self
	 */
	public static function perDay(int $requestLimit): self
	{
		return (new self($requestLimit))->setTimeLimit(86400);
	}

	/**
	 * Retrieves the client's IP address.
	 *
	 * @return string|null IP address or null if not found.
	 */
	protected static function getIp(): ?string
	{
		return Request::ip();
	}

	/**
	 * Sets the time limit for requests.
	 *
	 * @param int $expireInSeconds Time limit in seconds.
	 * @return self
	 */
	public function setTimeLimit(int $expireInSeconds): self
	{
		$this->expireInSeconds = $expireInSeconds;
		return $this;
	}

	/**
	 * Retrieves the time limit.
	 *
	 * @return int Time limit in seconds.
	 */
	public function getTimeLimit(): int
	{
		return $this->expireInSeconds;
	}

	/**
	 * Sets the request identifier (e.g., user ID, IP).
	 *
	 * @param string $requestId Custom request identifier.
	 * @return self
	 */
	public function by(string $requestId): self
	{
		$this->requestId = $requestId;
		return $this;
	}

	/**
	 * Retrieves the request identifier.
	 *
	 * @return string Request identifier.
	 */
	public function id(): string
	{
		return $this->requestId;
	}
}