<?php declare(strict_types=1);
namespace Common\Controllers\OpenAi\Handlers;

/**
 * Image Generation API Handler
 *
 * Manages interactions with OpenAI's DALL-E image generation API.
 * Supports creating, editing, and modifying images from text prompts
 * with various configuration options.
 *
 * @package Common\Controllers\OpenAi\Handlers
 */
class ImageHandler extends Handler
{
	use CurlFileTrait;

	/**
	 * Generates new images from a text prompt.
	 *
	 * @param string $prompt Text description of the desired image(s)
	 * @param string $size Size of the generated image(s) (e.g., "1024x1024")
	 * @param int $number Number of images to generate
	 * @return object|null Response containing generated image URLs or null on failure
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