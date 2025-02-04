<?php declare(strict_types=1);
namespace App;

use Proto\Patterns\Structural\Registry;

/**
 * Data
 *
 * This will allow data to be stored in a registry.
 *
 * @package App
 */
class Data extends Registry
{
    /**
     * @var object $instance
     */
    protected static $instance = null;
}