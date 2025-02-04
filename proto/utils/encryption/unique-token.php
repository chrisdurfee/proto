<?php declare(strict_types=1);
namespace Proto\Utils\Encryption;

/**
 * UniqueToken
 *
 * This will generate a unique token.
 *
 * @package Proto\Utils\Encryption
 */
class UniqueToken
{
    /**
     * This will generate a unique token.
     *
     * @param int $length
     * @return string
     */
    public static function generate(int $length = 128): string
    {
        $bytes = \random_bytes($length);
        return \bin2hex($bytes);
    }
}