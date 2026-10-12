<?php declare(strict_types=1);

namespace Proto\Tests\Unit\Error;

use Proto\Error\Error;
use Proto\Tests\Test;
use ReflectionMethod;

/**
 * ErrorRequestRedactionTest
 *
 * Error rows store the request payload. Passwords and other secrets in
 * that payload (or the URL query string) must never be persisted.
 *
 * @package Proto\Tests\Unit\Error
 */
final class ErrorRequestRedactionTest extends Test
{
	/**
	 * @var bool
	 */
	protected bool $useTransactions = false;

	/**
	 * @param string $method
	 * @param mixed ...$args
	 * @return mixed
	 */
	private function call(string $method, mixed ...$args): mixed
	{
		$ref = new ReflectionMethod(Error::class, $method);
		return $ref->invoke(null, ...$args);
	}

	/**
	 * @return void
	 */
	public function testPasswordFieldsAreRedactedFromRequestPayload(): void
	{
		$redacted = $this->call('redactRequestData', [
			'email' => 'user@example.com',
			'password' => 'hunter2',
			'item' => (object)[
				'title' => 'x',
				'passwordHash' => 'abc',
				'token' => 'secret-token',
			],
		]);

		$this->assertSame('user@example.com', $redacted['email']);
		$this->assertSame('[REDACTED]', $redacted['password']);
		$this->assertSame('x', $redacted['item']->title);
		$this->assertSame('[REDACTED]', $redacted['item']->passwordHash);
		$this->assertSame('[REDACTED]', $redacted['item']->token);
	}

	/**
	 * @return void
	 */
	public function testUrlQuerySecretsAreRedacted(): void
	{
		$url = 'https://api.example.com/login?email=a%40b.com&password=hunter2&next=/home';
		$sanitized = $this->call('sanitizeUrl', $url);

		$this->assertStringContainsString('email=a%40b.com', $sanitized);
		$this->assertStringContainsString('password=%5BREDACTED%5D', $sanitized);
		$this->assertStringNotContainsString('hunter2', $sanitized);
	}

	/**
	 * @return void
	 */
	public function testUrlUserInfoPasswordIsRedacted(): void
	{
		$url = 'https://user:supersecret@api.example.com/path';
		$sanitized = $this->call('sanitizeUrl', $url);

		$this->assertStringContainsString('user:[REDACTED]@', $sanitized);
		$this->assertStringNotContainsString('supersecret', $sanitized);
	}
}
