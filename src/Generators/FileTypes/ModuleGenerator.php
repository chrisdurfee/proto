<?php declare(strict_types=1);
namespace Proto\Generators\FileTypes;

use Proto\Generators\AbstractFileGenerator;
use Proto\Generators\Templates;
use Proto\Utils\Strings;
use Proto\Utils\Files\File;

/**
 * ModuleGenerator
 *
 * Generates module files based on the provided settings.
 *
 * @package Proto\Generators\FileTypes
 */
class ModuleGenerator extends AbstractFileGenerator
{
	/**
	 * Generates a module file.
	 *
	 * @param object $settings The settings for the module file generation.
	 * @return bool True on success, false otherwise.
	 */
	public function generate(object $settings): bool
	{
		$moduleName = Strings::pascalCase($settings->name);
		$dir = $this->getDir($moduleName, '');
		$fileName = $this->getFileName($moduleName . 'Module');
		$template = new Templates\ModuleTemplate($settings);
		return $this->saveFile($dir, $fileName, $template);
	}

	/**
	 * Returns the full directory path where the module file should be saved.
	 * Now uses the Docker-aware getModuleDir method from AbstractFileGenerator.
	 *
	 * @param string $dir The relative directory.
	 * @param string $module The module name (unused in this implementation).
	 * @return string The full directory path.
	 */
	protected function getDir(string $dir, string $module): string
	{
		$dir = str_replace('\\', '/', $dir);
		$folderName = $this->convertSlashes($dir);

		// For module generation, we need to construct the path differently
		// since we're creating the module directory itself
		if ($this->isDockerEnvironment())
		{
			$basePath = $this->getBasePath();
			$targetDir = $basePath . '/modules/' . $folderName;
			File::checkDir($targetDir);

			return $targetDir;
		}
		else
		{
			// Local environment
			$modulesBasePath = realpath(BASE_PATH . '/modules');
			return $modulesBasePath . DIRECTORY_SEPARATOR . $folderName;
		}
	}
}
