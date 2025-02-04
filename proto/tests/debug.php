<?php declare(strict_types=1);
namespace Proto\Tests;

/**
 * Debug
 *
 * This will render debug content to the screen.
 *
 * @package Proto\Tests
 */
class Debug
{
    /**
     * This will render content to the screen in a pre tag.
     *
     * @param mixed $content
     * @return void
     */
    public static function render(mixed $content): void
    {
        echo '<pre>';
        var_dump($content);
        echo '</pre>';
    }
}