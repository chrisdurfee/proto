<?php declare(strict_types=1);
namespace Proto\Dispatch\Drivers\Sms;

use Proto\Dispatch\Drivers\Driver;
use Proto\Dispatch\Response;

/**
 * TextDriverInterface
 *
 * This is the text driver interface. This will be used to send text messages.
 *
 * @package Proto\Dispatch\Drivers\Sms
 */
interface TextDriverInterface
{
    /**
     * This will send a text.
     *
     * @param object $settings
     * @return Response
     */
    public function send(object $settings): Response;
}

/**
 * TextDriver
 *
 * This is the base text driver class. This implements the TextDriverInterface.
 *
 * @package Proto\Dispatch\Drivers\Sms
 */
abstract class TextDriver extends Driver implements TextDriverInterface
{

}