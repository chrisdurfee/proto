<?php declare(strict_types=1);
namespace Proto\Generators;

use Proto\Utils\Files\File;
use Proto\Utils\Strings;

/**
 * Abstract File Generator
 *
 * This class provides common methods for file generators.
 *
 * @package Proto\Generators
 * @abstract
 */
abstract class AbstractFileGenerator implements FileGeneratorInterface
{
	/**
	 * Saves a file.
	 *
	 * @param string $dir Directory path.
	 * @param string $fileName File name.
	 * @param mixed $content File content.
	 * @return bool True on success, false if the file already exists.
	 */
	protected function saveFile(string $dir, string $fileName, mixed $content): bool
	{
		$path = $this->convertSlashes($dir . '/' . $fileName);
		if (file_exists($path))
        {
			return false;
		}
		File::put($path, (string)$content);
		return true;
	}

	/**
	 * Gets a file name.
	 *
	 * @param string $str The base string.
	 * @return string The generated file name.
	 */
	protected function getFileName(string $str): string
	{
		return Strings::pascalCase($str) . '.php';
	}

	/**
	 * Converts slashes in a path.
	 *
	 * @param string $path The path to convert.
	 * @return string The converted path.
	 */
	protected function convertSlashes(string $path): string
	{
		return str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $path);
	}

	/**
	 * Checks if the environment is Docker.
	 *
	 * @return bool True if in Docker environment, false otherwise.
	 */
	protected function isDockerEnvironment(): bool
	{
		return getenv('DOCKER_CONTAINER') === 'true' || file_exists('/.dockerenv');
	}

	/**
	 * Gets the full directory path for a module with Docker environment support.
	 *
	 * @param string $module The module name.
	 * @return string The full directory path.
	 */
	protected function getModuleDir(string $module): string
	{
		// Check if we're running in Docker container
		if ($this->isDockerEnvironment())
		{
			return $this->getDockerModuleDir($module);
		}

		// Standard local environment behavior
		return $this->getLocalModuleDir($module);
	}

	/**
	 * Gets the base path for the project.
	 *
	 * @return string The base path.
	 */
	protected function getBasePath(): string
	{
		return getenv('PROJECT_ROOT') ?: BASE_PATH;
	}

	/**
	 * Gets the module directory for Docker environments.
	 *
	 * @param string $module The module name.
	 * @return string The full directory path.
	 */
	protected function getDockerModuleDir(string $module): string
	{
		$basePath = $this->getBasePath();

		switch (strtolower($module))
		{
			case 'common':
				$path = $basePath . '/common';
				break;
			case 'proto':
				$path = $basePath . '/vendor/protoframework/proto/src';
				break;
			default:
				$path = $basePath . '/modules/' . $module;
		}

		// Ensure the directory exists
		File::checkDir($path);

		return $path;
	}

	/**
	 * Gets the module directory for local environments.
	 *
	 * @param string $module The module name.
	 * @return string The full directory path.
	 */
	protected function getLocalModuleDir(string $module): string
	{
		switch (strtolower($module))
		{
			case 'common':
				return realpath(BASE_PATH . '/common');
			case 'proto':
				return realpath(BASE_PATH . '/vendor/protoframework/proto/src');
			default:
				return (realpath(BASE_PATH . '/modules') . DIRECTORY_SEPARATOR . $module);
		}
	}

	/**
	 * Returns the full directory path where the file should be saved.
	 *
	 * This method must be implemented in the concrete file generator.
	 *
	 * @param string $dir A relative directory name.
	 * @param string $module The module name.
	 * @return string The full directory path.
	 */
	abstract protected function getDir(string $dir, string $module): string;
}
