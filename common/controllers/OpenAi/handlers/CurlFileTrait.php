<?php declare(strict_types=1);
namespace App\Controllers\OpenAi\Handlers;

use Dashr\Utils\Files\File;

/**
 * CurlFileTrait
 *
 * This will handle the curl file.
 *
 * @package App\Controllers\OpenAi\Handlers
 */
trait CurlFileTrait
{
    /**
     * This will get the tmp path.
     *
     * @param string $url
     * @return string
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