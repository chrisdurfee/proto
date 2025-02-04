<?php declare(strict_types=1);
namespace App\Utils\Messages;

/**
 * EmailError
 *
 * This will create an email error message that's compatible with the enum value in the database.
 */
final class EmailError extends MessageError
{
    /**
     * These are enum values from the messages table.
     *
     * @var array
     */
    private const ERRORS = [
        'Invalid recipient email address.',
        'This email is blocked from sending.',
        'Unable to send due to invalid notice object.',
        'No email setup.',
        'This email was grouped so the error is in the primary reminder error log.',
        'The email failed to send.'
    ];

    /**
     * This will create a text error message that's compatible with the enum value in the database.
     *
     * @return string|null
     */
    public static function create(string|null $message): string
    {
        if (!isset($message) || empty($message))
        {
            return '';
        }

        if (!in_array($message, self::ERRORS))
        {
            return 'The email failed to send.';
        }

        return $message;
    }
}