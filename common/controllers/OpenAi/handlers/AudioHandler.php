<?php declare(strict_types=1);
namespace Common\Controllers\OpenAi\Handlers;

/**
 * Audio API Handler
 *
 * Manages interactions with OpenAI's Audio API for transcription
 * and translation of audio files to text. Supports various file formats
 * and provides configuration options for language processing.
 *
 * @package Common\Controllers\OpenAi\Handlers
 */
class AudioHandler extends Handler
{
    use CurlFileTrait;

    /**
     * Transcribes speech from an audio file to text.
     *
     * @param string $file Path to the audio file to transcribe
     * @param string $model The model to use for transcription (default: whisper-1)
     * @return object Transcription response containing the text
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