<?php declare(strict_types=1);
namespace Proto\Database\QueryBuilder;

/**
 * Blueprint
 *
 * This will be the base class for all of the query
 * blueprints.
 *
 * @package Proto\Database\QueryBuilder
 * @abstract
 */
abstract class Blueprint extends Query
{
    /**
     * This will construct the blueprint.
     *
     * @param string $tableName
     * @param callable|null $callBack
     * @return void
     */
    public function __construct(string $tableName, ?callable $callBack = null)
    {
        parent::__construct($tableName);
        $this->callBack($callBack);
    }

    /**
     * This will call the call back.
     *
     * @param callable|null $callBack
     * @return void
     */
    protected function callBack(?callable $callBack = null): void
    {
        if (isset($callBack))
        {
            call_user_func($callBack, $this);
        }
    }
}