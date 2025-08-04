<?php declare(strict_types=1);
namespace Proto\Generators;

/**
 * File Generator Interface
 *
 * This interface defines the methods that a file generator must implement.
 *
 * @package Proto\Generators
 */
interface FileGeneratorInterface
{
	/**
	 * Generate the file/resource.
	 *
	 * @param object $settings The settings for file generation.
	 * @return bool True on success, false otherwise.
	 */
	public function generate(object $settings): bool;
}
