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
		 * Enables or disables displaying errors.
		 *
		 * @param bool $displayErrors Whether to display errors.
		 * @return void
		 */
		public static function enable(bool $displayErrors = false): void
		{
			static::setErrorReporting($displayErrors);

			if (env('errorTracking'))
			{
				// Test database connectivity before enabling error tracking
				if (!static::$databaseChecked && !static::isDatabaseAvailable())
				{
					static::$errorLoggingFailed = true;
					static::$databaseChecked = true;
					static::failDatabaseUnavailable("Error tracking disabled - database tables not available");
				}

				static::trackErrors();
			}
		}

		/**
		 * Sets the app's error reporting level.
		 *
		 * @param bool $displayErrors Whether to display errors.
		 * @return void
		 */
		protected static function setErrorReporting(bool $displayErrors): void
		{
			if (!$displayErrors)
			{
				error_reporting(0);
				return;
			}

			error_reporting(E_ALL);
			ini_set('display_errors', '1');
			ini_set('display_startup_errors', '1');
		}

		/**
		 * Handles error logging.
		 *
		 * @param int $errno Error number.
		 * @param string $errstr Error message.
		 * @param string $errfile File where the error occurred.
		 * @param int $errline Line number where the error occurred.
		 * @return bool Whether the error was logged successfully.
		 */
		public static function errorHandler(
			int $errno,
			string $errstr,
			string $errfile,
			int $errline
		): bool
		{
			// Prevent infinite loops if error logging has already failed
			if (static::$errorLoggingFailed)
			{
				return false;
			}

			// Check if this is the error log table missing - this is the only table we care about for error logging
			if (static::isErrorLogTableMissing($errstr))
			{
				static::$errorLoggingFailed = true;
				// Error log table is missing - this is a fatal configuration issue for error tracking
				static::failDatabaseUnavailable("Error log table missing: $errstr in $errfile:$errline");
			}

			$data = (object)[
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

			try
			{
				return ErrorLog::create($data);
			}
			catch (\Throwable $e)
			{
				static::$errorLoggingFailed = true;
				// Check if this is the error log table missing - this is the only table we care about for error logging
				if (static::isErrorLogTableMissing($e->getMessage()))
				{
					static::failDatabaseUnavailable("Error log table missing: " . $e->getMessage());
				}
				static::fail($data);
				return false;
			}
		}

		/**
		 * Outputs debug information and terminates the script.
		 *
		 * @param object $data The data to output.
		 * @return void
		 */
		protected static function fail(object $data): void
		{
			echo '<pre>';
			var_dump($data);
			die;
		}

		/**
		 * Handles database unavailable errors and terminates the script.
		 *
		 * @param string $message The error message to display.
		 * @return void
		 */
		protected static function failDatabaseUnavailable(string $message): void
		{
			// Log to PHP error log first
			error_log($message);

			// Clear any output that might have been started
			if (ob_get_level())
			{
				ob_clean();
			}

			// Send JSON error response using Proto Response class
			Response::error(
				'Database Configuration Error: The application cannot continue because required database tables are missing. Please contact your system administrator to resolve this issue.',
				500
			);

			// Terminate the application
			die();
		}

		/**
		 * Resets the error logging failed flag.
		 * Call this after fixing database issues to re-enable error logging.
		 *
		 * @return void
		 */
		public static function resetErrorLogging(): void
		{
			static::$errorLoggingFailed = false;
			static::$databaseChecked = false;
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
				// Check if ErrorLog class exists and has the create method
				if (!class_exists(ErrorLog::class) || !method_exists(ErrorLog::class, 'create'))
				{
					return false;
				}

				// Simply return true for now - we'll catch actual database errors during logging
				// The real protection happens in errorHandler and exceptionHandler methods
				return true;
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

			// Disable error logs in production
			if ($env !== 'prod')
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
				if ($err && in_array($err['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR]))
				{
					static::errorHandler(
						$err['type'],
						$err['message'],
						$err['file'],
						$err['line']
					);
				}
			});
		}

		/**
		 * Enables error logging.
		 *
		 * @return void
		 */
		protected static function setErrorLogging(): void
		{
			ini_set('log_errors', '1');
			ini_set('error_log', 'error.log');
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
		 * Handles exception logging.
		 *
		 * @param \Throwable $exception The exception object.
		 * @return bool Whether the exception was logged successfully.
		 */
		public static function exceptionHandler(\Throwable $exception): bool
		{
			// Prevent infinite loops if error logging has already failed
			if (static::$errorLoggingFailed)
			{
				return false;
			}

			// Check if this is the error log table missing to prevent infinite loops
			if (static::isErrorLogTableMissing($exception->getMessage()))
			{
				static::$errorLoggingFailed = true;
				// Error log table is missing - this is a fatal configuration issue for error tracking
				static::failDatabaseUnavailable("Error log table missing exception: " . $exception->getMessage() . " in " . $exception->getFile() . ":" . $exception->getLine());
			}

			$backtrace = debug_backtrace();
			$data = (object)[
				'errorNumber' => $exception->getCode(),
				'errorMessage' => $exception->getMessage(),
				'errorFile' => $exception->getFile(),
				'errorLine' => $exception->getLine(),
				'errorTrace' => $exception->getTraceAsString(),
				'backTrace' => JsonFormat::encode($backtrace),
				'env' => env('env'),
				'url' => Request::fullUrlWithScheme(),
				'query' => JsonFormat::encode(Request::all()),
				'errorIp' => Request::ip()
			];

			try
			{
				return ErrorLog::create($data);
			}
			catch (\Throwable $e)
			{
				static::$errorLoggingFailed = true;
				// Check if this is the error log table missing - this is the only table we care about for error logging
				if (static::isErrorLogTableMissing($e->getMessage()))
				{
					static::failDatabaseUnavailable("Error log table missing: " . $e->getMessage());
				}
				static::fail($data);
				return false;
			}
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