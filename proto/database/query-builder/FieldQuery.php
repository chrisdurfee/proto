<?php declare(strict_types=1);
namespace Proto\Database\QueryBuilder;

/**
 * FieldQuery
 *
 * Abstract class that handles field-specific aspects of query construction.
 *
 * @package Proto\Database\QueryBuilder
 * @abstract
 */
abstract class FieldQuery extends Query
{
    /**
     * The list of fields for the query.
     *
     * @var string[]
     */
    protected array $fields = [];

    /**
     * Retrieves the fields for the query.
     *
     * @return string[] The array of field strings.
     */
    public function getFields() : array
    {
        return $this->fields;
    }

    /**
     * Adds a field to the query.
     *
     * The method supports either a raw SQL string or an array defining the field and its alias.
     *
     * @param mixed $fieldDefinition The field definition as a string or an array.
     * @param string $alias The alias to use for the field.
     *
     * @return void
     */
    protected function addField(mixed $fieldDefinition, string $alias) : void
    {
        if (is_array($fieldDefinition))
        {
            // Allow raw SQL to be set as a field.
            if (count($fieldDefinition) < 2)
            {
                $column = $fieldDefinition[0];
            }
            elseif (\is_array($fieldDefinition[0]))
            {
                $column = $fieldDefinition[0][0] . ' AS ' . $fieldDefinition[1];
            }
            else
            {
                $fieldSql = $fieldDefinition[0] . ' AS ' . $fieldDefinition[1];
                $column   = $alias . '.' . $fieldSql;
            }
        }
        else
        {
            $column = $alias . '.' . $fieldDefinition;
        }

        $this->fields[] = $column;
    }
}