<?php declare(strict_types=1);
namespace App\Controllers\OpenAi\Handlers;

/**
 * FileHandler
 *
 * This will handle the files.
 *
 * @package App\Controllers\OpenAi\Handlers
 */
class FileHandler extends Handler
{
    use CurlFileTrait;

    /**
     * This will list the files.
     *
     * @return object|null
     */
    public function list(): ?object
    {
        /**
         * This will get the response.
         */
        $result = $this->api->listFiles();
        return decode($result);
    }

    /**
     * This will upload a file.
     *
     * @param string $file
     * @param string $purpose
     * @return object|null
     */
    public function upload(
        string $file,
        string $purpose = 'answers'
    ): ?object
    {
        $file = $this->createCurlFile($file);

        /**
         * This will get the response.
         */
        $result = $this->api->uploadFile([
            'purpose' => $purpose,
            'file' => $file
        ]);
        return decode($result);
    }

    /**
     * This will delete a file.
     *
     * @param string $file
     * @return object|null
     */
    public function delete(
        string $file
    ): ?object
    {
        /**
         * This will get the response.
         */
        $result = $this->api->deleteFile([
            'file' => $file
        ]);
        return decode($result);
    }

    /**
     * This will retrieve a file.
     *
     * @param string $file
     * @return object|null
     */
    public function retrieve(
        string $file
    ): ?object
    {
        /**
         * This will get the response.
         */
        $result = $this->api->retrieveFile([
            'file' => $file
        ]);
        return decode($result);
    }
}