<?php declare(strict_types=1);
namespace Proto\Models\Data;

/**
 * Abstract Class AbstractMapper
 *
 * Provides base functionality for mapping property keys.
 *
 * @package Proto\Models\Data
 */
abstract class AbstractMapper
{
	/** @var array Alias mappings. */
	protected array $alias = [];

	/**
	 * Checks if a field has an alias and returns the mapped key.
	 *
	 * @param mixed $field Field name or [original, alias] pair.
	 * @return string
	 */
	public function checkAliasField(mixed $field): string
	{
		if (!is_array($field))
		{
			return $this->convert($field);
		}

		$this->alias[$field[1]] = is_array($field[0])
			? $field[0]
			: $this->convert($field[0]);

		return $field[1];
	}

	/**
	 * Returns the mapped field name.
	 *
	 * @param string $field Field name.
	 * @return string
	 */
	public function getMappedField(string $field): string
	{
		return $this->alias[$field] ?? $field;
	}

	/**
	 * Maps a key using the conversion method.
	 *
	 * @param string $key The key to map.
	 * @return string
	 */
	public function mapKey(string $key): string
	{
		return $this->convert($key);
	}

	/**
	 * Converts a string according to the mapping strategy.
	 *
	 * @param string $str Input string.
	 * @return string
	 */
	abstract protected function convert(string $str): string;
}