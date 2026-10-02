<?php declare(strict_types=1);
namespace Proto\Realtime\Server;

/**
 * Metrics
 *
 * Counters for `/__realtime/metrics`. One instance per process.
 *
 * @package Proto\Realtime\Server
 */
final class Metrics
{
	public int $connectionsOpen = 0;
	public int $connectionsTotal = 0;
	public int $messagesReceived = 0;
	public int $framesSent = 0;
	public int $slowClientsDropped = 0;
	public int $upstreamErrors = 0;
	public int $hydrateCalls = 0;
	public int $hydrateFailures = 0;
	public int $upstreamInFlight = 0;
	public int $upstreamQueued = 0;
	public int $upstreamQueuedPeak = 0;
	public int $hydrateShed = 0;
	private float $hydrateSeconds = 0.0;
	private float $startedAt;

	public function __construct()
	{
		$this->startedAt = microtime(true);
	}

	/**
	 * @param float $seconds
	 * @return void
	 */
	public function recordHydrate(float $seconds): void
	{
		$this->hydrateCalls++;
		$this->hydrateSeconds += $seconds;
	}

	/**
	 * @param int $channels
	 * @return array<string, int|float>
	 */
	public function snapshot(int $channels): array
	{
		return [
			'uptimeSeconds' => (int)(microtime(true) - $this->startedAt),
			'connectionsOpen' => $this->connectionsOpen,
			'connectionsTotal' => $this->connectionsTotal,
			'channels' => $channels,
			'messagesReceived' => $this->messagesReceived,
			'framesSent' => $this->framesSent,
			'slowClientsDropped' => $this->slowClientsDropped,
			'upstreamErrors' => $this->upstreamErrors,
			'hydrateCalls' => $this->hydrateCalls,
			'hydrateFailures' => $this->hydrateFailures,
			'hydrateAvgMs' => $this->hydrateCalls > 0 ? round(($this->hydrateSeconds / $this->hydrateCalls) * 1000, 1) : 0.0,
			'hydrateShed' => $this->hydrateShed,
			'upstreamInFlight' => $this->upstreamInFlight,
			'upstreamQueued' => $this->upstreamQueued,
			'upstreamQueuedPeak' => $this->upstreamQueuedPeak,
			'memoryBytes' => memory_get_usage(true)
		];
	}
}
