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
