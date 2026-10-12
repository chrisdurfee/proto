<?php declare(strict_types=1);

namespace Proto\Tests\Unit\Api;

use Proto\Api\FileValidator;
use Proto\Tests\Test;

/**
 * FileValidatorTest
 *
 * Upload validation must judge the detected file type, never the type
 * the client declared, and must run the content check even with the
 * default MIME list.
 *
 * @package Proto\Tests\Unit\Api
 */
final class FileValidatorTest extends Test
{
	/**
	 * @var bool
	 */
	protected bool $useTransactions = false;

	/**
	 * @var array<int, string>
	 */
	private array $uploadFiles = [];

	/**
	 * @return void
	 */
	protected function tearDown(): void
	{
		foreach ($this->tempFiles as $path)
		{
			@unlink($path);
		}

		parent::tearDown();
	}

	/**
	 * @return void
	 */
	public function testSvgDeclaredAsPngIsRejected(): void
	{
		$svg = '<?xml version="1.0"?><svg xmlns="http://www.w3.org/2000/svg" onload="alert(1)"><rect width="1" height="1"/></svg>';
		$result = FileValidator::validate($this->upload('x.svg', 'image/png', $svg));

		$this->assertFalse($result['valid']);
	}

	/**
	 * @return void
	 */
	public function testHtmlDeclaredAsPngIsRejectedWithExplicitImageList(): void
	{
		$html = '<!DOCTYPE html><html><body><script>alert(1)</script></body></html>';
		$result = FileValidator::validate($this->upload('x.png', 'image/png', $html), null, ['image/png']);

		$this->assertFalse($result['valid']);
	}

	/**
	 * @return void
	 */
	public function testRealPngPasses(): void
	{
		$png = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNkYPhfDwAChwGA60e6kgAAAABJRU5ErkJggg==');
		$result = FileValidator::validate($this->upload('pixel.png', 'image/png', $png), null, ['image/png']);

		$this->assertTrue($result['valid'], implode('; ', $result['errors']));
	}

	/**
	 * CSV files (finfo: text/plain) still pass a CSV allow-list now that
	 * the declared type is ignored.
	 *
	 * @return void
	 */
	public function testCsvPassesCsvAllowList(): void
	{
		$result = FileValidator::validate($this->upload('people.csv', 'text/csv', "name,email\nAda,ada@example.com\n"), null, ['text/csv']);
		$this->assertTrue($result['valid'], implode('; ', $result['errors']));
	}

	/**
	 * A docx (zip container) passes a docx allow-list even when libmagic
	 * reports application/zip.
	 *
	 * @return void
	 */
	public function testDocxZipContainerPassesDocxAllowList(): void
	{
		if (!class_exists(\ZipArchive::class))
		{
			$this->markTestSkipped('ZipArchive not available.');
		}

		$path = (string)tempnam(sys_get_temp_dir(), 'docx');
		$zip = new \ZipArchive();
		$zip->open($path, \ZipArchive::OVERWRITE);
		$zip->addFromString('word/document.xml', '<w:document/>');
		$zip->close();
		$this->uploadFiles[] = $path;

		$mime = 'application/vnd.openxmlformats-officedocument.wordprocessingml.document';
		$result = FileValidator::validate([
			'name' => 'report.docx',
			'type' => $mime,
			'tmp_name' => $path,
			'error' => UPLOAD_ERR_OK,
			'size' => (int)filesize($path)
		], null, [$mime]);

		$this->assertTrue($result['valid'], implode('; ', $result['errors']));
	}

	/**
	 * libmagic aliases (audio/x-wav) match canonical allow-list entries.
	 *
	 * @return void
	 */
	public function testWavAliasMatchesDefaultList(): void
	{
		$wav = 'RIFF' . pack('V', 36) . 'WAVEfmt ' . pack('VvvVVvv', 16, 1, 1, 8000, 8000, 1, 8) . 'data' . pack('V', 0);
		$result = FileValidator::validate($this->upload('clip.wav', 'audio/wav', $wav));

		$this->assertTrue($result['valid'], implode('; ', $result['errors']));
		$this->assertSame('audio/wav', \Proto\Http\UploadFile::canonicalMime('audio/x-wav'));
		$this->assertSame('application/x-rar-compressed', \Proto\Http\UploadFile::canonicalMime('application/x-rar'));
	}

	/**
	 * @param string $name
	 * @param string $declaredType
	 * @param string $contents
	 * @return array<string, mixed>
	 */
	private function upload(string $name, string $declaredType, string $contents): array
	{
		$path = (string)tempnam(sys_get_temp_dir(), 'fv');
		file_put_contents($path, $contents);
		$this->tempFiles[] = $path;

		return [
			'name' => $name,
			'type' => $declaredType,
			'tmp_name' => $path,
			'error' => UPLOAD_ERR_OK,
			'size' => strlen($contents)
		];
	}
}
