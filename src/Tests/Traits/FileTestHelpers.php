<?php declare(strict_types=1);
namespace Proto\Tests\Traits;

/**
 * FileTestHelpers
 *
 * Provides file system testing utilities for test cases.
 *
 * @package Proto\Tests\Traits
 */
trait FileTestHelpers
{
	/**
	 * @var array $testFiles Track created test files for cleanup
	 */
	protected array $testFiles = [];

	/**
	 * @var array $testDirectories Track created test directories for cleanup
	 */
	protected array $testDirectories = [];

	/**
	 * Creates a test file with content.
	 *
	 * @param string $path
	 * @param string $content
	 * @return void
	 */
	protected function createTestFile(string $path, string $content = ''): void
	{
		$directory = dirname($path);
		if (!is_dir($directory))
		{
			mkdir($directory, 0755, true);
		}

		file_put_contents($path, $content);
		$this->testFiles[] = $path;
	}

	/**
	 * Creates a test directory.
	 *
	 * @param string $path
	 * @param int $permissions
	 * @return void
	 */
	protected function createTestDirectory(string $path, int $permissions = 0755): void
	{
		if (!is_dir($path))
		{
			mkdir($path, $permissions, true);
		}
		$this->testDirectories[] = $path;
	}

	/**
	 * Asserts that a file exists.
	 *
	 * @param string $path
	 * @param string $message
	 * @return void
	 */
	protected function assertTestFileExists(string $path, string $message = ''): void
	{
		$this->assertTrue(file_exists($path),
			$message ?: "Failed asserting that file [{$path}] exists"
		);
	}

	/**
	 * Asserts that a file does not exist.
	 *
	 * @param string $path
	 * @param string $message
	 * @return void
	 */
	protected function assertTestFileNotExists(string $path, string $message = ''): void
	{
		$this->assertFalse(file_exists($path),
			$message ?: "Failed asserting that file [{$path}] does not exist"
		);
	}

	/**
	 * Asserts that a file contains specific content.
	 *
	 * @param string $path
	 * @param string $content
	 * @param string $message
	 * @return void
	 */
	protected function assertFileContains(string $path, string $content, string $message = ''): void
	{
		$this->assertTestFileExists($path);
		$fileContent = file_get_contents($path);
		$this->assertStringContainsString($content, $fileContent,
			$message ?: "Failed asserting that file [{$path}] contains expected content"
		);
	}

	/**
	 * Asserts that a file does not contain specific content.
	 *
	 * @param string $path
	 * @param string $content
	 * @param string $message
	 * @return void
	 */
	protected function assertFileNotContains(string $path, string $content, string $message = ''): void
	{
		$this->assertTestFileExists($path);
		$fileContent = file_get_contents($path);
		$this->assertStringNotContainsString($content, $fileContent,
			$message ?: "Failed asserting that file [{$path}] does not contain specific content"
		);
	}

	/**
	 * Asserts that a file has specific permissions.
	 *
	 * @param string $path
	 * @param int $permissions
	 * @param string $message
	 * @return void
	 */
	protected function assertFilePermissions(string $path, int $permissions, string $message = ''): void
	{
		$this->assertTestFileExists($path);
		$actualPermissions = fileperms($path) & 0777;
		$this->assertEquals($permissions, $actualPermissions,
			$message ?: "Failed asserting that file [{$path}] has permissions " . decoct($permissions)
		);
	}

	/**
	 * Asserts that a directory exists.
	 *
	 * @param string $path
	 * @param string $message
	 * @return void
	 */
	protected function assertTestDirectoryExists(string $path, string $message = ''): void
	{
		$this->assertTrue(is_dir($path),
			$message ?: "Failed asserting that directory [{$path}] exists"
		);
	}

	/**
	 * Asserts that a directory does not exist.
	 *
	 * @param string $path
	 * @param string $message
	 * @return void
	 */
	protected function assertTestDirectoryNotExists(string $path, string $message = ''): void
	{
		$this->assertFalse(is_dir($path),
			$message ?: "Failed asserting that directory [{$path}] does not exist"
		);
	}

	/**
	 * Asserts that a directory is empty.
	 *
	 * @param string $path
	 * @param string $message
	 * @return void
	 */
	protected function assertDirectoryEmpty(string $path, string $message = ''): void
	{
		$this->assertTestDirectoryExists($path);
		$files = array_diff(scandir($path), ['.', '..']);
		$this->assertEmpty($files,
			$message ?: "Failed asserting that directory [{$path}] is empty"
		);
	}

	/**
	 * Asserts that a directory contains a specific file.
	 *
	 * @param string $directory
	 * @param string $filename
	 * @param string $message
	 * @return void
	 */
	protected function assertDirectoryContainsFile(string $directory, string $filename, string $message = ''): void
	{
		$this->assertTestDirectoryExists($directory);
		$filePath = rtrim($directory, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $filename;
		$this->assertTestFileExists($filePath,
			$message ?: "Failed asserting that directory [{$directory}] contains file [{$filename}]"
		);
	}

	/**
	 * Gets the content of a file.
	 *
	 * @param string $path
	 * @return string
	 */
	protected function getFileContent(string $path): string
	{
		$this->assertTestFileExists($path);
		return file_get_contents($path) ?: '';
	}

	/**
	 * Gets the size of a file in bytes.
	 *
	 * @param string $path
	 * @return int
	 */
	protected function getFileSize(string $path): int
	{
		$this->assertTestFileExists($path);
		return filesize($path) ?: 0;
	}

	/**
	 * Copies a file for testing.
	 *
	 * @param string $source
	 * @param string $destination
	 * @return void
	 */
	protected function copyFileForTest(string $source, string $destination): void
	{
		$this->assertTestFileExists($source);

		$directory = dirname($destination);
		if (!is_dir($directory))
		{
			mkdir($directory, 0755, true);
		}

		copy($source, $destination);
		$this->testFiles[] = $destination;
	}

	/**
	 * Cleans up created test files and directories.
	 *
	 * @return void
	 */
	protected function cleanupTestFiles(): void
	{
		// Clean up files first
		foreach ($this->testFiles as $file)
		{
			if (file_exists($file))
			{
				unlink($file);
			}
		}

		// Clean up directories (in reverse order)
		foreach (array_reverse($this->testDirectories) as $directory)
		{
			if (is_dir($directory))
			{
				$this->removeTestDirectory($directory);
			}
		}

		$this->testFiles = [];
		$this->testDirectories = [];
	}

	/**
	 * Recursively removes a test directory.
	 *
	 * @param string $directory
	 * @return void
	 */
	protected function removeTestDirectory(string $directory): void
	{
		if (!is_dir($directory))
		{
			return;
		}

		$files = array_diff(scandir($directory), ['.', '..']);
		foreach ($files as $file)
		{
			$path = $directory . DIRECTORY_SEPARATOR . $file;
			if (is_dir($path))
			{
				$this->removeTestDirectory($path);
			}
			else
			{
				unlink($path);
			}
		}
		rmdir($directory);
	}
}