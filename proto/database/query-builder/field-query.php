<?php declare(strict_types=1);
namespace Proto\Database\QueryBuilder;

/**
 * FieldQuery
 *
 * This will handle the field query.
 *
 * @package Proto\Database\QueryBuilder
 */
abstract class FieldQuery extends Query
{
    /**
     * @var array $fields
     */
    protected array $fields = [];

    /**
     * This will get the fields.
     *
     * @return array
     */
    public function getFields(): array
    {
        return $this->fields;
    }

    /**
     * This will add a field.
     *
     * @param mixed $row
     * @param string $alias
     * @return void
     */
    protected function addField(mixed $row, string $alias): void
    {
        if (is_array($row))
        {
            // this will allow raw sql to be set as a field
            if (count($row) < 2)
            {
                $column = $row[0];
            }
            else if (\is_array($row[0]))
            {
                $column = $row[0][0] . ' AS ' . $row[1];
            }
            else
            {
                $row = $row[0] . ' AS ' . $row[1];
                $column = $alias . '.' . $row;
            }
        }
        else
        {
            $column = $alias . '.' . $row;
        }

        array_push($this->fields, $column);
    }
}