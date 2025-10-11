<?php declare(strict_types=1);
namespace Proto\Tests\Traits;

use Proto\Tests\TestDataManager;
use Proto\Utils\Files\File;

/**
 * TestDataHelpers
 *
 * Provides test data management utilities for test cases.
 *
 * @package Proto\Tests\Traits
 */
trait TestDataHelpers
{
	/**
	 * @var TestDataManager|null $dataManager
	 */
	protected ?TestDataManager $dataManager = null;

	/**
	 * @var array $tempFiles
	 */
	protected array $tempFiles = [];

	/**
	 * Gets test data by key.
	 *
	 * @param string $key
	 * @param mixed $default
	 * @return mixed
	 */
	protected function getTestData(string $key, mixed $default = null): mixed
	{
		return $this->getDataManager()->get($key, $default);
	}

	/**
	 * Sets test data.
	 *
	 * @param string $key
	 * @param mixed $value
	 * @return void
	 */
	protected function setTestData(string $key, mixed $value): void
	{
		$this->getDataManager()->set($key, $value);
	}

	/**
	 * Loads a fixture file.
	 *
	 * @param string $filename
	 * @return array
	 */
	protected function loadFixture(string $filename): array
	{
		$fixturePath = $this->getFixturePath($filename);

		if (!file_exists($fixturePath))
		{
			throw new \InvalidArgumentException("Fixture file not found: {$fixturePath}");
		}

		$extension = pathinfo($fixturePath, PATHINFO_EXTENSION);

		return match ($extension)
		{
			'json' => $this->loadJsonFixture($fixturePath),
			'php' => $this->loadPhpFixture($fixturePath),
			'yml', 'yaml' => $this->loadYamlFixture($fixturePath),
			default => throw new \InvalidArgumentException("Unsupported fixture format: {$extension}")
		};
	}

	/**
	 * Creates a temporary file with content.
	 *
	 * @param string $content
	 * @param string $extension
	 * @return string The file path
	 */
	protected function createTempFile(string $content, string $extension = 'txt'): string
	{
		$tempPath = sys_get_temp_dir() . '/proto_test_' . uniqid() . '.' . $extension;
		File::put($tempPath, $content);

		$this->tempFiles[] = $tempPath;
		return $tempPath;
	}

	/**
	 * Creates a temporary directory.
	 *
	 * @return string The directory path
	 */
	protected function createTempDirectory(): string
	{
		$tempPath = sys_get_temp_dir() . '/proto_test_dir_' . uniqid();
		$PERMISSIONS = 0777;
		File::makeDir($tempPath, $PERMISSIONS);

		$this->tempFiles[] = $tempPath;
		return $tempPath;
	}

	/**
	 * Cleans up temporary files.
	 *
	 * @return void
	 */
	protected function cleanupTempFiles(): void
	{
		foreach ($this->tempFiles as $path)
		{
			if (is_file($path))
			{
				unlink($path);
			}
			elseif (is_dir($path))
			{
				$this->removeDirectory($path);
			}
		}
		$this->tempFiles = [];
	}

	/**
	 * Generates fake data using a simple faker.
	 *
	 * @return object
	 */
	protected function fake(): object
	{
		return $this->getDataManager()->faker();
	}

	/**
	 * Gets or creates the data manager.
	 *
	 * @return TestDataManager
	 */
	protected function getDataManager(): TestDataManager
	{
		if ($this->dataManager === null)
		{
			$this->dataManager = new TestDataManager();
		}
		return $this->dataManager;
	}

	/**
	 * Gets the fixture file path.
	 *
	 * @param string $filename
	 * @return string
	 */
	protected function getFixturePath(string $filename): string
	{
		// Look for fixtures in common locations
		$possiblePaths = [
			dirname((new \ReflectionClass($this))->getFileName()) . '/fixtures/' . $filename,
			BASE_PATH . '/tests/fixtures/' . $filename,
			BASE_PATH . '/src/Tests/fixtures/' . $filename,
		];

		foreach ($possiblePaths as $path)
		{
			if (file_exists($path))
			{
				return $path;
			}
		}

		// Default path if not found
		return dirname((new \ReflectionClass($this))->getFileName()) . '/fixtures/' . $filename;
	}

	/**
	 * Loads a JSON fixture.
	 *
	 * @param string $path
	 * @return array
	 */
	protected function loadJsonFixture(string $path): array
	{
		$content = File::get($path);
		$data = json_decode($content, true);

		if (json_last_error() !== JSON_ERROR_NONE)
		{
			throw new \RuntimeException("Invalid JSON in fixture: " . json_last_error_msg());
		}

		return $data ?? [];
	}

	/**
	 * Loads a PHP fixture.
	 *
	 * @param string $path
	 * @return array
	 */
	protected function loadPhpFixture(string $path): array
	{
		$data = include $path;
		return is_array($data) ? $data : [];
	}

	/**
	 * Loads a YAML fixture.
	 *
	 * @param string $path
	 * @return array
	 */
	protected function loadYamlFixture(string $path): array
	{
		if (!function_exists('yaml_parse_file'))
		{
			throw new \RuntimeException("YAML extension is required to parse YAML fixtures");
		}

		$data = yaml_parse_file($path);
		return is_array($data) ? $data : [];
	}

	/**
	 * Recursively removes a directory.
	 *
	 * @param string $dir
	 * @return void
	 */
	protected function removeDirectory(string $dir): void
	{
		if (!is_dir($dir))
		{
			return;
		}

		$files = array_diff(scandir($dir), ['.', '..']);
		foreach ($files as $file)
		{
			$path = $dir . DIRECTORY_SEPARATOR . $file;
			if (is_dir($path))
			{
				$this->removeDirectory($path);
			}
			else
			{
				unlink($path);
			}
		}
		rmdir($dir);
	}
}