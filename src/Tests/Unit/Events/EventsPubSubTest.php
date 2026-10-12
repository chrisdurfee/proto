<?php declare(strict_types=1);

namespace Proto\Tests\Unit\Events;

use Proto\Events\EventEmitter;
use Proto\Events\EventProxy;
use Proto\Events\Events;
use Proto\Patterns\Structural\PubSub;
use Proto\Tests\Test;

/**
 * EventsPubSubTest
 *
 * Local (non-Redis) subscribe/emit/unsubscribe through PubSub, the
 * Events singleton, EventEmitter and EventProxy.
 *
 * @package Proto\Tests\Unit\Events
 */
final class EventsPubSubTest extends Test
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
		Events::reset();
	}

	/**
	 * @return void
	 */
	protected function tearDown(): void
	{
		Events::reset();
		parent::tearDown();
	}

	/**
	 * @return void
	 */
	public function testPublishDeliversPayloadToEverySubscriberInOrder(): void
	{
		$pubSub = new PubSub();
		$log = [];
		$pubSub->subscribe('saved', function($m) use (&$log) { $log[] = "a:{$m}"; });
		$pubSub->subscribe('saved', function($m) use (&$log) { $log[] = "b:{$m}"; });

		$pubSub->publish('saved', 'x');
		$this->assertSame(['a:x', 'b:x'], $log);
	}

	/**
	 * @return void
	 */
	public function testPublishOnlyReachesMatchingKey(): void
	{
		$pubSub = new PubSub();
		$hits = 0;
		$pubSub->subscribe('a', function() use (&$hits) { $hits++; });

		$pubSub->publish('b', null);
		$pubSub->publish('A', null);
		$this->assertSame(0, $hits, 'Keys are exact and case-sensitive.');
	}

	/**
	 * @return void
	 */
	public function testTokensAreUniqueAcrossKeys(): void
	{
		$pubSub = new PubSub();
		$a = $pubSub->subscribe('a', fn() => null);
		$b = $pubSub->subscribe('b', fn() => null);
		$c = $pubSub->subscribe('a', fn() => null);

		$this->assertCount(3, array_unique([$a, $b, $c]));
	}

	/**
	 * @return void
	 */
	public function testUnsubscribeRemovesOnlyThatToken(): void
	{
		$pubSub = new PubSub();
		$log = [];
		$first = $pubSub->subscribe('e', function() use (&$log) { $log[] = 1; });
		$pubSub->subscribe('e', function() use (&$log) { $log[] = 2; });

		$pubSub->unsubscribe('e', $first);
		$pubSub->publish('e', null);
		$this->assertSame([2], $log);
	}

	/**
	 * @return void
	 */
	public function testUnsubscribeWithWrongKeyOrTokenIsHarmless(): void
	{
		$pubSub = new PubSub();
		$token = $pubSub->subscribe('e', fn() => null);

		$pubSub->unsubscribe('other', $token);
		$pubSub->unsubscribe('e', 'id-999');
		$this->assertCount(1, $pubSub->getSubscribers('e'));
	}

	/**
	 * A listener removing itself mid-publish must not skip the others.
	 *
	 * @return void
	 */
	public function testUnsubscribeDuringPublishIsSafe(): void
	{
		$pubSub = new PubSub();
		$log = [];
		$token = null;
		$token = $pubSub->subscribe('e', function() use (&$log, &$token, $pubSub)
		{
			$log[] = 'once';
			$pubSub->unsubscribe('e', $token);
		});
		$pubSub->subscribe('e', function() use (&$log) { $log[] = 'always'; });

		$pubSub->publish('e', null);
		$pubSub->publish('e', null);
		$this->assertSame(['once', 'always', 'always'], $log);
	}

	/**
	 * @return void
	 */
	public function testClearSubscribersAndClearAll(): void
	{
		$pubSub = new PubSub();
		$pubSub->subscribe('a', fn() => null);
		$pubSub->subscribe('b', fn() => null);

		$pubSub->clearSubscribers('a');
		$this->assertSame([], $pubSub->getSubscribers('a'));
		$this->assertCount(1, $pubSub->getSubscribers('b'));

		$pubSub->clearAll();
		$this->assertSame([], $pubSub->getSubscribers('b'));
	}

	/**
	 * @return void
	 */
	public function testEventsOnUpdateOff(): void
	{
		$received = [];
		$token = Events::on('user:add', function($payload) use (&$received) { $received[] = $payload; });

		Events::update('user:add', (object)['id' => 1]);
		Events::off('user:add', $token);
		Events::update('user:add', (object)['id' => 2]);

		$this->assertCount(1, $received);
		$this->assertSame(1, $received[0]->id);
	}

	/**
	 * @return void
	 */
	public function testListeningTracksSubscriptions(): void
	{
		$this->assertFalse(Events::listening('order:paid'));

		$token = Events::on('order:paid', fn() => null);
		$this->assertTrue(Events::listening('order:paid'));
		$this->assertFalse(Events::listening('order:refunded'));

		Events::off('order:paid', $token);
		$this->assertFalse(Events::listening('order:paid'), 'Removing the last listener stops listening().');
	}

	/**
	 * @return void
	 */
	public function testEventsInstanceApiMatchesStaticApi(): void
	{
		$events = Events::getInstance();
		$hits = 0;
		$token = $events->subscribe('x', function() use (&$hits) { $hits++; });

		$events->emit('x', null);
		Events::update('x', null);
		$this->assertSame(2, $hits);
		$this->assertTrue($events->hasListeners('x'));

		$events->unsubscribe('x', $token);
		$this->assertFalse($events->hasListeners('x'));
	}

	/**
	 * @return void
	 */
	public function testResetDropsSubscribers(): void
	{
		Events::on('gone', fn() => null);
		Events::reset();

		$this->assertFalse(Events::listening('gone'));
	}

	/**
	 * @return void
	 */
	public function testRedisPrefixedKeyFallsBackToLocalWithoutAdapter(): void
	{
		if (Events::getInstance()->getRedisAdapter() !== null)
		{
			$this->markTestSkipped('Redis adapter is configured in this environment.');
		}

		$got = null;
		Events::on('redis:chat', function($payload) use (&$got) { $got = $payload; });
		Events::update('redis:chat', 'hi');

		$this->assertSame('hi', $got);
		$this->assertTrue(Events::listening('redis:chat'));
	}

	/**
	 * @return void
	 */
	public function testEventEmitterIsIsolatedFromGlobalEvents(): void
	{
		$emitter = new EventEmitter();
		$local = 0;
		$global = 0;
		$token = $emitter->on('tick', function() use (&$local) { $local++; });
		Events::on('tick', function() use (&$global) { $global++; });

		$emitter->emit('tick');
		$emitter->off('tick', $token);
		$emitter->emit('tick');

		$this->assertSame(1, $local);
		$this->assertSame(0, $global);
	}

	/**
	 * @return void
	 */
	public function testEventProxyPublishesArgsAndResult(): void
	{
		$target = new class
		{
			public function add(int $a, int $b): int
			{
				return $a + $b;
			}
		};

		$payloads = [];
		Events::on('math:add', function($payload) use (&$payloads) { $payloads[] = $payload; });

		$proxy = new EventProxy('math', $target);
		$this->assertSame(5, $proxy->add(2, 3));
		$this->assertCount(1, $payloads);
		$this->assertSame([2, 3], $payloads[0]->args);
		$this->assertSame(5, $payloads[0]->data);
	}

	/**
	 * @return void
	 */
	public function testEventProxyUnknownMethodThrowsWithoutPublishing(): void
	{
		$published = false;
		Events::on('math:nope', function() use (&$published) { $published = true; });

		try
		{
			(new EventProxy('math', new \stdClass()))->nope();
			$this->fail('Expected BadMethodCallException.');
		}
		catch (\BadMethodCallException $e)
		{
			$this->assertStringContainsString('math', $e->getMessage());
		}

		$this->assertFalse($published);
	}
}
