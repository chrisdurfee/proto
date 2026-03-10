<?php declare(strict_types=1);
namespace Proto\Dispatch;

/**
 * Class Mail
 *
 * Placeholder class to support sending mail.
 *
 * @package Proto\Dispatch
 */
class Mail extends Dispatch
{
	/**
	 * Sends a mail message.
	 *
	 * This method MUST be overridden in a concrete subclass.
	 * Calling it directly on the base Mail class will throw a RuntimeException
	 * to prevent silent no-op behaviour from obscuring configuration mistakes.
	 *
	 * @throws \RuntimeException Always — override this method in a subclass.
	 * @return Response
	 */
	public function send(): Response
	{
		throw new \RuntimeException(
			static::class . '::send() is not implemented. '
			. 'Extend ' . self::class . ' and override send().'
		);
	}
}