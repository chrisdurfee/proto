<?php declare(strict_types=1);

namespace Proto\Tests\Unit\Http\Router;

use Proto\Http\Router\EntityTag;
use Proto\Tests\Test;

/**
 * EntityTagTest
 *
 * @package Proto\Tests\Unit\Http\Router
 */
final class EntityTagTest extends Test
{
	/**
	 * @var bool
	 */
	protected bool $useTransactions = false;

	/**
	 * @return void
	 */
	protected function tearDown(): void
	{
		unset($_SERVER['HTTP_IF_NONE_MATCH']);
		parent::tearDown();
	}

	/**
	 * @return void
	 */
	public function testGenerateIsQuotedAndStable(): void
	{
		$body = '{"success":true,"rows":[]}';
		$etag = EntityTag::generate($body);

		$this->assertMatchesRegularExpression('/^"[0-9a-f]{32}"$/', $etag);
		$this->assertSame($etag, EntityTag::generate($body));
	}

	/**
	 * @return void
	 */
	public function testGenerateChangesWithBody(): void
	{
		$this->assertNotSame(
			EntityTag::generate('{"rows":[1]}'),
			EntityTag::generate('{"rows":[2]}')
		);
	}

	/**
	 * @return void
	 */
	public function testMatchesExactTag(): void
	{
		$etag = EntityTag::generate('payload');
		$this->assertTrue(EntityTag::matches($etag, $etag));
	}

	/**
	 * @return void
	 */
	public function testDoesNotMatchDifferentTag(): void
	{
		$etag = EntityTag::generate('payload');
		$other = EntityTag::generate('other payload');

		$this->assertFalse(EntityTag::matches($etag, $other));
	}

	/**
	 * @return void
	 */
	public function testMissingOrEmptyHeaderNeverMatches(): void
	{
		$etag = EntityTag::generate('payload');

		$this->assertFalse(EntityTag::matches($etag, null));
		$this->assertFalse(EntityTag::matches($etag, ''));
		$this->assertFalse(EntityTag::matches($etag, '   '));
	}

	/**
	 * @return void
	 */
	public function testWildcardMatches(): void
	{
		$this->assertTrue(EntityTag::matches(EntityTag::generate('payload'), '*'));
	}

	/**
	 * A weak validator must still satisfy If-None-Match, which requires
	 * weak comparison.
	 *
	 * @return void
	 */
	public function testWeakPrefixMatches(): void
	{
		$etag = EntityTag::generate('payload');
		$this->assertTrue(EntityTag::matches($etag, 'W/' . $etag));
	}

	/**
	 * mod_deflate rewrites the origin tag as "hash-gzip" when it compresses
	 * the body, and the client echoes that value back. Without normalizing
	 * the suffix every revalidation would miss and return a full 200.
	 *
	 * @return void
	 */
	public function testTagRewrittenByCompressingProxyStillMatches(): void
	{
		$etag = EntityTag::generate('payload');
		$hash = trim($etag, '"');

		$this->assertTrue(EntityTag::matches($etag, '"' . $hash . '-gzip"'));
		$this->assertTrue(EntityTag::matches($etag, '"' . $hash . '-br"'));
		$this->assertTrue(EntityTag::matches($etag, 'W/"' . $hash . '-gzip"'));
	}

	/**
	 * @return void
	 */
	public function testMatchesWithinCommaSeparatedList(): void
	{
		$etag = EntityTag::generate('payload');
		$header = '"aaaaaaaa", ' . $etag . ', "bbbbbbbb"';

		$this->assertTrue(EntityTag::matches($etag, $header));
	}

	/**
	 * @return void
	 */
	public function testRequestedTagReadsHeader(): void
	{
		$_SERVER['HTTP_IF_NONE_MATCH'] = '"abc123"';
		$this->assertSame('"abc123"', EntityTag::requestedTag());
	}

	/**
	 * @return void
	 */
	public function testRequestedTagIsNullWhenAbsent(): void
	{
		unset($_SERVER['HTTP_IF_NONE_MATCH']);
		$this->assertNull(EntityTag::requestedTag());
	}

	/**
	 * The header is client controlled and flows into comparisons, so
	 * newline injection must not survive.
	 *
	 * @return void
	 */
	public function testRequestedTagStripsControlCharacters(): void
	{
		$_SERVER['HTTP_IF_NONE_MATCH'] = "\"abc\r\nX-Injected: 1\"";
		$this->assertSame('"abcX-Injected: 1"', EntityTag::requestedTag());
	}
}
