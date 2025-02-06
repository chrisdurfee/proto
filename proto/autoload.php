<?php declare(strict_types=1);

/**
 * Autoloads class files using the namespace as the path.
 *
 * Registers with spl_autoload_register() to autoload classes by converting
 * the namespace to a file path and including the corresponding PHP file.
 */
spl_autoload_register(function(string $class): void
{
	// Convert namespace to path (replace \ with /)
	$path = str_replace('\\', DIRECTORY_SEPARATOR, $class);

	// Convert all folder names to lowercase, except for the file itself
	$segments = explode(DIRECTORY_SEPARATOR, $path);
	$segmentsCount = count($segments);

	// Loop through and convert each folder name (except the last, which is the file)
	for ($i = 0; $i < $segmentsCount - 1; $i++)
	{
		$segments[$i] = strtolower($segments[$i]);
	}

	// Reconstruct the correct path
	$finalPath = __DIR__ . DIRECTORY_SEPARATOR . ".." . DIRECTORY_SEPARATOR . implode(DIRECTORY_SEPARATOR, $segments) . ".php";

	// Require the file if it exists
	if (file_exists($finalPath))
	{
		require_once $finalPath;
	}
});