<?php declare(strict_types=1);
namespace App\Controllers\OpenAi\Handlers;

/**
 * AudioHandler
 *
 * This will handle the audio.
 *
 * @package App\Controllers\OpenAi\Handlers
 */
class AudioHandler extends Handler
{
    use CurlFileTrait;

    /**
     * This will transcribe the file.
     *
     * @param string $file The file path.
     * @param string $model
     * @return object
     */
    public function transcribe(
        string $file,
        string $model = 'whisper-1'
    ): object
    {
        $file = $this->createCurlFile($file);

        /**
         * This will get the response.
         */
        $result = $this->api->transcribe([
            'model' => $model,
            'file' => $file
        ]);
        return decode($result);
    }

    /**
     * This will translate the file.
     *
     * @param string $file The file path.
     * @param string $model
     * @return object|null
     */
    public function translate(
        string $file,
        string $model = 'whisper-1'
    ): ?object
    {
        $file = $this->createCurlFile($file);

        /**
         * This will get the response.
         */
        $result = $this->api->translate([
            'model' => $model,
            'file' => $file
        ]);
        return decode($result);
    }
}