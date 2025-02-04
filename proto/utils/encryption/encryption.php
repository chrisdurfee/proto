<?php declare(strict_types=1);
namespace Proto\Utils\Encryption;

use Proto\Utils\Util;

/**
 * Encryption
 *
 * This will handle the encryption.
 *
 * @package Proto\Utils\Encryption
 */
class Encryption extends Util
{
    /**
     * Generated using openssl_random_pseudo_bytes(128)
     * then converting binary to hex
     *
     * $bytes = openssl_random_pseudo_bytes(128);
     * $hex   = bin2hex($bytes);
     *
     * @var string
     */
    private const KEY = 'be23001c1a7db9d81b9abc6d913ea4a0c87581eab44bd589989b59c2d2d385d5fadfcc457f6c896bd54725682c72939141546b7729d091e93c1e305b8b63891d127e7310125ddc214ee4e698456643a0fe60966000b01cf685ba2bff1119fc64d7bb55486c26daf6f88ff102a7c1692bd9636f63af8ca4e0f2e6a680bb17037f';

    /**
     * This will encrypt the data passed to it
     *
     * @param mixed $data
     * @param string|null $key
     * @return string
     */
    public static function encrypt(mixed $data, ?string $key = null): string
    {
        if (gettype($data) !== 'string')
        {
            $data = \json_encode($data);
        }

        $key = $key ?? self::KEY;
        return Cipher::encrypt($data, $key);
    }

    /**
     * This will decrypt the text
     *
     * @param string $text
     * @param string|null $key
     * @return string
     */
    public static function decrypt(string $text, ?string $key = null): string
    {
        $key = $key ?? self::KEY;
        return Cipher::decrypt($text, $key);
    }
}