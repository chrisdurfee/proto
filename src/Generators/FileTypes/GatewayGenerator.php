<?php declare(strict_types=1);
namespace Proto\Generators\FileTypes;

use Proto\Generators\AbstractFileGenerator;
use Proto\Generators\Templates;
use Proto\Utils\Strings;

/**
 * ControllerGenerator
 *
 * Generates controller files based on the provided settings.
 *
 * @package Proto\Generators\FileTypes
 */
class GatewayGenerator extends AbstractFileGenerator
{
	/**
	 * Generates a gateway file.
	 *
	 * @param object $settings The settings for the gateway file generation.
	 * @return bool True on success, false otherwise.
	 */
	public function generate(object $settings): bool
	{
		$moduleName = Strings::pascalCase($settings->moduleName ?? '');
		$featurePath = $settings->featurePath ?? null;
		$dir = $this->getDir($moduleName, $moduleName, $featurePath);
		$fileName = $this->getFileName('Gateway');
		$template = new Templates\GatewayTemplate($settings);
		return $this->saveFile($dir, $fileName, $template);
	}

	/**
	 * Returns the full directory path where the module file should be saved.
	 *
	 * @param string $dir The relative directory.
	 * @param string $moduleName The module name.
	 * @param string|null $featurePath Optional feature path within the module.
	 * @return string The full directory path.
	 */
	protected function getDir(string $dir, string $moduleName, ?string $featurePath = null): string
	{
		$moduleDir = $this->getModuleDir($moduleName, $featurePath);
		return $moduleDir . DIRECTORY_SEPARATOR . 'Gateway';
	}
}