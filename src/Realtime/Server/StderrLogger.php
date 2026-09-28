<?php declare(strict_types=1);
namespace Proto\Realtime\Server;

use Psr\Log\AbstractLogger;
use Psr\Log\LogLevel;

/**
 * StderrLogger
 *
 * One JSON line per entry on stderr, so `docker logs` shows them.
 *
 * @package Proto\Realtime\Server
 */
final class StderrLogger extends AbstractLogger
{
	/**
	 * @var array<string, int>
	 */
	private const RANK = [
		LogLevel::DEBUG => 0,
		LogLevel::INFO => 1,
		LogLevel::NOTICE => 2,
		LogLevel::WARNING => 3,
		LogLevel::ERROR => 4,
		LogLevel::CRITICAL => 5,
		LogLevel::ALERT => 6,
		LogLevel::EMERGENCY => 7
	];

	/**
	 * @param string $minLevel
	 */
	public function __construct(private readonly string $minLevel = LogLevel::INFO)
	{
	}

	/**
	 * @param mixed $level
	 * @param string|\Stringable $message
	 * @param array<string, mixed> $context
	 * @return void
	 */
	public function log($level, string|\Stringable $message, array $context = []): void
	{
		$level = (string)$level;
		if ((self::RANK[$level] ?? 1) < (self::RANK[$this->minLevel] ?? 1))
		{
			return;
		}

		$line = json_encode([
			'time' => gmdate('c'),
			'level' => $level,
			'message' => (string)$message,
			'context' => $this->scalarContext($context)
		]);

		fwrite(STDERR, ($line === false ? (string)$message : $line) . "\n");
	}

	/**
	 * Amp puts objects (exceptions, addresses) in context; keep it printable.
	 *
	 * @param array<string, mixed> $context
	 * @return array<string, mixed>
	 */
	private function scalarContext(array $context): array
	{
		$out = [];
		foreach ($context as $key => $value)
		{
			$out[$key] = match (true)
			{
				$value instanceof \Throwable => $value->getMessage(),
				$value instanceof \Stringable => (string)$value,
				is_scalar($value) || $value === null => $value,
				default => get_debug_type($value)
			};
		}

		return $out;
	}
}
