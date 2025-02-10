<?php declare(strict_types=1);
namespace Proto\Database\QueryBuilder;

/**
 * Update
 *
 * This class handles update queries.
 *
 * @package Proto\Database\QueryBuilder
 */
class Update extends Query
{
	/**
	 * The fields to update.
	 *
	 * @var string[]
	 */
	protected array $fields = [];

	/**
	 * Updates the table with the given fields.
	 *
	 * @param array|object|string ...$fields The fields to update.
	 * @return self
	 */
	public function update(...$fields): self
	{
		$this->set(...$fields);
		return $this;
	}

	/**
	 * Sets the fields to be updated.
	 *
	 * @param mixed ...$fields The fields to set.
	 * @return self
	 */
	public function set(...$fields): self
	{
		if (count($fields) < 1)
		{
			return $this;
		}

		foreach ($fields as $row)
		{
			if (is_string($row))
			{
				$fieldList = explode(',', $row);
				$this->fields = array_merge($this->fields, $fieldList);
				continue;
			}

			foreach ($row as $key => $value)
			{
				$this->fields[] = "{$key} = {$value}";
			}
		}

		return $this;
	}

	/**
	 * Renders the update query.
	 *
	 * @return string The rendered SQL query.
	 */
	public function render(): string
	{
		$table = $this->getTableString();
		$joins = implode(' ', $this->joins);
		$fieldString = implode(', ', $this->fields);
		$where = $this->getPropertyString($this->conditions, ' WHERE ', ' AND ');
		$orderBy = $this->getPropertyString($this->orderBy, ' ORDER BY ', ', ');
		$query = "UPDATE {$table} {$joins} SET {$fieldString}{$where}{$orderBy}{$this->limit};";
		return trim(preg_replace('/\s+/', ' ', $query));
	}
}