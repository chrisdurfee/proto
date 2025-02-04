<?php declare(strict_types=1);
namespace Proto\Utils\Encryption;

use Proto\Utils\Util;

/**
 * Cipher
 *
 * This will handle the cipher.
 *
 * @package Proto\Utils\Encryption
 */
class Cipher extends Util
{
    /**
     * This is the cipher type
     *
     * @var string
     */
    private const CIPHER = 'aes-256-ctr';

    /**
     * This will get the length of the initialization vector
     *
     * @return int|false
     */
    protected static function getIvLength()
    {
        return \openssl_cipher_iv_length(self::CIPHER);
    }

    /**
     * This will get the initialization vector. This will be a new vector each time it is called
     *
     * @return string
     */
    protected static function getIv(): string
    {
        $ivLength = self::getIvLength();
        return \openssl_random_pseudo_bytes($ivLength);
    }

    /**
     * This will return a base64 encode string
     *
     * @param string $message
     * @return string
     */
    protected static function safe_B64_encode(string $message): string
    {
        $data = \base64_encode($message);
        $data = str_replace(
            ['+', '/', '='],
            ['-','_',''],
            $data
        );
        return $data;
    }

    /**
     * This will decode a base64 string
     *
     * @param string $message
     * @return string
     */
    protected static function safe_B64_decode(string $message): string
    {
        $data = str_replace(array('-','_'), array('+','/'), $message);
        $mod4 = strlen($data) % 4;
        if ($mod4)
        {
            $data .= substr('====', $mod4);
        }
        return base64_decode($data);
    }

    /**
     * This will encrypt the string
     *
     * @param string $plainText
     * @param string $key
     * @return string
     */
    public static function encrypt(string $plainText, string $key): string
    {
        if (!$plainText)
        {
            return "No text provided";
        }

        $iv = self::getIv();
        $encrypted = openssl_encrypt(
            $plainText,
            self::CIPHER,
            $key,
            OPENSSL_RAW_DATA,
            $iv
        );
        $encoded = self::safe_B64_encode($iv . self::hash($encrypted, $key) . $encrypted);

        return $encoded;
    }

    /**
     * This will hash the encrypted text
     *
     * @param string $text
     * @return string
     */
    protected static function hash(string $text, string $key): string
    {
        return hash_hmac('sha256', $text, $key, true);
    }

    /**
     * This will decode the text
     *
     * @param string $text
     * @return string
     */
    protected static function decodeText(string $text): string
    {
        return self::safe_B64_decode($text);
    }

    /**
     * This will decrypt the text
     *
     * @param string $encodedText
     * @param string $key
     * @return string
     */
    public static function decrypt(string $encodedText, string $key): string
    {
        if (!$encodedText)
        {
            return "No text provided";
        }

        $sha256Length = 32;
        $text = self::decodeText($encodedText);
        $ivLength = self::getIvLength();
        $iv = substr($text, 0, $ivLength);
        $hash = substr($text, $ivLength, $sha256Length);
        $rawText = substr($text, $ivLength + $sha256Length);
        $plainText = openssl_decrypt($rawText, self::CIPHER, $key, $options=OPENSSL_RAW_DATA, $iv);

        $calculatedHash = self::hash($rawText, $key);
        if (hash_equals($hash, $calculatedHash))
        {
            return $plainText;
        }

        return "Hash did not match.";
    }
}