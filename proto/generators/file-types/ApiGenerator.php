<?php declare(strict_types=1);
namespace Proto\Generators\FileTypes;

use Proto\Generators\AbstractFileGenerator;
use Proto\Generators\Templates;
use Proto\Utils\Strings;

/**
 * ApiGenerator
 *
 * Generates API resource files based on the provided settings.
 *
 * @package Proto\Generators\FileTypes
 */
class ApiGenerator extends AbstractFileGenerator
{
	/**
	 * Generates an API resource file.
	 *
	 * @param object $settings The settings for the API resource file generation.
	 * @return bool True on success, false otherwise.
	 */
	public function generate(object $settings): bool
	{
		$dir = $this->getDir($settings->dir);
		$fileName = $this->getFileName($settings->className . 'API');
		$template = new Templates\ApiTemplate($settings);
		return $this->saveFile($dir, $fileName, $template);
	}

	/**
	 * Returns the full directory path where the API resource file should be saved.
	 *
	 * @param string $dir The relative directory.
	 * @return string The full directory path.
	 */
	protected function getDir(string $dir): string
	{
		$dir = str_replace('\\', '/', $dir);
		return realpath(__DIR__ . '/../../../app') . $this->convertSlashes('/API/' . strtolower(Strings::hyphen($dir)));
	}
}