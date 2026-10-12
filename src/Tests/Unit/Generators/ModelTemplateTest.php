<?php declare(strict_types=1);

namespace Proto\Tests\Unit\Generators;

use Proto\Generators\Templates\ModelTemplate;
use Proto\Tests\Test;

/**
 * ModelTemplateTest
 *
 * Generated models must be valid PHP with a joins() signature that
 * matches the framework's documented convention.
 *
 * @package Proto\Tests\Unit\Generators
 */
final class ModelTemplateTest extends Test
{
	/**
	 * @var bool
	 */
	protected bool $useTransactions = false;

	/**
	 * @return void
	 */
	public function testGeneratedModelIsValidPhpWithTypedJoins(): void
	{
		$code = (string)(new ModelTemplate([
			'className' => 'Widget',
			'moduleName' => 'shop',
			'dir' => 'Models',
			'tableName' => 'widgets',
			'alias' => 'w',
			'fields' => ['id', 'name'],
			'joins' => '$builder->one(Other::class)->on([\'otherId\', \'id\']);'
		]));

		$this->assertStringContainsString('protected static function joins(object $builder): void', $code);
		$this->assertDoesNotMatchRegularExpression('/class Widget\s{2,}extends/', $code);

		$file = (string)tempnam(sys_get_temp_dir(), 'gen');
		file_put_contents($file, $code);
		exec(escapeshellarg(PHP_BINARY) . ' -l ' . escapeshellarg($file), $out, $status);
		@unlink($file);
		$this->assertSame(0, $status, implode("\n", $out) . "\n" . $code);
	}
}
