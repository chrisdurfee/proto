<?php declare(strict_types=1);

namespace Proto\Tests\Unit\Http;

use Proto\Http\Jwt;
use Proto\Tests\Test;

/**
 * JwtTest
 *
 * @package Proto\Tests\Unit\Http
 */
final class JwtTest extends Test
{
	private const SECRET = 'jwt-test-secret-0123456789abcdef';

	/**
	 * @var bool
	 */
	protected bool $useTransactions = false;

	/**
	 * @return void
	 */
	public function testRoundTrip(): void
	{
		$token = Jwt::encode(['sub' => 7], self::SECRET);
		$payload = Jwt::decode($token, self::SECRET);

		$this->assertSame(7, $payload['sub']);
		$this->assertArrayHasKey('exp', $payload);
	}

	/**
	 * @return void
	 */
	public function testWrongSecretAndTamperingAreRejected(): void
	{
		$token = Jwt::encode(['sub' => 7], self::SECRET);
		$this->assertNull(Jwt::decode($token, 'another-secret-0123456789abcdefg'));

		[$h, $p, $s] = explode('.', $token);
		$forged = $h . '.' . rtrim(strtr(base64_encode(json_encode(['sub' => 1, 'exp' => time() + 60])), '+/', '-_'), '=') . '.' . $s;
		$this->assertNull(Jwt::decode($forged, self::SECRET));
	}

	/**
	 * @return void
	 */
	public function testExpiredTokenIsRejected(): void
	{
		$token = Jwt::encode(['sub' => 7], self::SECRET, null, '-1 minute');
		$this->assertNull(Jwt::decode($token, self::SECRET));
	}

	/**
	 * @return void
	 */
	public function testTokenWithoutExpIsRejectedByDefault(): void
	{
		$token = $this->sign(['typ' => 'JWT', 'alg' => 'HS256'], ['sub' => 7]);

		$this->assertNull(Jwt::decode($token, self::SECRET));
		$this->assertSame(7, Jwt::decode($token, self::SECRET, false)['sub']);
	}

	/**
	 * @return void
	 */
	public function testNonHs256HeaderAndNonArrayPayloadAreRejected(): void
	{
		$this->assertNull(Jwt::decode($this->sign(['alg' => 'none'], ['sub' => 7, 'exp' => time() + 60]), self::SECRET));
		$this->assertNull(Jwt::decode($this->sign(['alg' => 'HS256'], 'just-a-string'), self::SECRET));
	}

	/**
	 * @return void
	 */
	public function testEncodeAlwaysAdvertisesHs256(): void
	{
		$token = Jwt::encode(['sub' => 7], self::SECRET, ['typ' => 'JWT', 'alg' => 'RS256']);
		$header = json_decode(base64_decode(strtr(explode('.', $token)[0], '-_', '+/')), true);

		$this->assertSame('HS256', $header['alg']);
		$this->assertNotNull(Jwt::decode($token, self::SECRET));
	}

	/**
	 * @param array $header
	 * @param mixed $payload
	 * @return string
	 */
	private function sign(array $header, mixed $payload): string
	{
		$b64 = fn(string $v): string => rtrim(strtr(base64_encode($v), '+/', '-_'), '=');
		$h = $b64(json_encode($header));
		$p = $b64(json_encode($payload));
		$s = $b64(hash_hmac('sha256', "{$h}.{$p}", self::SECRET, true));
		return "{$h}.{$p}.{$s}";
	}
}
