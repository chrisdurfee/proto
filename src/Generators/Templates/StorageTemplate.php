<?php declare(strict_types=1);
namespace Proto\Generators\Templates;

/**
 * StorageTemplate
 *
 * This class generates a storage template.
 *
 * @package Proto\Generators\Templates
 */
class StorageTemplate extends ClassTemplate
{
	/**
	 * Retrieves the extends string.
	 *
	 * @return string
	 */
	protected function getExtends(): string
	{
		$extends = $this->get('extends');
		return 'extends ' . (!empty($extends) ? $extends : 'Storage');
	}

	/**
	 * Retrieves the connection property.
	 *
	 * @return string
	 */
	protected function getConnection(): string
	{
		$connection = $this->get('connection');
		if (empty($connection))
		{
			return '';
		}

		$property = $this->getProtectedProperty('$connection', $this->quote($connection), true);

		return <<<EOT
/**
	 * @var string \$connection
	 */
	{$property}
EOT;
	}

	/**
	 * Retrieves the use statement for the model.
	 *
	 * @return string
	 */
	protected function getUse(): string
	{
		return "use Proto\\Storage\\Storage;";
	}

	/**
	 * Retrieves the storage class name.
	 *
	 * @return string
	 */
	protected function getClassName(): string
	{
		return $this->get('className') . 'Storage';
	}

	/**
	 * Retrieves the class content.
	 *
	 * @return string
	 */
	protected function getClassContent(): string
	{
		$connection = $this->getConnection();
		return !empty($connection) ? "\n\t{$connection}" : '';
	}
}