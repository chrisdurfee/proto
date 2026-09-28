<?php declare(strict_types=1);
namespace Proto\Realtime\Server;

use Proto\Realtime\RealtimeBridge;

/**
 * StreamDescriptor
 *
 * What PHP said a viewer may subscribe to (the authorize answer from
 * `RealtimeBridge::describe()`), validated again on this side so a bad
 * upstream answer can never subscribe to an arbitrary channel.
 *
 * @package Proto\Realtime\Server
 */
final class StreamDescriptor
{
	/**
	 * @var string
	 */
	private const CHANNEL_PATTERN = '/^[a-zA-Z0-9._:\-]+$/';

	/**
	 * @param array<int, string> $channels
	 * @param string $hydrate
	 * @param string $userId
	 * @param string $sessionId
	 * @param string $sseClient
	 */
	public function __construct(
		public readonly array $channels,
		public readonly string $hydrate,
		public readonly string $userId,
		public readonly string $sessionId,
		public readonly string $sseClient
	)
	{
	}

	/**
	 * Parse an authorize body. Returns null unless it is a descriptor
	 * that allows at least one valid channel.
	 *
	 * @param mixed $data Decoded JSON
	 * @return self|null
	 */
	public static function fromArray(mixed $data): ?self
	{
		if (!is_array($data) || ($data['allow'] ?? false) !== true || !is_array($data['channels'] ?? null))
		{
			return null;
		}

		$channels = [];
		foreach ($data['channels'] as $channel)
		{
			if (is_string($channel) && preg_match(self::CHANNEL_PATTERN, $channel) === 1 && !str_starts_with($channel, 'sse:'))
			{
				$channels[] = $channel;
			}
		}

		if ($channels === [])
		{
			return null;
		}

		$hydrate = (string)($data['hydrate'] ?? RealtimeBridge::HYDRATE_VIEWER);
		if (!in_array($hydrate, [RealtimeBridge::HYDRATE_RAW, RealtimeBridge::HYDRATE_SHARED, RealtimeBridge::HYDRATE_VIEWER], true))
		{
			$hydrate = RealtimeBridge::HYDRATE_VIEWER;
		}

		$userId = (string)($data['userId'] ?? 'guest');
		$sseClient = (string)($data['sseClient'] ?? '');

		return new self(
			array_values(array_unique($channels)),
			$hydrate,
			preg_match('/^[A-Za-z0-9_-]{1,64}$/', $userId) === 1 ? $userId : 'guest',
			(string)($data['sessionId'] ?? ''),
			preg_match('/^[A-Za-z0-9_-]{8,64}$/', $sseClient) === 1 ? $sseClient : ''
		);
	}

	/**
	 * True when the viewer is signed in (guests share no kick channel).
	 *
	 * @return bool
	 */
	public function hasUser(): bool
	{
		return $this->userId !== 'guest' && $this->userId !== '';
	}
}
