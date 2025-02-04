<?php declare(strict_types=1);
namespace Proto\Database\Migrations;

use Proto\Database\QueryBuilder\QueryHandler;
use Proto\Database\QueryBuilder\Drop;

/**
 * Migration
 *
 * This class will handle migrations.
 *
 * @package Proto\Database\Migrations
 * @abstract
 */
abstract class Migration
{
    /**
     * @var string $connection
     */
    protected string $connection = '';

    /**
     * @var string $fileName
     */
    protected string $fileName = '';

    /**
     * @var int|null $id
     */
    protected ?int $id;

    /**
     * @var array $queries
     */
    protected array $queries = [];

    /**
     * This will get the connection.
     *
     * @return string
     */
    public function getConnection(): string
    {
        return $this->connection;
    }

    /**
     * This will set the file name.
     *
     * @param string $fileName
     * @return void
     */
    public function setFileName(string $fileName): void
    {
        $this->fileName = $fileName;
    }

    /**
     * This will get the file name.
     *
     * @return string
     */
    public function getFileName(): string
    {
        return $this->fileName;
    }

    /**
     * This will set the id.
     *
     * @param string $id
     * @return void
     */
    public function setId(int $id): void
    {
        $this->id = $id;
    }

    /**
     * This will get the id.
     *
     * @return int|null
     */
    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * This will get the migration queries.
     *
     * @return array
     */
    public function getQueries(): array
    {
        return $this->queries;
    }

    /**
     * This will create a query handler.
     *
     * @param string $tableName
     * @return QueryHandler
     */
    protected function createQueryHandler(string $tableName): QueryHandler
    {
        return new QueryHandler($tableName);
    }

    /**
     * This will create a new query handler.
     *
     * @param string $tableName
     * @param callable $callBack
     * @return void
     */
    protected function create(string $tableName, $callBack): void
    {
        $table = $this->createQueryHandler($tableName);
        $query = $table->create($callBack);
        array_push($this->queries, $query);
    }

    /**
     * This will create a new query builder to create a view.
     *
     * @param string $viewName
     * @return object
     */
    protected function createView(string $viewName): object
    {
        $table = $this->createQueryHandler($viewName);
        $query = $table->createView();
        array_push($this->queries, $query);
        return $query;
    }

    /**
     * This will create a new alter table builder.
     *
     * @param string $tableName
     * @param callable $callBack
     * @return void
     */
    protected function alter(string $tableName, $callBack): void
    {
        $table = $this->createQueryHandler($tableName);
        $query = $table->alter($callBack);
        array_push($this->queries, $query);
    }

    /**
     * This will drop a view.
     *
     * @param string $viewName
     * @return void
     */
    protected function dropView(string $viewName): void
    {
        $this->drop($viewName, 'view');
    }

    /**
     * This will create a drop query builder.
     *
     * @param string $tableName
     * @return void
     */
    protected function drop(string $tableName, ?string $type = null): void
    {
        $query = new Drop($tableName);
        if(isset($type) === true)
        {
            $query->type($type);
        }
        array_push($this->queries, $query);
    }

    /**
     * Run the migration.
     *
     * @return void
     */
    public function up()
    {

    }

    /**
     * Revert the migration.
     *
     * @return void
     */
    public function down()
    {

    }
}