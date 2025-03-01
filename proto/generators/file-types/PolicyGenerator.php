<?php declare(strict_types=1);
namespace Proto\Generators\FileTypes;

use Proto\Generators\AbstractFileGenerator;
use Proto\Generators\Templates;
use Proto\Utils\Strings;

/**
 * PolicyGenerator
 *
 * Generates policy files based on the provided settings.
 *
 * @package Proto\Generators\FileTypes
 */
class PolicyGenerator extends AbstractFileGenerator
{
	/**
	 * Generates a policy file.
	 *
	 * @param object $settings The settings for the policy file generation.
	 * @return bool True on success, false otherwise.
	 */
	public function generate(object $settings): bool
	{
		$dir = $this->getDir($settings->dir);
		$fileName = $this->getFileName($settings->className . 'Policy');
		$template = new Templates\PolicyTemplate($settings);
		return $this->saveFile($dir, $fileName, $template);
	}

	/**
	 * Returns the full directory path where the policy file should be saved.
	 *
	 * @param string $dir The relative directory.
	 * @return string The full directory path.
	 */
	protected function getDir(string $dir): string
	{
		$dir = str_replace('\\', '/', $dir);
		return realpath(__DIR__ . '/../../../common') . $this->convertSlashes('/Auth/Policies/' . strtolower(Strings::hyphen($dir)));
	}
}