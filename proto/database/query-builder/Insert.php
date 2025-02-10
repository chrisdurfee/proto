<?php declare(strict_types=1);

namespace Proto\Database\QueryBuilder;

/**
 * Insert
 *
 * This class handles the insert query.
 *
 * @package Proto\Database\QueryBuilder
 */
class Insert extends Query
{
	/**
	 * The list of fields.
	 *
	 * @var string[]
	 */
	protected array $fields = [];

	/**
	 * The list of values.
	 *
	 * @var string[]
	 */
	protected array $values = [];

	/**
	 * The conditions for the query.
	 *
	 * @var array
	 */
	protected array $conditions = [];

	/**
	 * The ON DUPLICATE KEY UPDATE clause.
	 *
	 * @var string
	 */
	protected string $onDuplicate = '';

	/**
	 * Inserts data into the table.
	 *
	 * @param array|object|null $data The data to insert.
	 * @return self
	 */
	public function insert(array|object|null $data = null): self
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
	 * Sets the fields.
	 *
	 * @param string[] $fields The fields.
	 * @return self
	 */
	public function fields(array $fields): self
	{
		$this->fields = $fields;
		return $this;
	}

	/**
	 * Sets the values.
	 *
	 * @param string[] $values The values.
	 * @return self
	 */
	public function values(array $values): self
	{
		$this->values = $values;
		return $this;
	}

	/**
	 * Creates placeholders for the values.
	 *
	 * @param array $values The values.
	 * @return string[] The placeholders.
	 */
	protected function createPlaceholders(array $values): array
	{
		return array_fill(0, count($values), '?');
	}

	/**
	 * Binds the fields to parameters.
	 *
	 * @param array|object|null $data The data to bind.
	 * @param array &$params The parameter array.
	 * @return self
	 */
	public function bind(array|object|null $data, array &$params = []): self
	{
		if ($data === null)
		{
			return $this;
		}

		$dataParams = $this->createParamsFromData($data);
		$this->fields = $dataParams->cols;
		$this->values = $this->createPlaceholders($dataParams->values);
		$params = array_merge($params, $dataParams->values);
		return $this;
	}

	/**
	 * Creates the duplicate field string.
	 *
	 * @param string $key The field name.
	 * @param string $value The value.
	 * @return string The duplicate field string.
	 */
	protected function getDuplicateField(string $key, string $value): string
	{
		return "{$key} => VALUES({$value})";
	}

	/**
	 * Adds an ON DUPLICATE KEY UPDATE clause.
	 *
	 * @param array $updateFields The fields to update.
	 * @return self
	 */
	public function onDuplicate(array $updateFields): self
	{
		if (count($updateFields) === 0)
		{
			return $this;
		}

		$fields = [];
		foreach ($updateFields as $key => $value)
		{
			$fields[] = $this->getDuplicateField($key, $value);
		}

		$this->onDuplicate = ' ON DUPLICATE KEY UPDATE ' . implode(', ', $fields);
		return $this;
	}

	/**
	 * Renders the insert query.
	 *
	 * @return string The rendered SQL query.
	 */
	public function render(): string
	{
		$fields = implode(', ', $this->fields);
		$values = implode(', ', $this->values);
		$duplicate = $this->onDuplicate;
		return "INSERT INTO {$this->tableName} ({$fields}) VALUES ({$values}){$duplicate};";
	}
}