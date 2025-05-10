<?php declare(strict_types=1);
namespace Common\Controllers\OpenAi\Settings;

/**
 * OpenAI Settings Interface
 *
 * Defines the contract for all OpenAI API configuration settings classes.
 * Ensures consistent configuration handling across different API services.
 *
 * @package Common\Controllers\OpenAi\Settings
 */
interface SettingsInterface
{
    /**
     * Retrieves the settings as an array.
     *
     * @return array Settings configuration for API requests
     */
    public function get(): array;
}