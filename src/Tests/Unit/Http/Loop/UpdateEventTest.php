<?php declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use Proto\Http\Loop\EventLoop;
use Proto\Http\Loop\UpdateEvent;

final class UpdateEventTest extends TestCase
{
    public function testUpdateEventLoopsAndCanEndLoop(): void
    {
        $loop = new EventLoop(0); // fastest possible ticks for the test
        $count = 0;

        $loop->addEvent(new UpdateEvent(function() use (&$count, $loop) {
            $count++;
            if ($count >= 3) {
                // End the loop after 3 ticks
                $loop->end();
            }
            return ['tick' => $count];
        }));

        // Run the loop; it should end after our condition
        $loop->loop();

        $this->assertSame(3, $count, 'UpdateEvent should have ticked exactly 3 times.');
    }
}
