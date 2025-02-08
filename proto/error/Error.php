<?php declare(strict_types=1);
namespace Proto\Error
{
	use Proto\Models\ErrorLog;
	use Proto\Http\Request;
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
			$backtrace = debug_backtrace();

			return ErrorLog::create((object)[
				'errorNumber' => $errno,
				'errorMessage' => $errstr,
				'errorFile' => $errfile,
				'errorLine' => $errline,
				'errorTrace' => '',
				'backTrace' => JsonFormat::encode($backtrace),
				'env' => env('env'),
				'url' => Request::fullUrlWithScheme(),
				'query' => JsonFormat::encode(Request::all()),
				'errorIp' => Request::ip()
			]);
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
			$backtrace = debug_backtrace();

			return ErrorLog::create((object)[
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
			]);
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