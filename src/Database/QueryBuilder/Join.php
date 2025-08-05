<?php declare(strict_types=1);
namespace Proto\Database\QueryBuilder;

/**
 * Join
 *
 * This class handles join queries.
 *
 * @package Proto\Database\QueryBuilder
 */
class Join extends FieldQuery
{
	/**
	 * The join type.
	 *
	 * @var string
	 */
	protected string $type = 'JOIN';

	/**
	 * The USING clause, if provided.
	 *
	 * @var string
	 */
	protected string $using = '';

	/**
	 * The ON conditions.
	 *
	 * @var string[]
	 */
	protected array $on = [];

	/**
	 * Sets the join type.
	 *
	 * @param string $type The join type.
	 * @return self
	 */
	public function addType(string $type): self
	{
		$this->type = \strtoupper($type);
		return $this;
	}

	/**
	 * Configures a left join.
	 *
	 * @return self
	 */
	public function left(): self
	{
		return $this->addType('LEFT JOIN');
	}

	/**
	 * Configures a right join.
	 *
	 * @return self
	 */
	public function right(): self
	{
		return $this->addType('RIGHT JOIN');
	}

	/**
	 * Configures an outer join.
	 *
	 * @return self
	 */
	public function outer(): self
	{
		return $this->addType('OUTER JOIN');
	}

	/**
	 * Configures a cross join.
	 *
	 * @return self
	 */
	public function cross(): self
	{
		return $this->addType('CROSS JOIN');
	}

	/**
	 * Adds columns for the join.
	 *
	 * @param mixed ...$fields The fields to join.
	 * @return self
	 */
	public function fields(...$fields): self
	{
		if (count($fields) < 1)
		{
			return $this;
		}
		foreach ($fields as $field)
		{
			$this->addField($field, $this->alias);
		}
		return $this;
	}

	/**
	 * Sets the USING clause.
	 *
	 * @param string $field The field for the USING clause.
	 * @return self
	 */
	public function using(string $field): self
	{
		$this->using = 'USING(' . $field . ')';
		return $this;
	}

	/**
	 * Adds ON conditions.
	 *
	 * @param array|string ...$on The ON conditions.
	 * @return self
	 */
	public function on(...$on): self
	{
		if (count($on) < 1)
		{
			return $this;
		}
		foreach ($on as $conditionSpec)
		{
			$condition = $this->getCompareString($conditionSpec);
			$this->on[] = $condition;
		}
		return $this;
	}

	/**
	 * Gets the binding clause.
	 *
	 * @return string
	 */
	protected function getBind(): string
	{
		if (!empty($this->using))
		{
			return $this->using;
		}
		$onConditions = implode(' AND ', $this->on);
		return "ON {$onConditions}";
	}

	/**
	 * Renders the join clause.
	 *
	 * @return string The rendered SQL join clause.
	 */
	public function render(): string
	{
		$on = $this->getBind();
		$alias = !empty($this->alias) ? ' AS ' . $this->alias : '';
		return "{$this->type} {$this->tableName}{$alias} {$on}";
	}
}