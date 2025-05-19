<?php declare(strict_types=1);
namespace Common\Services\OpenAi\Chat;

/**
 * Content Helper for OpenAI API
 *
 * Provides utility functions to filter and clean content strings.
 *
 * @package Common\Services\OpenAi\Chat
 */
class ContentHelper
{
    /**
     * Handles content formatting for OpenAI API requests.
     *
     * @param string $content
     * @return string
     */
    public static function format(string $content): string
    {
        /**
         * Removes leading spaces and new lines.
         */
        $content = preg_replace('/^\s*[\r
        |\n]+/m', '', $content);

        /**
         * Handles replacing carriage returns and new lines with a space.
         */
        $content = preg_replace("/\r|\n/", ' ', $content);

        /**
         * Handles replacing multiple spaces with a single space.
         */
        return preg_replace('/\s{2,}/', ' ', $content);
    }
}