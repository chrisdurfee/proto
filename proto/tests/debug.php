<?php declare(strict_types=1);
namespace Proto\Tests;

/**
 * Debug
 *
 * Handles rendering of debug content for development purposes.
 *
 * @package Proto\Tests
 */
class Debug
{
	/**
	 * Renders content to the screen within a `<pre>` tag.
	 *
	 * @param mixed $content The content to be displayed.
	 * @return void
	 */
	public static function render(mixed $content): void
	{
		echo '<pre>', htmlspecialchars(self::format($content), ENT_QUOTES, 'UTF-8'), '</pre>';
	}

	/**
	 * Formats content for debug output.
	 *
	 * @param mixed $content The content to be formatted.
	 * @return string Formatted debug output.
	 */
	protected static function format(mixed $content): string
	{
		ob_start();
		var_dump($content);
		return ob_get_clean() ?: 'Debugging failed.';
	}

	/**
	 * Logs debug content to a file instead of rendering.
	 *
	 * @param mixed $content The content to log.
	 * @param string $filePath The log file path.
	 * @return void
	 */
	public static function log(mixed $content, string $filePath = '/tmp/debug.log'): void
	{
		$logMessage = "[" . date('Y-m-d H:i:s') . "] " . self::format($content) . PHP_EOL;
		file_put_contents($filePath, $logMessage, FILE_APPEND | LOCK_EX);
	}

	/**
	 * Dumps content and stops execution.
	 *
	 * @param mixed $content The content to display.
	 * @return never
	 */
	public static function dumpAndExit(mixed $content): never
	{
		self::render($content);
		exit;
	}
}