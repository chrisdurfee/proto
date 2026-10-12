<?php declare(strict_types=1);

namespace Proto\Tests\Unit\Utils;

use Proto\Tests\Test;
use Proto\Utils\Encryption\Cipher;

/**
 * CipherTest
 *
 * New ciphertexts use derived encryption/MAC keys and authenticate the
 * IV; values written in the legacy format still decrypt.
 *
 * @package Proto\Tests\Unit\Utils
 */
final class CipherTest extends Test
{
	private const KEY = 'cipher-test-key-0123456789abcdef';

	/**
	 * @var bool
	 */
	protected bool $useTransactions = false;

	/**
	 * @return void
	 */
	public function testRoundTripUsesVersionedFormat(): void
	{
		$encrypted = Cipher::encrypt('secret value', self::KEY);

		$this->assertStringStartsWith('v2.', $encrypted);
		$this->assertSame('secret value', Cipher::decrypt($encrypted, self::KEY));
		$this->assertNull(Cipher::decrypt($encrypted, 'wrong-key-0123456789abcdefghijkl'));
	}

	/**
	 * @return void
	 */
	public function testTamperedIvIsRejected(): void
	{
		$encrypted = Cipher::encrypt('secret value', self::KEY);
		$raw = base64_decode(strtr(substr($encrypted, 3), '-_', '+/'));
		$raw[0] = chr(ord($raw[0]) ^ 1);
		$tampered = 'v2.' . rtrim(strtr(base64_encode($raw), '+/', '-_'), '=');

		$this->assertNull(Cipher::decrypt($tampered, self::KEY));
	}

	/**
	 * @return void
	 */
	public function testLegacyCiphertextStillDecrypts(): void
	{
		$iv = random_bytes(16);
		$encrypted = openssl_encrypt('legacy value', 'aes-256-ctr', self::KEY, OPENSSL_RAW_DATA, $iv);
		$hash = hash_hmac('sha256', $encrypted, self::KEY, true);
		$legacy = rtrim(strtr(base64_encode($iv . $hash . $encrypted), '+/', '-_'), '=');

		$this->assertSame('legacy value', Cipher::decrypt($legacy, self::KEY));
	}

	/**
	 * @return void
	 */
	public function testFalsyPlaintextDecrypts(): void
	{
		$this->assertSame('0', Cipher::decrypt(Cipher::encrypt('0', self::KEY), self::KEY));
	}
}
