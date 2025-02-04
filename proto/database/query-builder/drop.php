<?php declare(strict_types=1);
namespace Proto\Database\QueryBuilder;

/**
 * Drop
 *
 * This will handle the drop query.
 *
 * @package Proto\Database\QueryBuilder
 */
class Drop extends Query
{
    /**
     * @var string $type
     */
    protected string $type = 'TABLE';

    /**
     * @var string $tableName
     */
    protected string $tableName;

    /**
     * This will set the drop type.
     *
     * @param string $type
     * @return void
     */
    public function type(string $type): void
    {
        if (empty($type))
        {
            return;
        }

        $this->type = strtoupper($type);
    }

    /**
     * This will render the query.
     *
     * @return string
     */
    public function render(): string
    {
        return "DROP {$this->type} {$this->tableName};";
    }
}