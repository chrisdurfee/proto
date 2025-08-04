<?php declare(strict_types=1);
namespace Proto\Database\QueryBuilder;

/**
 * Replace
 *
 * This class handles replace queries.
 *
 * @package Proto\Database\QueryBuilder
 */
class Replace extends Insert
{
	/**
	 * Replaces data into the table.
	 *
	 * @param array|object|null $data The data to replace.
	 * @return self
	 */
	public function replace(array|object|null $data = null): self
	{
		if ($data === null)
		{
			return $this;
		}

		$params = $this->createParamsFromData($data);
		$this->fields = $params->cols;
		$this->values = $params->values;
		return $this;
	}

	/**
	 * Renders the replace query.
	 *
	 * @return string The rendered SQL query.
	 */
	public function render(): string
	{
		$fields = implode(', ', $this->fields);
		$values = implode(', ', $this->values);
		return "REPLACE INTO {$this->tableName} ({$fields}) VALUES ({$values});";
	}
}