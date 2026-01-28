<?php declare(strict_types=1);
namespace Proto\Generators\FileTypes;

use Proto\Generators\AbstractFileGenerator;
use Proto\Generators\Templates;

/**
 * MigrationGenerator
 *
 * Generates migration files based on the provided settings.
 *
 * @package Proto\Generators\FileTypes
 */
class MigrationGenerator extends AbstractFileGenerator
{
	/**
	 * Generates a migration file.
	 *
	 * @param object $settings The settings for the migration file generation.
	 * @return bool True on success, false otherwise.
	 */
	public function generate(object $settings): bool
	{
		$dateTime = $this->getFileDate();
		$fileName = $dateTime . '_' . $this->getFileName($settings->className);
		$template = new Templates\MigrationTemplate($settings);
		$featurePath = $settings->featurePath ?? null;
		$dir = $this->getDir('', $settings->moduleName, $featurePath);
		return $this->saveFile($dir, $fileName, $template);
	}

	/**
	 * Returns the full directory path where the API resource file should be saved.
	 *
	 * @param string $dir The relative directory.
	 * @param string $module The module name.
	 * @param string|null $featurePath Optional feature path within the module.
	 * @return string The full directory path.
	 */
	protected function getDir(string $dir, string $module, ?string $featurePath = null): string
	{
		$dir = str_replace('\\', '/', $dir);
		$moduleDir = $this->getModuleDir($module, $featurePath);
		return $moduleDir . $this->convertSlashes('/Migrations');
	}

	/**
	 * Returns the current file date formatted for migration files.
	 *
	 * @return string The formatted date.
	 */
	protected function getFileDate(): string
	{
		$dateTime = new \DateTime();
		$formatted = $dateTime->format('Y-m-d H.i.s.u');
		return str_replace(' ', 'T', $formatted);
	}
}