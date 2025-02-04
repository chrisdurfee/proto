<?php declare(strict_types=1);
namespace Proto\Http;

use Proto\Http\Request;

/**
 * Limit
 *
 * This will create a limit.
 *
 * @package Proto\Http
 */
class Limit
{
    /**
     * @var int $expireInSeconds
     */
	protected int $expireInSeconds = 60;

    /**
     * @var string $requestId
     */
    protected string $requestId;

    /**
     * This will set the limit.
     *
     * @param int $requestLimit
     * @return void
     */
    public function __construct(
        protected int $requestLimit = 0
    )
    {
        $this->requestId = $this->getIp();
    }

    /**
     * This will check if the requests are over the limit.
     *
     * @param int $requests
     * @return bool
     */
    public function isOverLimit(int $requests): bool
    {
        if ($this->requestLimit === 0)
        {
            return false;
        }

        return ($requests > $this->requestLimit);
    }

    /**
     * This will set a limit of none.
     *
     * @return Limit
     */
    public static function none(): Limit
    {
        return new static();
    }

    /**
     * This will create a new limit per minute.
     *
     * @param int $requestLimit
     * @return Limit
     */
    public static function perMinute(int $requestLimit): Limit
    {
        return new static($requestLimit);
    }

    /**
     * This will create a new limit per hour.
     *
     * @param int $requestLimit
     * @return Limit
     */
    public static function perHour(int $requestLimit): Limit
    {
        $MINUTES_PER_HOUR = 60;
        $limit = new static($requestLimit);
        return $limit->setTimeLimit($MINUTES_PER_HOUR * 60);
    }

    /**
     * This will create a new limit per day.
     *
     * @param int $requestLimit
     * @return Limit
     */
    public static function perDay(int $requestLimit): Limit
    {
        $MINUTES_PER_DAY = 1440;
        $limit = new static($requestLimit);
        return $limit->setTimeLimit($MINUTES_PER_DAY * 60);
    }

    /**
	 * This will get the request ip.
	 *
	 * @return string|null
	 */
	protected function getIp(): ?string
	{
		return Request::ip();
	}

    /**
     * This will set the time limit.
     *
     * @param int $expireInSeconds
     * @return self
     */
    public function setTimeLimit(int $expireInSeconds): self
    {
        $this->expireInSeconds = $expireInSeconds;

        return $this;
    }

    /**
     * This will get the time limit.
     *
     * @return int
     */
    public function getTimeLimit(): int
    {
        return $this->expireInSeconds;
    }

    /**
     * This will set the request id.
     *
     * @param string $requestId
     * @return self
     */
	public function by(string $requestId): self
    {
        $this->requestId = $requestId;

        return $this;
    }

    /**
     * This will get the request id.
     *
     * @return string|null
     */
    public function id(): ?string
    {
        return $this->requestId;
    }
}
