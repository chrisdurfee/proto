<?php declare(strict_types=1);
namespace Proto\Database\QueryBuilder;

/**
 * CreateIndex
 *
 * This will handle the create index query.
 *
 * @package Proto\Database\QueryBuilder
 */
class CreateIndex extends Template
{
    /**
     * @var string $fields
     */
    protected string $fields = '';

    /**
     * @var string $name
     */
    protected string $name;

    /**
     * @var string $type
     */
    protected string $type = '';

    /**
     * This will construct the create index.
     *
     * @param string $name
     * @return void
     */
    public function __construct(string $name)
    {
        $this->name = $name;
    }

    /**
     * This will add fields.
     *
     * @param string ...$fields
     * @return string
     */
    public function fields(string ...$fields): self
    {
        $this->fields = '`' . implode('`,`', $fields) . '`';

        return $this;
    }

    /**
     * This will set the type.
     *
     * @param string $type
     * @return self
     */
    public function type(string $type): self
    {
        $this->type = \strtoupper($type);

        return $this;
    }

    /**
     * This will set the index as spatial.
     *
     * @return self
     */
    public function spatial(): self
    {
        return $this->type('SPATIAL');
    }

    /**
     * This will set the index as fulltext.
     *
     * @return self
     */
    public function fulltext(): self
    {
        return $this->type('FULLTEXT');
    }

    /**
     * This will get the index type.
     *
     * @return string
     */
    protected function getIndexType(): string
    {
        $type = $this->type;
        $nonBTreeTypes = ['SPATIAL', 'FULLTEXT'];
        if (\in_array($type, $nonBTreeTypes))
        {
            return '';
        }

        return ' USING BTREE';
    }

    /**
     * This will render the sql.
     *
     * @return string
     */
    public function render(): string
    {
        $indexType = $this->getIndexType();
        return "{$this->type} INDEX `{$this->name}` ({$this->fields}){$indexType}";
    }
}