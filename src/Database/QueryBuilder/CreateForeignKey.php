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
	protected string $field = '';

	/**
	 * The name of the foreign key constraint.
	 *
	 * @var string
	 */
	protected string $name;

	/**
	 * The referenced field in the foreign table.
	 *
	 * @var string
	 */
	protected string $references = '';

	/**
	 * The referenced table.
	 *
	 * @var string
	 */
	protected string $on = '';

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
		$this->field = $field;
		$this->name = 'fk_' . $field;
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
	 * @param string $references The field in the referenced table.
	 * @return self
	 */
	public function references(string $references): self
	{
		$this->references = $references;
		return $this;
	}

	/**
	 * Sets the referenced table and updates the constraint name.
	 *
	 * @param string $on The referenced table.
	 * @return self
	 */
	public function on(string $on): self
	{
		$this->on = $on;
		$this->name .= '_' . $on;
		return $this;
	}

	/**
	 * Renders the SQL statement for creating the foreign key constraint.
	 *
	 * @return string
	 */
	public function render(): string
	{
		return "CONSTRAINT `{$this->name}` FOREIGN KEY (`{$this->field}`) REFERENCES `{$this->on}` (`{$this->references}`) ON UPDATE {$this->onUpdate} ON DELETE {$this->onDelete}";
	}
}