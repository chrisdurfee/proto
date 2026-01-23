<?php declare(strict_types=1);
namespace Proto\Error
{
	use Proto\Error\Models\ErrorLog;
	use Proto\Http\Request;
	use Proto\Http\Response;
	use Proto\Utils\Format\JsonFormat;

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
		}

		/**
		 * Initializes error tracking if enabled.
		 *
		 * @param bool $silent Whether to suppress output on initialization failure.
		 * @return void
		 */
		protected static function initializeTracking(bool $silent): void
		{
			static::$trackingEnabled = env('errorTracking');
			if (!static::$trackingEnabled)
			{
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
			if ($displayErrors)
			{
				error_reporting(E_ALL);
				ini_set('display_errors', '1');
				ini_set('display_startup_errors', '1');
				return;
			}

			error_reporting(0);
			ini_set('display_errors', '0');
			ini_set('display_startup_errors', '0');
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
			if (!static::shouldHandle())
			{
				return true;
			}

			if (static::isErrorLogTableMissing($errstr))
			{
				return static::handleMissingTable("Error log table missing: $errstr in $errfile:$errline");
			}

			$data = static::buildErrorData($errno, $errstr, $errfile, $errline);
			return static::logError($data);
		}

		/**
		 * Handles exception logging.
		 *
		 * @param \Throwable $exception The exception object.
		 * @return bool Whether the exception was handled.
		 */
		public static function exceptionHandler(\Throwable $exception): bool
		{
			if (!static::shouldHandle())
			{
				return true;
			}

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

		/**
		 * Checks if error handling should proceed.
		 *
		 * @return bool Whether to handle the error.
		 */
		protected static function shouldHandle(): bool
		{
			return !static::$errorLoggingFailed && static::$trackingEnabled;
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
				'errorMessage' => $errstr,
				'errorFile' => $errfile,
				'errorLine' => $errline,
				'errorTrace' => '',
				'backTrace' => JsonFormat::encode(debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS)),
				'env' => env('env'),
				'url' => Request::fullUrlWithScheme(),
				'query' => JsonFormat::encode(Request::all()),
				'errorIp' => Request::ip()
			];
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
				'errorMessage' => $exception->getMessage(),
				'errorFile' => $exception->getFile(),
				'errorLine' => $exception->getLine(),
				'errorTrace' => $exception->getTraceAsString(),
				'backTrace' => JsonFormat::encode(debug_backtrace()),
				'env' => env('env'),
				'url' => Request::fullUrlWithScheme(),
				'query' => JsonFormat::encode(Request::all()),
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
			Response::error(
				'Error tracker error: ' . json_encode($data),
				500
			);

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
			static::setExceptionHandler();
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

				if (!static::shouldHandle())
				{
					return;
				}

				static::errorHandler(
					$err['type'],
					$err['message'],
					$err['file'],
					$err['line']
				);
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
