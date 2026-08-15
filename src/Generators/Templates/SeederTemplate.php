<?php declare(strict_types=1);
namespace Proto\Generators\Templates;

/**
 * SeederTemplate
 *
 * Generates a database seeder class.
 *
 * @package Proto\Generators\Templates
 */
class SeederTemplate extends ClassTemplate
{
	/**
	 * Retrieves the extends string.
	 *
	 * @return string
	 */
	protected function getExtends(): string
	{
		$extends = $this->get('extends');
		return 'extends ' . (!empty($extends) ? $extends : 'Seeder');
	}

	/**
	 * Retrieves the seeder class name.
	 *
	 * @return string
	 */
	protected function getClassName(): string
	{
		return $this->get('className') . 'Seeder';
	}

	/**
	 * Retrieves the use statements.
	 *
	 * @return string
	 */
	protected function getUse(): string
	{
		$dir = $this->getModuleDir();
		$className = $this->getNamespace() . $this->get('className');
		return "use Proto\\Database\\Seeders\\Seeder;
use {$dir}\\Models\\{$className};";
	}

	/**
	 * Retrieves the class content.
	 *
	 * @return string
	 */
	protected function getClassContent(): string
	{
		$className = $this->get('className');

		return <<<EOT
	/**
	 * Run the seeder.
	 *
	 * @return void
	 */
	public function run(): void
	{
		if (!method_exists({$className}::class, 'factory'))
		{
			return;
		}

		{$className}::factory()->count(10)->create();
	}
EOT;
	}
}
