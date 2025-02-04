<?php declare(strict_types=1);
namespace Proto\Database\QueryBuilder;

/**
 * CreateForeignKey
 *
 * This will handle the create foreign key query.
 *
 * @package Proto\Database\QueryBuilder
 */
class CreateForeignKey extends Template
{
    /**
     * @var string $field
     */
    protected string $field = '';

    /**
     * @var string $name
     */
    protected string $name;

    /**
     * @var string $references
     */
    protected string $references = '';

    /**
     * @var string $on
     */
    protected string $on = '';

    /**
     * @var string $onUpdate
     */
    protected string $onUpdate = 'NO ACTION';

    /**
     * @var string $onDelete
     */
    protected string $onDelete = 'NO ACTION';

    /**
     * This will construct the create foreign key.
     *
     * @param string $field
     * @return void
     */
    public function __construct(string $field)
    {
        $this->field = $field;
        $this->name = 'fk_' . $field;
    }

    /**
     * This will add on update.
     *
     * @param string $update
     * @return string
     */
    public function onUpdate(string $update): self
    {
        $this->onUpdate = strtoupper($update);

        return $this;
    }

    /**
     * This will add on delete.
     *
     * @param string $delete
     * @return string
     */
    public function onDelete(string $delete): self
    {
        $this->onDelete = strtoupper($delete);

        return $this;
    }

    /**
     * This will add the reference field.
     *
     * @param string $references
     * @return string
     */
    public function references(string $references): self
    {
        $this->references = $references;

        return $this;
    }

    /**
     * This will add the reference table.
     *
     * @param string $on
     * @return string
     */
    public function on(string $on): self
    {
        $ref = $this->references . rand(0, 1000);

        $this->on = $on;
        $this->name .= '_' . $on . '_' . $ref;

        return $this;
    }

    /**
     * This will render the sql.
     *
     * @return string
     */
    public function render(): string
    {
        return "CONSTRAINT `{$this->name}` FOREIGN KEY (`{$this->field}`) REFERENCES `{$this->on}` (`{$this->references}`) ON UPDATE {$this->onUpdate} ON DELETE {$this->onDelete}";
    }
}