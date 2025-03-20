<?php declare(strict_types=1);
namespace Proto\Database\QueryBuilder;

/**
 * CreateField
 *
 * Builds an SQL field definition for table creation queries.
 *
 * @package Proto\Database\QueryBuilder
 */
class CreateField extends Template
{
	/**
	 * SQL field type definition.
	 *
	 * @var string
	 */
	protected string $fieldType = '';

	/**
	 * Field name.
	 *
	 * @var string
	 */
	protected string $fieldName;

	/**
	 * Null constraint clause.
	 *
	 * @var string
	 */
	protected string $nullConstraint = 'NOT NULL';

	/**
	 * Default value clause.
	 *
	 * @var string
	 */
	protected string $defaultValue = '';

	/**
	 * Primary key clause.
	 *
	 * @var string
	 */
	protected string $primaryKey = '';

	/**
	 * AFTER column clause.
	 *
	 * @var string
	 */
	protected string $afterClause = '';

	/**
	 * Rename clause.
	 *
	 * @var string
	 */
	protected string $renameClause = '';

	/**
	 * Auto increment clause.
	 *
	 * @var string
	 */
	protected string $autoIncrementClause = '';

	/**
	 * Constructor.
	 *
	 * @param string $name Field name.
	 */
	public function __construct(string $name)
	{
		$this->fieldName = $name;
	}

	/**
	 * Sets the field type with an optional length or specification.
	 *
	 * @param string $type SQL field type.
	 * @param mixed $value Optional length or specification.
	 * @return self
	 */
	public function setFieldType(string $type, $value = null): self
	{
		$type = strtoupper($type);
		$this->fieldType = isset($value) ? "{$type}({$value})" : $type;
		return $this;
	}

	/**
	 * Sets the field type to INT with specified length.
	 *
	 * @param int $length
	 * @return self
	 */
	public function int(int $length): self
	{
		$this->setFieldType('INT', $length);
		return $this;
	}

	/**
	 * Sets the field type to INT with specified length.
	 *
	 * @param int $length
	 * @return self
	 */
	public function integer(int $length): self
	{
		return $this->int($length);
	}

	/**
	 * Sets the field type to BIT with a default length of 1.
	 *
	 * @return self
	 */
	public function bit(): self
	{
		$this->setFieldType('BIT', 1);
		return $this;
	}

	/**
	 * Sets the field type to TINYINT with specified length.
	 *
	 * @return self
	 */
	public function boolean(): self
	{
		return $this->tinyInteger();
	}

	/**
	 * Sets the field type to TINYINT with specified length.
	 *
	 * @param int [$length = 1]
	 * @return self
	 */
	public function tinyInteger(int $length = 1): self
	{
		$this->setFieldType('TINYINT', $length);
		return $this;
	}

	/**
	 * Sets the field type to SMALLINT with specified length.
	 *
	 * @param int $length
	 * @return self
	 */
	public function smallInteger(int $length): self
	{
		$this->setFieldType('SMALLINT', $length);
		return $this;
	}

	/**
	 * Sets the field type to MEDIUMINT with specified length.
	 *
	 * @param int $length
	 * @return self
	 */
	public function mediumInteger(int $length): self
	{
		$this->setFieldType('MEDIUMINT', $length);
		return $this;
	}

	/**
	 * Sets the field type to BIGINT with specified length.
	 *
	 * @param int $length
	 * @return self
	 */
	public function bigInteger(int $length): self
	{
		$this->setFieldType('BIGINT', $length);
		return $this;
	}

	/**
	 * Sets the field type to DECIMAL with specified length.
	 *
	 * @param int $length
	 * @return self
	 */
	public function decimal(int $length): self
	{
		$this->setFieldType('DECIMAL', $length);
		return $this;
	}

	/**
	 * Sets the field type to FLOAT with specified length.
	 *
	 * @param int $length
	 * @return self
	 */
	public function floatType(int $length): self
	{
		$this->setFieldType('FLOAT', $length);
		return $this;
	}

	/**
	 * Sets the field type to DOUBLE with specified length.
	 *
	 * @param int $length
	 * @return self
	 */
	public function doubleType(int $length): self
	{
		$this->setFieldType('DOUBLE', $length);
		return $this;
	}

	/**
	 * Sets the field type to CHAR with specified length.
	 *
	 * @param int $length
	 * @return self
	 */
	public function char(int $length): self
	{
		$this->setFieldType('CHAR', $length);
		return $this;
	}

	/**
	 * Sets the field type to VARCHAR with specified length.
	 *
	 * @param int $length
	 * @return self
	 */
	public function varchar(int $length): self
	{
		$this->setFieldType('VARCHAR', $length);
		return $this;
	}

	/**
	 * Sets the field type to BINARY with specified length.
	 *
	 * @param int $length
	 * @return self
	 */
	public function binary(int $length): self
	{
		$this->setFieldType('BINARY', $length);
		return $this;
	}

	/**
	 * Sets the field type to TINYBLOB.
	 *
	 * @return self
	 */
	public function tinyBlob(): self
	{
		$this->setFieldType('TINYBLOB');
		return $this;
	}

	/**
	 * Sets the field type to BLOB with specified length.
	 *
	 * @param int $length
	 * @return self
	 */
	public function blob(int $length): self
	{
		$this->setFieldType('BLOB', $length);
		return $this;
	}

	/**
	 * Sets the field type to MEDIUMBLOB with specified length.
	 *
	 * @param int $length
	 * @return self
	 */
	public function mediumBlob(int $length): self
	{
		$this->setFieldType('MEDIUMBLOB', $length);
		return $this;
	}

	/**
	 * Sets the field type to LONGBLOB with specified length.
	 *
	 * @param int $length
	 * @return self
	 */
	public function longBlob(int $length): self
	{
		$this->setFieldType('LONGBLOB', $length);
		return $this;
	}

	/**
	 * Sets the field type to TINYTEXT.
	 *
	 * @return self
	 */
	public function tinyText(): self
	{
		$this->setFieldType('TINYTEXT');
		return $this;
	}

	/**
	 * Sets the field type to TEXT.
	 *
	 * @return self
	 */
	public function text(): self
	{
		$this->setFieldType('TEXT');
		return $this;
	}

	/**
	 * Sets the field type to MEDIUMTEXT.
	 *
	 * @return self
	 */
	public function mediumText(): self
	{
		$this->setFieldType('MEDIUMTEXT');
		return $this;
	}

	/**
	 * Sets the field type to LONGTEXT.
	 *
	 * @return self
	 */
	public function longText(): self
	{
		$this->setFieldType('LONGTEXT');
		return $this;
	}

	/**
	 * Sets the field type to JSON.
	 *
	 * @return self
	 */
	public function json(): self
	{
		$this->setFieldType('JSON');
		return $this;
	}

	/**
	 * Sets the field type to POINT.
	 *
	 * @return self
	 */
	public function point(): self
	{
		$this->setFieldType('POINT');
		return $this;
	}

	/**
	 * Sets the field type to ENUM with provided values.
	 *
	 * @param string ...$values
	 * @return self
	 */
	public function enum(string ...$values): self
	{
		$sql = "'" . implode("','", $values) . "'";
		$this->setFieldType('ENUM', $sql);
		return $this;
	}

	/**
	 * Sets the field type to DATE.
	 *
	 * @return self
	 */
	public function date(): self
	{
		$this->setFieldType('DATE');
		return $this;
	}

	/**
	 * Sets the field type to DATETIME.
	 *
	 * @return self
	 */
	public function datetime(): self
	{
		$this->setFieldType('DATETIME');
		return $this;
	}

	/**
	 * Sets the field type to TIMESTAMP.
	 *
	 * @return self
	 */
	public function timestamp(): self
	{
		$this->setFieldType('TIMESTAMP');
		return $this;
	}

	/**
	 * Sets the default value clause.
	 *
	 * @param mixed $value
	 * @return self
	 */
	public function setDefault($value): self
	{
		$this->defaultValue = "DEFAULT {$value}";
		return $this;
	}

	/**
	 * Sets the default value clause to UTC_TIMESTAMP.
	 *
	 * @return self
	 */
	public function utcTimestamp(): self
	{
		$this->setDefault("UTC_TIMESTAMP");
		return $this;
	}

	/**
	 * Sets the default value clause to CURRENT_TIMESTAMP.
	 *
	 * @return self
	 */
	public function currentTimestamp(): self
	{
		$this->setDefault("CURRENT_TIMESTAMP");
		return $this;
	}

	/**
	 * Sets the rename clause.
	 *
	 * @param string $newName New field name.
	 * @return self
	 */
	public function rename(string $newName): self
	{
		$this->renameClause = "`{$this->fieldName}` `{$newName}`";
		return $this;
	}

	/**
	 * Sets the AFTER clause.
	 *
	 * @param string $afterField Column name after which this field should appear.
	 * @return self
	 */
	public function after(string $afterField): self
	{
		$this->afterClause = "AFTER `{$afterField}`";
		return $this;
	}

	/**
	 * Allows the field to accept NULL values.
	 *
	 * @return self
	 */
	public function nullable(): self
	{
		$this->nullConstraint = "NULL";
		return $this;
	}

	/**
	 * Enables auto increment on the field.
	 *
	 * @return self
	 */
	public function autoIncrement(): self
	{
		$this->autoIncrementClause = "AUTO_INCREMENT";
		return $this;
	}

	/**
	 * Sets the field as a primary key.
	 *
	 * @return self
	 */
	public function primary(): self
	{
		$this->primaryKey = "PRIMARY KEY";
		return $this;
	}

	/**
	 * Renders the SQL field definition.
	 *
	 * @return string
	 */
	public function render():string
	{
		$fieldIdentifier = !empty($this->renameClause)
			? $this->renameClause
			: "`{$this->fieldName}`";

		$parts = [
			$fieldIdentifier,
			$this->fieldType,
			$this->nullConstraint,
			$this->defaultValue,
			$this->primaryKey,
			$this->autoIncrementClause,
			$this->afterClause,
		];

		return implode(' ', array_filter($parts, function($part): bool
		{
			return $part !== '';
		}));
	}
}