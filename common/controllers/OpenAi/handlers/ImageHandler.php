<?php declare(strict_types=1);
namespace App\Controllers\OpenAi\Handlers;

/**
 * ImageHandler
 *
 * This will handle images.
 *
 * @package App\Controllers\OpenAi\Handlers
 */
class ImageHandler extends Handler
{
    use CurlFileTrait;

    /**
     * This will generate an image.
     *
     * @param string $prompt
     * @param string $size
     * @param int $number
     * @return object|null
     */
    public function create(
        string $prompt,
        string $size = '256x256',
        int $number = 4
    ): ?object
    {
        $result = $this->api->image([
            "prompt" => $prompt,
            "n" => $number,
            "size" => $size,
            "response_format" => "url",
        ]);
        return decode($result);
    }

    /**
     * This will edit an image.
     *
     * @param string $prompt
     * @param string $image
     * @param string $mask
     * @param string $size
     * @param int $number
     * @return object|null
     */
    public function edit(
        string $prompt,
        string $image,
        string $mask = '',
        string $size = '1024x1024',
        int $number = 1
    ): ?object
    {
        $curlImage = $this->createCurlFile($image);
        if (!isset($curlImage))
        {
            return null;
        }

        $curlMask = (!empty($mask))? $this->createCurlFile($mask) : '';

        $result = $this->api->imageEdit([
            "prompt" => $prompt,
            "image" => $curlImage,
            "mask" => $curlMask,
            "n" => $number,
            "size" => $size
        ]);
        return decode($result);
    }

    /**
     * This will create a variant of an image.
     *
     * @param string $image
     * @param string $size
     * @param int $number
     * @return object|null
     */
    public function variant(
        string $image,
        string $size = '1024x1024',
        int $number = 2
    ): ?object
    {
        $curlImage = $this->createCurlFile($image);
        if (!isset($curlImage))
        {
            return null;
        }

        $result = $this->api->createImageVariation([
            "image" => $curlImage,
            "n" => $number,
            "size" => $size
        ]);
        return decode($result);
    }
}