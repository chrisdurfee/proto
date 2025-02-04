<?php declare(strict_types=1);
namespace Proto\Database\QueryBuilder;

/**
 * CreateView
 *
 * This will handle the create view query.
 *
 * @package Proto\Database\QueryBuilder
 */
class CreateView extends Blueprint
{
    /**
     * @var object|null $select
     */
    protected $select = null;

    /**
     * @var string $viewName
     */
    protected string $viewName;

    /**
     * This will construct the create view.
     *
     * @param string $viewName
     * @return void
     */
    public function __construct(string $viewName)
    {
        $this->viewName = $viewName;
    }

    /**
     * This will create a select query builder.
     *
     * @param array|string $tableName
     * @param string|null $alias
     * @return Select
     */
    public function table($tableName, ?string $alias = null): Select
    {
        $query = new Select($tableName, $alias);
        $this->select = $query;
        return $query;
    }

    /**
     * This will add a query string as the select.
     *
     * @param string $tableName
     * @param string|null $alias
     * @return void
     */
    public function query(string $query): void
    {
        $this->select = $query;
    }

    /**
     * This will render the query.
     *
     * @return string
     */
    public function render(): string
    {
        $select = (string)$this->select;

        return "CREATE OR REPLACE VIEW {$this->viewName} AS
                {$select}";
    }
}