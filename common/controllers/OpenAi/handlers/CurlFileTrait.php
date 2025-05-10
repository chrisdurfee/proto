<?php declare(strict_types=1);
namespace Common\Controllers\OpenAi\Handlers;

use Proto\Utils\Files\File;

/**
 * CURL File Handling Trait
 *
 * Provides utility methods for handling file uploads and conversions
 * for CURL-based API requests to OpenAI's services. Handles remote files,
 * local files, and proper formatting for multipart/form-data requests.
 *
 * @package Common\Controllers\OpenAi\Handlers
 */
trait CurlFileTrait
{
    /**
     * Gets a temporary file path for a remote URL.
     *
     * @param string $url Remote file URL to download
     * @return string Path to the downloaded temporary file
     */
    protected function getTmpPath(string $url): string
    {
        $url = parse_url($url, PHP_URL_PATH);
        return sys_get_temp_dir() . '/' . basename($url);
    }

    /**
     * This will get the remote file.
     *
     * @param string $url
     * @return string|null
     */
    protected function getRemoteFile(string $url): ?string
    {
        $file = File::get($url, true);
        if ($file === false)
        {
            return null;
        }

        $path = $this->getTmpPath($url);
        File::put($path, $file);
        return $path;
    }

    /**
     * This will create the curl file.
     *
     * @param string $file The file path.
     * @param int $count The count.
     * @return \CURLFile|null
     */
    protected function createCurlFile(string $file, int $count = 0): ?\CURLFile
    {
        if (!file_exists($file))
        {
            if ($count >= 2)
            {
                return null;
            }

            /**
             * This will get the remote file.
             */
            $file = $this->getRemoteFile($file);
            if ($file === null)
            {
                return null;
            }

            return $this->createCurlFile($file, $count++);
        }
        return curl_file_create($file, mime_content_type($file), basename($file));
    }
}