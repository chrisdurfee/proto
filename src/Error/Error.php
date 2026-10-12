<?php declare(strict_types=1);
namespace Proto\Error
{
	use Proto\Database\DatabaseUnavailableException;
	use Proto\Error\Models\ErrorLog;
	use Proto\Http\Request;
	use Proto\Http\Response;
	use Proto\Utils\Format\JsonFormat;
	use Proto\Cache\Cache;

	/**
	 * Class Error
	 *
	 * Handles error reporting and exception handling.
	 *
	 * @package Proto\Error
	 */
	class Error
	{
		/**
		 * Flag to prevent infinite loops when error logging fails.
		 *
		 * @var bool
		 */
		private static bool $errorLoggingFailed = false;

		/**
		 * Flag to track if we've already tried to check database connectivity.
		 *
		 * @var bool
		 */
		private static bool $databaseChecked = false;

		/**
		 * Whether error tracking is enabled.
		 *
		 * @var bool
		 */
		private static bool $trackingEnabled = false;

		/**
		 * Whether error tracking has been manually disabled.
		 *
		 * @var bool
		 */
		private static bool $manuallyDisabled = false;

		/**
		 * Whether silent mode is enabled (log to database only, no output).
		 *
		 * @var bool
		 */
		private static bool $silentMode = false;

		/**
		 * Flag to prevent re-entry into error handler while processing.
		 *
		 * @var bool
		 */
		private static bool $isHandling = false;

		/**
		 * Whether error details may be shown to the client (dev only).
		 *
		 * @var bool
		 */
		private static bool $displayErrors = false;

		/**
		 * Whether the fatal-error shutdown handler has been registered.
		 *
		 * @var bool
		 */
		private static bool $shutdownHandlerInstalled = false;

		/**
		 * Throttle window in seconds for identical errors.
		 *
		 * @var int
		 */
		private const THROTTLE_WINDOW = 60;

		/**
		 * Maximum identical errors logged per throttle window.
		 *
		 * @var int
		 */
		private const THROTTLE_LIMIT = 5;

		/**
		 * Checks if a message indicates the error log table is missing.
		 *
		 * @param string $message The error message to check.
		 * @return bool Whether the message indicates the error log table is missing.
		 */
		protected static function isErrorLogTableMissing(string $message): bool
		{
			return str_contains($message, "proto_error_log") && str_contains($message, "doesn't exist");
		}

		/**
		 * Enables error tracking with optional error display.
		 *
		 * @param bool $displayErrors Whether to display errors.
		 * @return void
		 */
		public static function enable(bool $displayErrors = false): void
		{
			static::$silentMode = false;
			static::setErrorReporting($displayErrors);
			static::resetState();

			if (static::$manuallyDisabled)
			{
				return;
			}

			static::initializeTracking(false);
		}

		/**
		 * Disables error tracking completely.
		 *
		 * @return void
		 */
		public static function disable(): void
		{
			static::$trackingEnabled = false;
			static::$manuallyDisabled = true;
			static::$silentMode = false;

			restore_error_handler();
			restore_exception_handler();

			ini_set('log_errors', '0');
		}

		/**
		 * Enables silent mode - errors are logged to database but not displayed.
		 * This keeps error tracking active while suppressing all screen output
		 * and preventing script termination on errors.
		 *
		 * @return void
		 */
		public static function silent(): void
		{
			static::$silentMode = true;
			static::$manuallyDisabled = false;
			static::setErrorReporting(false);
			static::resetState();
			static::initializeTracking(true);
		}

		/**
		 * Resets internal state flags.
		 *
		 * @return void
		 */
		protected static function resetState(): void
		{
			static::$errorLoggingFailed = false;
			static::$databaseChecked = false;
			static::$isHandling = false;
		}

		/**
		 * Initializes error tracking if enabled.
		 *
		 * @param bool $silent Whether to suppress output on initialization failure.
		 * @return void
		 */
		protected static function initializeTracking(bool $silent): void
		{
			static::$trackingEnabled = (bool)env('errorTracking');
			if (!static::$trackingEnabled)
			{
				// No database logging, but uncaught errors must still get a
				// JSON 500 and reach the PHP error log.
				static::installResponseHandlers();
				return;
			}

			if (!static::$databaseChecked && !static::isDatabaseAvailable())
			{
				static::$errorLoggingFailed = true;
				static::$databaseChecked = true;

				if (!$silent)
				{
					static::outputError("Error tracking disabled - database tables not available");
				}
				return;
			}

			static::trackErrors();
		}

		/**
		 * Sets the app's error reporting level.
		 *
		 * @param bool $displayErrors Whether to display errors.
		 * @return void
		 */
		protected static function setErrorReporting(bool $displayErrors): void
		{
			static::$displayErrors = $displayErrors;
			if ($displayErrors)
			{
				error_reporting(E_ALL);
				ini_set('display_errors', '1');
				ini_set('display_startup_errors', '1');
				return;
			}

			/**
			 * Never display errors outside dev, but keep reporting and
			 * logging them: `error_reporting(0)` used to hide every
			 * warning and fatal from the PHP error log as well.
			 */
			error_reporting(E_ALL & ~E_DEPRECATED & ~E_USER_DEPRECATED);
			ini_set('display_errors', '0');
			ini_set('display_startup_errors', '0');
			ini_set('log_errors', '1');
		}

		/**
		 * Handles error logging.
		 *
		 * @param int $errno Error number.
		 * @param string $errstr Error message.
		 * @param string $errfile File where the error occurred.
		 * @param int $errline Line number where the error occurred.
		 * @return bool Whether the error was handled.
		 */
		public static function errorHandler(
			int $errno,
			string $errstr,
			string $errfile,
			int $errline
		): bool
		{
			// Honor error_reporting() (and the @ operator) so notices and
			// deprecations excluded in production do not become log rows.
			if ($errno > 0 && !(error_reporting() & $errno))
			{
				return false;
			}

			if (!static::shouldHandle())
			{
				return true;
			}

			// Skip "headers already sent" errors caused by our own error output
			if (str_contains($errstr, 'Cannot modify header information'))
			{
				return true;
			}

			// Set re-entry guard
			static::$isHandling = true;

			try
			{
				if (static::isErrorLogTableMissing($errstr))
				{
					return static::handleMissingTable("Error log table missing: $errstr in $errfile:$errline");
				}

				$data = static::buildErrorData($errno, $errstr, $errfile, $errline);
				return static::logError($data);
			}
			finally
			{
				static::$isHandling = false;
			}
		}

		/**
		 * Handles an uncaught exception: reports it, then renders a
		 * generic JSON error response (503 when the database is down,
		 * otherwise 500). Details are only included in dev.
		 *
		 * @param \Throwable $exception The exception object.
		 * @return bool Whether the exception was handled.
		 */
		public static function exceptionHandler(\Throwable $exception): bool
		{
			static::reportException($exception);
			static::renderServerError($exception);
			return true;
		}

		/**
		 * Reports an exception without producing any output: writes it to
		 * the error log table when tracking is on, otherwise to the PHP
		 * error log.
		 *
		 * @param \Throwable $exception The exception object.
		 * @return bool Whether the exception was handled.
		 */
		public static function reportException(\Throwable $exception): bool
		{
			if (!static::shouldHandle())
			{
				if (!static::$isHandling && PHP_SAPI !== 'cli')
				{
					error_log(static::describeException($exception));
				}
				return true;
			}

			// Set re-entry guard
			static::$isHandling = true;

			try
			{
				if (static::isErrorLogTableMissing($exception->getMessage()))
				{
					return static::handleMissingTable(
						"Error log table missing exception: " . $exception->getMessage() .
						" in " . $exception->getFile() . ":" . $exception->getLine()
					);
				}

				$data = static::buildExceptionData($exception);
				return static::logError($data);
			}
			finally
			{
				static::$isHandling = false;
			}
		}

		/**
		 * One-line, path-redacted description of an exception for logs.
		 *
		 * @param \Throwable $exception
		 * @return string
		 */
		protected static function describeException(\Throwable $exception): string
		{
			return 'Uncaught ' . $exception::class . ': '
				. static::sanitizeErrorMessage($exception->getMessage())
				. ' in ' . static::redactPath($exception->getFile()) . ':' . $exception->getLine();
		}

		/**
		 * Renders a generic JSON error response for an unhandled failure.
		 *
		 * Outside dev the body never includes the exception message, SQL,
		 * paths, or traces. In CLI processes (workers, realtime server,
		 * tests) a one-line description goes to STDERR instead.
		 *
		 * @param \Throwable|null $exception The failure, or null for a fatal error.
		 * @return void
		 */
		public static function renderServerError(?\Throwable $exception = null): void
		{
			if (static::isCli())
			{
				if ($exception !== null && defined('STDERR'))
				{
					fwrite(STDERR, static::describeException($exception) . PHP_EOL);
				}
				return;
			}

			if (static::headersSent())
			{
				return;
			}

			static::clearOutputBuffers();

			$unavailable = $exception instanceof DatabaseUnavailableException;
			$status = $unavailable ? 503 : 500;
			$message = $unavailable
				? 'The service is temporarily unavailable. Please try again shortly.'
				: 'An unexpected error occurred. Please try again.';

			if (static::$displayErrors && $exception !== null)
			{
				$message .= ' [' . $exception::class . ': ' . $exception->getMessage() . ']';
			}

			http_response_code($status);
			header('Content-Type: application/json');
			header('Cache-Control: no-store');
			if ($unavailable)
			{
				header('Retry-After: 5');
			}

			echo json_encode(['success' => false, 'message' => $message]);
		}

		/**
		 * Whether this is a CLI process (worker, realtime server, tests).
		 *
		 * @return bool
		 */
		protected static function isCli(): bool
		{
			return PHP_SAPI === 'cli' || PHP_SAPI === 'phpdbg';
		}

		/**
		 * Whether response headers have already gone out.
		 *
		 * @return bool
		 */
		protected static function headersSent(): bool
		{
			return headers_sent();
		}

		/**
		 * Discards partial output so the error body is valid JSON.
		 *
		 * @return void
		 */
		protected static function clearOutputBuffers(): void
		{
			while (ob_get_level() > 0)
			{
				ob_end_clean();
			}
		}

		/**
		 * Checks if error handling should proceed.
		 *
		 * @return bool Whether to handle the error.
		 */
		protected static function shouldHandle(): bool
		{
			return !static::$isHandling && !static::$errorLoggingFailed && static::$trackingEnabled;
		}

		/**
		 * Handles missing error log table scenario.
		 *
		 * @param string $message The error message.
		 * @return bool Always returns true.
		 */
		protected static function handleMissingTable(string $message): bool
		{
			static::$errorLoggingFailed = true;

			if (!static::$silentMode)
			{
				static::outputError($message);
			}

			return true;
		}

		/**
		 * Strips the absolute BASE_PATH prefix from a file path to avoid
		 * disclosing the server's directory structure in error logs.
		 *
		 * @param string $path Absolute file path.
		 * @return string Relative path or the original if BASE_PATH is not defined.
		 */
		protected static function redactPath(string $path): string
		{
			if (!defined('BASE_PATH'))
			{
				return $path;
			}

			$base = rtrim(BASE_PATH, '\/') . DIRECTORY_SEPARATOR;
			if (str_starts_with($path, $base))
			{
				return substr($path, strlen($base));
			}

			return $path;
		}

		/**
		 * Builds error data object for logging.
		 *
		 * @param int $errno Error number.
		 * @param string $errstr Error message.
		 * @param string $errfile File where the error occurred.
		 * @param int $errline Line number where the error occurred.
		 * @return object The error data object.
		 */
		protected static function buildErrorData(
			int $errno,
			string $errstr,
			string $errfile,
			int $errline
		): object
		{
			return (object)[
				'errorNumber' => $errno,
				'errorMessage' => static::sanitizeErrorMessage($errstr),
				'errorFile' => static::redactPath($errfile),
				'errorLine' => $errline,
				'errorTrace' => '',
				'backTrace' => static::redactBacktrace(debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS)),
				'env' => env('env'),
				'url' => static::sanitizeUrl(Request::fullUrlWithScheme()),
				'query' => JsonFormat::encode(static::redactRequestData(Request::all())),
				'errorIp' => Request::ip()
			];
		}

		/**
		 * Redacts file paths from a backtrace array and encodes to JSON.
		 *
		 * @param array $trace The debug_backtrace() result.
		 * @return string JSON-encoded redacted backtrace.
		 */
		protected static function redactBacktrace(array $trace): string
		{
			foreach ($trace as &$frame)
			{
				if (isset($frame['file']))
				{
					$frame['file'] = static::redactPath($frame['file']);
				}
			}

			return JsonFormat::encode($trace) ?: '[]';
		}

		/**
		 * Sanitizes an error message to remove potential credentials or secrets.
		 *
		 * Masks SMTP authentication strings, passwords, and connection strings.
		 *
		 * @param string $message The error message.
		 * @return string The sanitized message.
		 */
		protected static function sanitizeErrorMessage(string $message): string
		{
			// Mask SMTP authentication details
			$message = preg_replace(
				'/AUTH\s+(LOGIN|PLAIN|CRAM-MD5)\s+[A-Za-z0-9+\/=]+/i',
				'AUTH $1 [REDACTED]',
				$message
			) ?? $message;

			// Mask password-like patterns in connection strings
			$message = preg_replace(
				'/password[\s]*[=:]\s*[^\s,;]+/i',
				'password=[REDACTED]',
				$message
			) ?? $message;

			return $message;
		}

		/**
		 * Redacts sensitive keys from request payload logged with errors.
		 *
		 * Error rows store `Request::all()`, which includes login and form
		 * bodies. Without this, a password submitted on a failing request
		 * was persisted in `proto_error_log.query`.
		 *
		 * @param mixed $data
		 * @return mixed
		 */
		protected static function redactRequestData(mixed $data): mixed
		{
			if (is_array($data))
			{
				$out = [];
				foreach ($data as $key => $value)
				{
					if (is_string($key) && static::isSensitiveRequestKey($key))
					{
						$out[$key] = '[REDACTED]';
						continue;
					}

					$out[$key] = static::redactRequestData($value);
				}

				return $out;
			}

			if (is_object($data))
			{
				$out = new \stdClass();
				foreach (get_object_vars($data) as $key => $value)
				{
					if (static::isSensitiveRequestKey($key))
					{
						$out->$key = '[REDACTED]';
						continue;
					}

					$out->$key = static::redactRequestData($value);
				}

				return $out;
			}

			return $data;
		}

		/**
		 * Whether a request key should be stripped from error logs.
		 *
		 * @param string $key
		 * @return bool
		 */
		protected static function isSensitiveRequestKey(string $key): bool
		{
			$exact = [
				'password' => true,
				'passwordHash' => true,
				'passwd' => true,
				'pwd' => true,
				'secret' => true,
				'token' => true,
				'apiKey' => true,
				'apikey' => true,
				'authorization' => true,
				'creditCard' => true,
				'cardNumber' => true,
				'cvv' => true,
				'cvc' => true,
				'ssn' => true,
				'privateKey' => true,
				'accessToken' => true,
				'refreshToken' => true,
				'clientSecret' => true,
			];

			if (isset($exact[$key]))
			{
				return true;
			}

			$lower = strtolower($key);
			foreach (['password', 'passwd', 'secret', 'token', 'apikey', 'authorization', 'creditcard', 'cardnumber', 'privatekey'] as $needle)
			{
				if (str_contains($lower, $needle))
				{
					return true;
				}
			}

			return false;
		}

		/**
		 * Redacts sensitive query-string values from a logged URL.
		 *
		 * @param string $url
		 * @return string
		 */
		protected static function sanitizeUrl(string $url): string
		{
			$parts = parse_url($url);
			if ($parts === false)
			{
				return $url;
			}

			if (isset($parts['query']) && $parts['query'] !== '')
			{
				parse_str($parts['query'], $query);
				$parts['query'] = http_build_query(static::redactRequestData($query));
			}

			$scheme = isset($parts['scheme']) ? $parts['scheme'] . '://' : '';
			$user = $parts['user'] ?? '';
			$auth = ($user !== '' || isset($parts['pass']))
				? $user . (isset($parts['pass']) ? ':[REDACTED]' : '') . '@'
				: '';
			$host = $parts['host'] ?? '';
			$port = isset($parts['port']) ? ':' . $parts['port'] : '';
			$path = $parts['path'] ?? '';
			$queryString = isset($parts['query']) && $parts['query'] !== '' ? '?' . $parts['query'] : '';
			$fragment = isset($parts['fragment']) ? '#' . $parts['fragment'] : '';

			return $scheme . $auth . $host . $port . $path . $queryString . $fragment;
		}

		/**
		 * Builds exception data object for logging.
		 *
		 * @param \Throwable $exception The exception object.
		 * @return object The exception data object.
		 */
		protected static function buildExceptionData(\Throwable $exception): object
		{
			return (object)[
				'errorNumber' => $exception->getCode(),
				'errorMessage' => static::sanitizeErrorMessage($exception->getMessage()),
				'errorFile' => static::redactPath($exception->getFile()),
				'errorLine' => $exception->getLine(),
				'errorTrace' => static::redactPath($exception->getTraceAsString()),
				'backTrace' => static::redactBacktrace(debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS)),
				'env' => env('env'),
				'url' => static::sanitizeUrl(Request::fullUrlWithScheme()),
				'query' => JsonFormat::encode(static::redactRequestData(Request::all())),
				'errorIp' => Request::ip()
			];
		}

		/**
		 * Logs error data to the database.
		 *
		 * @param object $data The error data to log.
		 * @return bool Whether the error was logged successfully.
		 */
		protected static function logError(object $data): bool
		{
			if (static::isThrottled($data))
			{
				return true;
			}

			try
			{
				return ErrorLog::create($data);
			}
			catch (\Throwable $e)
			{
				return static::handleLoggingFailure($e, $data);
			}
		}

		/**
		 * Determines whether an identical error should be throttled to protect
		 * the database from error storms.
		 *
		 * Uses the cache as a cross-request fixed-window counter keyed by an
		 * error fingerprint (number + file + line + message). Up to
		 * THROTTLE_LIMIT identical errors are logged per THROTTLE_WINDOW
		 * seconds; further duplicates are suppressed. Degrades gracefully:
		 * when the cache is unavailable or any failure occurs, throttling is
		 * skipped so genuine errors are never silently dropped.
		 *
		 * @param object $data The error data.
		 * @return bool True when the error should be suppressed.
		 */
		protected static function isThrottled(object $data): bool
		{
			try
			{
				if (!Cache::isSupported())
				{
					return false;
				}

				$key = 'error-log:' . static::getErrorFingerprint($data);
				$count = Cache::incr($key);
				if ($count <= 1)
				{
					// First occurrence in the window: set TTL and allow logging.
					Cache::set($key, '1', static::THROTTLE_WINDOW);
					return false;
				}

				return $count > static::THROTTLE_LIMIT;
			}
			catch (\Throwable $e)
			{
				return false;
			}
		}

		/**
		 * Builds a stable fingerprint identifying a unique error occurrence.
		 *
		 * @param object $data The error data.
		 * @return string
		 */
		protected static function getErrorFingerprint(object $data): string
		{
			$signature = ($data->errorNumber ?? '') . '|'
				. ($data->errorFile ?? '') . '|'
				. ($data->errorLine ?? '') . '|'
				. ($data->errorMessage ?? '');

			return hash('xxh3', $signature);
		}

		/**
		 * Handles failure to log an error.
		 *
		 * @param \Throwable $e The exception that occurred during logging.
		 * @param object $data The original error data.
		 * @return bool Always returns true.
		 */
		protected static function handleLoggingFailure(\Throwable $e, object $data): bool
		{
			static::$errorLoggingFailed = true;

			if (static::isErrorLogTableMissing($e->getMessage()))
			{
				if (!static::$silentMode)
				{
					static::outputError("Error log table missing: " . $e->getMessage());
				}
				return true;
			}

			if (!static::$silentMode)
			{
				static::outputErrorData($data);
			}

			return true;
		}

		/**
		 * Outputs an error message and terminates the script.
		 *
		 * @param string $message The error message.
		 * @return void
		 */
		protected static function outputError(string $message): void
		{
			error_log($message);

			if (ob_get_level())
			{
				ob_clean();
			}

			Response::error(
				'Database Configuration Error: The application cannot continue because required database tables are missing. Please contact your system administrator to resolve this issue.',
				500
			);

			die();
		}

		/**
		 * Outputs error data and terminates the script.
		 *
		 * @param object $data The error data to output.
		 * @return void
		 */
		protected static function outputErrorData(object $data): void
		{
			/**
			 * The error data holds the raw message (SQL text, credentials
			 * in driver errors), file paths, the URL, and the client IP.
			 * It goes to the server log only; the client gets a generic
			 * body unless this is dev.
			 */
			error_log('Error tracker could not write the error log row: ' . json_encode($data));

			$message = static::$displayErrors
				? 'Error tracker error: ' . json_encode($data)
				: 'An unexpected error occurred. Please try again.';

			Response::error($message, 500);

			die();
		}

		/**
		 * Resets the error logging failed flag.
		 *
		 * @return void
		 */
		public static function resetErrorLogging(): void
		{
			static::resetState();
		}

		/**
		 * Checks if the database and required tables are available.
		 *
		 * @return bool Whether the database is available for error logging.
		 */
		protected static function isDatabaseAvailable(): bool
		{
			try
			{
				return class_exists(ErrorLog::class) && method_exists(ErrorLog::class, 'create');
			}
			catch (\Throwable $e)
			{
				return false;
			}
		}

		/**
		 * Tracks errors by setting error handlers.
		 *
		 * @return void
		 */
		protected static function trackErrors(): void
		{
			$env = env('env');

			if ($env !== 'prod' && !static::$silentMode)
			{
				static::setErrorLogging();
			}

			static::setErrorHandler();
			static::installResponseHandlers();
		}

		/**
		 * Installs the exception and fatal-shutdown handlers that turn an
		 * unhandled failure into a JSON error response. The shutdown
		 * function is registered once per process.
		 *
		 * @return void
		 */
		protected static function installResponseHandlers(): void
		{
			static::setExceptionHandler();
			if (static::$shutdownHandlerInstalled)
			{
				return;
			}

			static::$shutdownHandlerInstalled = true;
			static::setShutdownHandler();
		}

		/**
		 * Sets the shutdown handler.
		 *
		 * @return void
		 */
		protected static function setShutdownHandler(): void
		{
			register_shutdown_function(function(): void
			{
				$err = error_get_last();
				if (!$err || !in_array($err['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR]))
				{
					return;
				}

				if (static::shouldHandle())
				{
					static::errorHandler(
						$err['type'],
						$err['message'],
						$err['file'],
						$err['line']
					);
				}

				static::renderServerError();
			});
		}

		/**
		 * Enables error logging to file.
		 *
		 * @return void
		 */
		protected static function setErrorLogging(): void
		{
			ini_set('log_errors', '1');
			ini_set('error_log', rtrim(sys_get_temp_dir(), DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . 'proto_error.log');
		}

		/**
		 * Returns the error handler callback.
		 *
		 * @return callable
		 */
		protected static function getErrorCallBack(): callable
		{
			return static fn(int $errno, string $errstr, string $errfile, int $errline): bool
				=> static::errorHandler($errno, $errstr, $errfile, $errline);
		}

		/**
		 * Sets the error handler.
		 *
		 * @return void
		 */
		public static function setErrorHandler(): void
		{
			set_error_handler(static::getErrorCallBack());
		}

		/**
		 * Returns the exception handler callback.
		 *
		 * @return callable
		 */
		protected static function getExceptionCallBack(): callable
		{
			return static fn(\Throwable $exception): bool
				=> static::exceptionHandler($exception);
		}

		/**
		 * Sets the exception handler.
		 *
		 * @return void
		 */
		public static function setExceptionHandler(): void
		{
			set_exception_handler(static::getExceptionCallBack());
		}
	}
}

namespace
{
	use Proto\Error\Error;

	/**
	 * Global function to log errors.
	 *
	 * @param string $errstr Error message.
	 * @param string $errfile File where the error occurred.
	 * @param int $errline Line number where the error occurred.
	 * @param int $errno Error number.
	 * @return bool Whether the error was logged successfully.
	 */
	function error(
		string $errstr,
		string $errfile = '',
		int $errline = -1,
		int $errno = -1
	): bool {
		return Error::errorHandler(
			$errno,
			$errstr,
			$errfile,
			$errline
		);
	}
}
