<?php declare(strict_types=1);
namespace Proto\Database\QueryBuilder;

/**
 * CreateForeignKey
 *
 * Builds an SQL foreign key constraint.
 *
 * @package Proto\Database\QueryBuilder
 */
class CreateForeignKey extends Template
{
	/**
	 * The local field that holds the foreign key.
	 *
	 * @var string
	 */
	protected string $localField = '';

	/**
	 * The name of the foreign key constraint.
	 *
	 * @var string
	 */
	protected string $constraintName;

	/**
	 * The referenced field in the foreign table.
	 *
	 * @var string
	 */
	protected string $referenceField = '';

	/**
	 * The referenced table.
	 *
	 * @var string
	 */
	protected string $referenceTable = '';

	/**
	 * The action on update.
	 *
	 * @var string
	 */
	protected string $onUpdate = 'NO ACTION';

	/**
	 * The action on delete.
	 *
	 * @var string
	 */
	protected string $onDelete = 'NO ACTION';

	/**
	 * Constructor.
	 *
	 * @param string $field The local field name.
	 * @return void
	 */
	public function __construct(string $field)
	{
		$this->localField = $field;
		$this->constraintName = 'fk_' . $field;
	}

	/**
	 * Sets the ON UPDATE action.
	 *
	 * @param string $update The update action.
	 * @return self
	 */
	public function onUpdate(string $update): self
	{
		$this->onUpdate = strtoupper($update);
		return $this;
	}

	/**
	 * Sets the ON DELETE action.
	 *
	 * @param string $delete The delete action.
	 * @return self
	 */
	public function onDelete(string $delete): self
	{
		$this->onDelete = strtoupper($delete);
		return $this;
	}

	/**
	 * Sets the referenced field.
	 *
	 * @param string $referenceField The field in the referenced table.
	 * @return self
	 */
	public function setReferenceField(string $referenceField): self
	{
		$this->referenceField = $referenceField;
		return $this;
	}

	/**
	 * Sets the referenced table and updates the constraint name.
	 *
	 * @param string $referenceTable The referenced table.
	 * @return self
	 */
	public function setReferenceTable(string $referenceTable): self
	{
		$randomSuffix = $this->referenceField . random_int(0, 1000);
		$this->referenceTable = $referenceTable;
		$this->constraintName .= '_' . $referenceTable . '_' . $randomSuffix;
		return $this;
	}

	/**
	 * Renders the SQL statement for creating the foreign key constraint.
	 *
	 * @return string
	 */
	public function render(): string
	{
		return "CONSTRAINT `{$this->constraintName}` FOREIGN KEY (`{$this->localField}`) REFERENCES `{$this->referenceTable}` (`{$this->referenceField}`) ON UPDATE {$this->onUpdate} ON DELETE {$this->onDelete}";
	}
}