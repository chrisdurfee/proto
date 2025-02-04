<?php declare(strict_types=1);
namespace App\Utils\Messages;

/**
 * TextError
 *
 * This will create a text error message that's compatible with the enum value in the database.
 */
final class TextError extends MessageError
{
    /**
     * These are enum values from the messages table.
     *
     * @var array
     */
    private const ERRORS = [
        'No client phone number found.',
        'Invalid recipient phone number.',
        'Unable to send due to invalid notice object.',
        'No mobile number setup.',
        'This text was grouped so the error is in the primary reminder error log.',
        'The text failed to send.'
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
            return 'The text failed to send.';
        }

        return $message;
    }
}