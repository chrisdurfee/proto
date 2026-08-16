<?php declare(strict_types=1);

namespace Proto\Tests\Unit\Utils\Files;

use Proto\Http\UploadFile;
use Proto\Tests\Test;
use Proto\Utils\Files\File;

/**
 * FileExtensionAllowlistTest
 *
 * Verifies the optional extension allowlist on {@see File::createNewName()}
 * and {@see UploadFile}: default `null` keeps the original unrestricted
 * behavior (BC); providing a list enforces it.
 *
 * @package Proto\Tests\Unit\Utils\Files
 */
final class FileExtensionAllowlistTest extends Test
{
	/**
	 * @var bool
	 */
	protected bool $useTransactions = false;

	/**
	 * Default behavior (no allowlist) is unchanged: any extension passes.
	 *
	 * @return void
	 */
	public function testCreateNewNameWithoutAllowlistAcceptsAnyExtension(): void
	{
		$name = File::createNewName('malicious.php');
		$this->assertStringEndsWith('.php', $name);
	}

	/**
	 * @return void
	 */
	public function testCreateNewNameWithAllowlistAcceptsListedExtension(): void
	{
		$name = File::createNewName('photo.JPG', ['jpg', 'png', 'gif']);
		$this->assertStringEndsWith('.JPG', $name);
	}

	/**
	 * @return void
	 */
	public function testCreateNewNameWithAllowlistRejectsUnlistedExtension(): void
	{
		$this->expectException(\InvalidArgumentException::class);
		File::createNewName('malicious.php', ['jpg', 'png', 'gif']);
	}

	/**
	 * UploadFile passes its optional allowlist through to
	 * File::createNewName(); an allowed extension still constructs and
	 * stores under a new unique name.
	 *
	 * @return void
	 */
	public function testUploadFileConstructsWithAllowedExtension(): void
	{
		$tmpPath = $this->makeTempUploadSource('hello world');

		$uploadFile = new UploadFile([
			'name' => 'document.txt',
			'type' => 'text/plain',
			'tmp_name' => $tmpPath,
			'error' => 0,
			'size' => 11
		], ['txt', 'csv']);

		$this->assertStringEndsWith('.txt', $uploadFile->getNewName());
		$this->assertTestFileExists($uploadFile->getFilePath());
		$this->testFiles[] = $uploadFile->getFilePath();
	}

	/**
	 * A disallowed extension throws instead of silently storing the
	 * file — this is the enforcement point for code calling
	 * `UploadFile`/`store()` directly, bypassing FileValidator/ImageValidator.
	 *
	 * @return void
	 */
	public function testUploadFileConstructsRejectsDisallowedExtension(): void
	{
		$tmpPath = $this->makeTempUploadSource('#!/bin/sh');

		try
		{
			$this->expectException(\InvalidArgumentException::class);
			new UploadFile([
				'name' => 'script.sh',
				'type' => 'application/x-sh',
				'tmp_name' => $tmpPath,
				'error' => 0,
				'size' => 9
			], ['jpg', 'png']);
		}
		finally
		{
			// The constructor throws before renaming, so the original tmp file remains.
			if (file_exists($tmpPath))
			{
				unlink($tmpPath);
			}
		}
	}

	/**
	 * @param string $contents
	 * @return string Path to a temp file simulating an uploaded tmp_name.
	 */
	private function makeTempUploadSource(string $contents): string
	{
		$path = sys_get_temp_dir() . DIRECTORY_SEPARATOR . uniqid('proto_upload_test_') . '.tmp';
		file_put_contents($path, $contents);
		return $path;
	}
}
