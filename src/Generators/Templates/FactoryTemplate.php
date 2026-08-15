<?php declare(strict_types=1);
namespace Proto\Generators\Templates;

/**
 * FactoryTemplate
 *
 * Generates a model factory class.
 *
 * @package Proto\Generators\Templates
 */
class FactoryTemplate extends ClassTemplate
{
	/**
	 * Retrieves the extends string.
	 *
	 * @return string
	 */
	protected function getExtends(): string
	{
		$extends = $this->get('extends');
		return 'extends ' . (!empty($extends) ? $extends : 'Factory');
	}

	/**
	 * Retrieves the factory class name.
	 *
	 * @return string
	 */
	protected function getClassName(): string
	{
		return $this->get('className') . 'Factory';
	}

	/**
	 * Retrieves the use statements.
	 *
	 * @return string
	 */
	protected function getUse(): string
	{
		return 'use Proto\\Models\\Factory;';
	}

	/**
	 * Build definition entries from model fields.
	 *
	 * @return string
	 */
	protected function getDefinitionBody(): string
	{
		$fields = $this->get('fields');
		$skip = ['id', 'createdAt', 'updatedAt', 'deletedAt', 'createdBy', 'updatedBy', 'deletedBy'];
		$lines = [];
		if (is_array($fields))
		{
			foreach ($fields as $field)
			{
				$field = (string)$field;
				if (in_array($field, $skip, true))
				{
					continue;
				}

				$lines[] = $this->definitionLine($field);
			}
		}

		if ($lines === [])
		{
			$lines[] = "'status' => 'active'";
		}

		return implode(",\n\t\t\t", $lines);
	}

	/**
	 * @param string $field
	 * @return string
	 */
	protected function definitionLine(string $field): string
	{
		$lower = strtolower($field);
		$value = match (true)
		{
			$lower === 'email' => '$faker->email()',
			$lower === 'username' => '$faker->username()',
			str_contains($lower, 'firstname') => '$faker->firstName()',
			str_contains($lower, 'lastname') => '$faker->lastName()',
			str_contains($lower, 'name') => '$faker->name()',
			str_contains($lower, 'title') => '$faker->sentence()',
			str_contains($lower, 'guid') || $lower === 'uuid' => '$faker->uuid()',
			$lower === 'status' => "'active'",
			default => '$faker->word()'
		};

		return "'{$field}' => {$value}";
	}

	/**
	 * Retrieves the class content.
	 *
	 * @return string
	 */
	protected function getClassContent(): string
	{
		$className = $this->get('className');
		$definition = $this->getDefinitionBody();

		return <<<EOT
	/**
	 * Get the model class.
	 *
	 * @return string
	 */
	protected function model(): string
	{
		return {$className}::class;
	}

	/**
	 * Define the model's default state.
	 *
	 * @return array
	 */
	public function definition(): array
	{
		\$faker = \$this->faker();

		return [
			{$definition}
		];
	}
EOT;
	}
}
