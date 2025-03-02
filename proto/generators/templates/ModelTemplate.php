<?php declare(strict_types=1);
namespace Proto\Generators\Templates;

/**
 * ModelTemplate
 *
 * This class generates a model template.
 *
 * @package Proto\Generators\Templates
 */
class ModelTemplate extends ClassTemplate
{
	/**
	 * Retrieves the extends string.
	 *
	 * @return string
	 */
	protected function getExtends(): string
	{
		$extends = $this->get('extends');
		return 'extends ' . (!empty($extends) ? $extends : 'Model');
	}

	/**
	 * Retrieves the storage type property.
	 *
	 * @return string
	 */
	protected function getStorage(): string
	{
		$storage = $this->get('storage');
		if (empty($storage))
		{
			return '';
		}

		$storageName = $this->getStorageClassName();
		$property = $this->getProtectedProperty('$storageType', $storageName, true);

		return <<<EOT

/**
	 * @var string \$storageType
	 */
	{$property}
EOT;
	}

	/**
	 * Retrieves the storage class name.
	 *
	 * @return string
	 */
	protected function getStorageName(): string
	{
		return $this->get('className') . 'Storage';
	}

	/**
	 * Retrieves the storage class reference.
	 *
	 * @return string
	 */
	protected function getStorageClassName(): string
	{
		return $this->getStorageName() . '::class';
	}

	/**
	 * Retrieves the table name property.
	 *
	 * @return string
	 */
	protected function getTable(): string
	{
		$property = $this->getProtectedProperty('$tableName', $this->quote($this->get('tableName')), true);

		return <<<EOT
	/**
	 * @var string \$tableName
	 */
	{$property}
EOT;
	}

	/**
	 * Retrieves the alias property.
	 *
	 * @return string
	 */
	protected function getAlias(): string
	{
		$property = $this->getProtectedProperty('$alias', $this->quote($this->get('alias')), true);

		return <<<EOT
/**
	 * @var string \$alias
	 */
	{$property}
EOT;
	}

	/**
	 * Retrieves the table fields property.
	 *
	 * @return string
	 */
	protected function getFields(): string
	{
		$fields = $this->get('fields');
		if (empty($fields))
		{
			return '';
		}

		$values = implode(",\n\t\t", array_map(fn($field) => "'{$field}'", $fields));
		$columns = "[\n\t\t{$values}\n\t]";

		$property = $this->getProtectedProperty('$fields', $columns, true);

		return <<<EOT
/**
	 * @var array \$fields
	 */
	{$property}
EOT;
	}

	/**
	 * Retrieves the model joins method.
	 *
	 * @return string
	 */
	protected function getJoins(): string
	{
		$joins = $this->get('joins');
		if (empty($joins))
		{
			return '';
		}

		return <<<EOT
/**
	 * Sets up the model joins.
	 *
	 * @param object \$builder
	 * @return void
	 */
	protected static function joins(\$builder)
	{
		{$joins}
	}
EOT;
	}

	/**
	 * Retrieves the use statement for the storage.
	 *
	 * @return string
	 */
	protected function getUse(): string
	{
		$storage = $this->get('storage');
		if (empty($storage))
		{
			return '';
		}

		$storageName = $this->getNamespace() . $this->getStorageName();
		return "use Common\\Storage\\{$storageName};";
	}

	/**
	 * Retrieves the class content.
	 *
	 * @return string
	 */
	protected function getClassContent(): string
	{
		$sections = array_filter([
			$this->getTable(),
			$this->getAlias(),
			$this->getFields(),
			$this->getJoins(),
			$this->getStorage()
		]);

		return implode("\n\t", $sections);
	}
}