<?php declare(strict_types=1);

/**
 * Autoloads classes using the new path format.
 *
 * This autoloader converts the namespace to a file path by replacing
 * backslashes with directory separators, converting all folder names (except
 * the file name) to lowercase with dashes for camelCase parts, and preserving
 * the original file name (in PascalCase). It then checks multiple base
 * directories for the file.
 */
spl_autoload_register(function(string $class): void
{
	// Replace namespace separators with directory separators
	$path = str_replace('\\', DIRECTORY_SEPARATOR, $class);

	// Split the path into segments
	$segments = explode(DIRECTORY_SEPARATOR, $path);
	$segmentsCount = count($segments);

	// Convert folder names (all segments except the last) to lowercase with dashed notation
	for ($i = 0; $i < $segmentsCount - 1; $i++)
	{
		$segments[$i] = strtolower(preg_replace('/(?<!^)[A-Z]/', '-$0', $segments[$i]));
	}

	// Reconstruct the file path and append the ".php" extension
	$fileName = implode(DIRECTORY_SEPARATOR, $segments) . '.php';

	// Define base directories to search
	$baseDirs = [
		__DIR__ . DIRECTORY_SEPARATOR . ".." . DIRECTORY_SEPARATOR,                       // Primary base (proto autoload base)
		__DIR__ . DIRECTORY_SEPARATOR . ".." . DIRECTORY_SEPARATOR . ".." . DIRECTORY_SEPARATOR . ".." . DIRECTORY_SEPARATOR, // ../../../
		__DIR__ . DIRECTORY_SEPARATOR . ".." . DIRECTORY_SEPARATOR . ".." . DIRECTORY_SEPARATOR,             // ../../
	];

	// Loop through each base directory and require the file if found
	foreach ($baseDirs as $baseDir)
	{
		$finalPath = $baseDir . $fileName;
		if (file_exists($finalPath))
		{
			require_once $finalPath;
			return;
		}
	}
});