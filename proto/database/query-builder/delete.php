<?php declare(strict_types=1);
namespace Proto\Database\QueryBuilder;

/**
 * Delete
 *
 * This will handle the delete query.
 *
 * @package Proto\Database\QueryBuilder
 */
class Delete extends Query
{
    /**
     * This will render the query.
     *
     * @return string
     */
    public function render(): string
    {
        $where = $this->getPropertyString($this->conditions, ' WHERE ', ' AND ');
        $orderBy = $this->getPropertyString($this->orderBy, ' ORDER BY ', ', ');

        return "DELETE FROM {$this->tableName}
                    {$where}{$orderBy}{$this->limit};";
    }
}