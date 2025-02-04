<?php declare(strict_types=1);
namespace App\Utils\Messages;

/**
 * MessageError
 *
 * This is the base class for a message error.
 */
abstract class MessageError
{
    /**
     * This will create an error message that's compatible with the enum values in the message table.
     *
     * @return string|null
     */
    abstract public static function create(string|null $message): string;
}