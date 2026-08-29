<?php declare(strict_types=1);

namespace Proto\Tests\Unit\Http\Router;

use Proto\Http\Request as HttpRequest;
use Proto\Http\Router\CacheDirective;
use Proto\Http\Router\EntityTag;
use Proto\Http\Router\Headers;
use Proto\Http\Router\Response;
use Proto\Tests\Test;

/**
 * ConditionalResponseTest
 *
 * Covers the ETag / 304 path on the router response. Header emission is a
 * no-op under the CLI SAPI, so these assertions are about the response body:
 * a revalidated request must send nothing at all.
 *
 * @package Proto\Tests\Unit\Http\Router
 */
final class ConditionalResponseTest extends Test
{
	/**
	 * @var bool
	 */
	protected bool $useTransactions = false;

	/**
	 * @return void
	 */
	protected function setUp(): void
	{
		parent::setUp();
		Headers::reset();
		$this->setMethod('GET');
	}

	/**
	 * @return void
	 */
	protected function tearDown(): void
	{
		unset($_SERVER['HTTP_IF_NONE_MATCH']);
		Headers::reset();
		HttpRequest::reset();
		parent::tearDown();
	}

	/**
	 * @param string $method
	 * @return void
	 */
	private function setMethod(string $method): void
	{
		HttpRequest::reset();
		(new \ReflectionClass(HttpRequest::class))
			->getProperty('httpMethod')
			->setValue(null, $method);
	}

	/**
	 * @param mixed $data
	 * @param int $code
	 * @return string
	 */
	private function capture(mixed $data, int $code = 200): string
	{
		$response = new Response();

		ob_start();
		$response->json($data, $code);
		return (string)ob_get_clean();
	}

	/**
	 * @return void
	 */
	public function testFirstRequestReturnsFullBody(): void
	{
		$data = (object)['success' => true, 'rows' => [1, 2, 3]];
		$body = $this->capture($data);

		$this->assertJsonStringEqualsJsonString(json_encode($data), $body);
	}

	/**
	 * @return void
	 */
	public function testMatchingValidatorReturnsEmptyBody(): void
	{
		$data = (object)['success' => true, 'rows' => [1, 2, 3]];

		$first = $this->capture($data);
		$_SERVER['HTTP_IF_NONE_MATCH'] = EntityTag::generate($first);

		$this->assertSame('', $this->capture($data));
	}

	/**
	 * @return void
	 */
	public function testStaleValidatorReturnsFullBody(): void
	{
		$data = (object)['success' => true, 'rows' => [1, 2, 3]];
		$_SERVER['HTTP_IF_NONE_MATCH'] = '"not-the-current-tag"';

		$this->assertNotSame('', $this->capture($data));
	}

	/**
	 * A changed payload must invalidate the client's copy even though the
	 * request is identical.
	 *
	 * @return void
	 */
	public function testChangedPayloadReturnsFullBody(): void
	{
		$first = $this->capture((object)['rows' => [1]]);
		$_SERVER['HTTP_IF_NONE_MATCH'] = EntityTag::generate($first);

		$second = $this->capture((object)['rows' => [1, 2]]);
		$this->assertNotSame('', $second);
		$this->assertStringContainsString('2', $second);
	}

	/**
	 * Only safe methods may be revalidated: a mutation must always run and
	 * always answer with its result.
	 *
	 * @return void
	 */
	public function testMutationIsNeverRevalidated(): void
	{
		$data = (object)['success' => true];
		$body = json_encode($data);
		$_SERVER['HTTP_IF_NONE_MATCH'] = EntityTag::generate($body);

		$this->setMethod('POST');
		$this->assertSame($body, $this->capture($data));
	}

	/**
	 * @return void
	 */
	public function testErrorResponseIsNeverRevalidated(): void
	{
		$data = (object)['success' => false, 'message' => 'Denied'];
		$body = json_encode($data);
		$_SERVER['HTTP_IF_NONE_MATCH'] = EntityTag::generate($body);

		$this->assertSame($body, $this->capture($data, 403));
	}

	/**
	 * no-store forbids the client from keeping the copy a 304 would refer
	 * to, so such a response must always be sent in full.
	 *
	 * @return void
	 */
	public function testNoStoreResponseIsNeverRevalidated(): void
	{
		$data = (object)['token' => 'secret-value'];
		$body = json_encode($data);
		$_SERVER['HTTP_IF_NONE_MATCH'] = EntityTag::generate($body);

		Response::cache(CacheDirective::noStore());
		$this->assertSame($body, $this->capture($data));
	}

	/**
	 * @return void
	 */
	public function testNullDataSendsNoBody(): void
	{
		$this->assertSame('', $this->capture(null));
	}

	/**
	 * A directive chosen while handling one request must not survive into
	 * the next request on the same worker.
	 *
	 * @return void
	 */
	public function testDirectiveResetsBetweenRequests(): void
	{
		Response::cache(CacheDirective::noStore());
		$this->assertFalse(Headers::directive()->isStorable());

		Headers::set(['GET']);
		$this->assertTrue(Headers::directive()->isStorable());
	}

	/**
	 * @return void
	 */
	public function testDefaultDirectiveAllowsRevalidation(): void
	{
		$directive = Headers::directive();

		$this->assertTrue($directive->isStorable());
		$this->assertFalse($directive->isReusable());
	}
}
