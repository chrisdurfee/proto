<?php declare(strict_types=1);

/**
 * Autoloads class files using the namespace as the path.
 *
 * This function will register with spl_autoload_register() to autoload
 * classes by converting the namespace to a file path and including
 * the corresponding PHP file.
 */
spl_autoload_register(function(string $class)
{
	// Replace namespace separator with the directory separator
	$class = str_replace('\\', '/', $class);

	// Convert camel case to kebab case (e.g. ClassName -> class-name)
	$fileName = strtolower(preg_replace('/([a-z]|[0-9])([A-Z])/', '\\1-\\2', $class));

	// Construct the file path
	$path = __DIR__ . "/../{$fileName}.php";

	// Include the file if it exists
	if (file_exists($path))
	{
		include $path;
	}
});
