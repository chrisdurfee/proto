<?php declare(strict_types=1);
namespace App\Controllers\OpenAi\Settings;

/**
 * SettingsInterface
 *
 * This will be the settings interface.
 *
 * @package App\Controllers\OpenAi\Settings
 */
interface SettingsInterface
{
    /**
     * This will get the settings.
     *
     * @return array
     */
    public function get(): array;
}