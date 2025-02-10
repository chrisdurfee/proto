<?php declare(strict_types=1);
namespace Proto\Database\QueryBuilder;

/**
 * CreateIndex
 *
 * Builds an SQL index definition for table queries.
 *
 * @package Proto\Database\QueryBuilder
 */
class CreateIndex extends Template
{
	/**
	 * The fields clause for the index.
	 *
	 * @var string
	 */
	protected string $fieldsClause = '';

	/**
	 * The name of the index.
	 *
	 * @var string
	 */
	protected string $indexName;

	/**
	 * The type of the index.
	 *
	 * @var string
	 */
	protected string $indexType = '';

	/**
	 * Constructor.
	 *
	 * @param string $name The name of the index.
	 * @return void
	 */
	public function __construct(string $name)
	{
		$this->indexName = $name;
	}

	/**
	 * Sets the fields for the index.
	 *
	 * @param string ...$fields The field names.
	 * @return self
	 */
	public function fields(string ...$fields): self
	{
		$this->fieldsClause = '`' . implode('`,`', $fields) . '`';
		return $this;
	}

	/**
	 * Sets the index type.
	 *
	 * @param string $type The index type.
	 * @return self
	 */
	public function setIndexType(string $type): self
	{
		$this->indexType = \strtoupper($type);
		return $this;
	}

	/**
	 * Sets the index as SPATIAL.
	 *
	 * @return self
	 */
	public function spatial(): self
	{
		return $this->setIndexType('SPATIAL');
	}

	/**
	 * Sets the index as FULLTEXT.
	 *
	 * @return self
	 */
	public function fulltext(): self
	{
		return $this->setIndexType('FULLTEXT');
	}

	/**
	 * Determines the appropriate index type clause.
	 *
	 * @return string
	 */
	protected function getIndexType(): string
	{
		$type = $this->indexType;
		$nonBTreeTypes = ['SPATIAL', 'FULLTEXT'];
		if (\in_array($type, $nonBTreeTypes))
		{
			return '';
		}
		return ' USING BTREE';
	}

	/**
	 * Renders the SQL for creating the index.
	 *
	 * @return string
	 */
	public function render(): string
	{
		$indexTypeClause = $this->getIndexType();
		return "{$this->indexType} INDEX `{$this->indexName}` ({$this->fieldsClause}){$indexTypeClause}";
	}
}