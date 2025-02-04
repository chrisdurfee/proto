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
		 * Enable or disable displaying errors.
		 *
		 * @param bool $displayErrors
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
		 * Set the app error reporting.
		 *
		 * @param bool $displayErrors
		 * @return void
		 */
		protected static function setErrorReporting(bool $displayErrors): void
		{
			if ($displayErrors === false)
			{
				error_reporting(0);
				return;
			}

			error_reporting(E_ALL);
			ini_set('display_errors', '1');
			ini_set('display_startup_errors', '1');
		}

		/**
		 * Handle the error reporting.
		 *
		 * @param int $errno
		 * @param string $errstr
		 * @param string $errfile
		 * @param int $errline
		 * @return bool
		 */
		public static function errorHandler(
			int $errno,
			string $errstr,
			string $errfile,
			int $errline
		): bool
		{
			return ErrorLog::create((object)[
				'errorNumber' => $errno,
				'errorMessage' => $errstr,
				'errorFile' => $errfile,
				'errorLine' => $errline,
				'errorTrace' => '',
				'backTrace' => JsonFormat::encode(debug_backtrace()),
				'env' => env('env'),
				'url' => Request::fullUrlWithScheme(),
				'query' => JsonFormat::encode(Request::all()),
				'errorIp' => Request::ip()
			]);
		}

		/**
		 * Track errors.
		 *
		 * @return void
		 */
		protected static function trackErrors(): void
		{
			/**
			 * Prevent error log files from
			 * being created on the production server.
			 */
			if (env('env') !== 'prod')
			{
				static::setErrorLogging();
			}

			static::setErrorHandler();
			static::setExceptionHandler();
		}

		/**
		 * Set error logging.
		 *
		 * @return void
		 */
		protected static function setErrorLogging(): void
		{
			ini_set('log_errors', '1');
			ini_set('error_log', 'error.log');
		}

		/**
		 * Get the callback for the error handler.
		 *
		 * @return callable
		 */
		protected static function getErrorCallBack(): callable
		{
			return [static::class, 'errorHandler'];
		}

		/**
		 * Set the error handler.
		 *
		 * @return void
		 */
		public static function setErrorHandler(): void
		{
			set_error_handler(static::getErrorCallBack());
		}

		/**
		 * Handle the exception reporting.
		 *
		 * @param \Throwable $exception
		 * @return bool
		 */
		public static function exceptionHandler(
			\Throwable $exception
		): bool
		{
			return ErrorLog::create((object)[
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
			]);
		}

		/**
		 * Get the callback for the exception handler.
		 *
		 * @return callable
		 */
		protected static function getExceptionCallBack(): callable
		{
			return [static::class, 'exceptionHandler'];
		}

		/**
		 * Set the exception handler.
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
	 * This will add the error handler to the global namespace.
	 *
	 * @param int $errno
	 * @param string $errstr
	 * @param string $errfile
	 * @param int $errline
	 * @return bool
	 */
	function error(
		string $errstr,
		string $errfile = '',
		int $errline = -1,
		int $errno = -1
	): bool
	{
		return Error::errorHandler(
			$errno,
			$errstr,
			$errfile,
			$errline
		);
	}
}