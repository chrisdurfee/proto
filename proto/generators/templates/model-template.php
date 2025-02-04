<?php declare(strict_types=1);
namespace Proto\Generators\Templates;

/**
 * ModelTemplate
 *
 * This will create a model template generator.
 *
 * @package Proto\Generators\Templates
 */
class ModelTemplate extends ClassTemplate
{
    /**
     * This will get the extends string.
     *
     * @return string
     */
    protected function getExtends(): string
    {
        $extends = $this->get('extends');
        return 'extends ' . (($extends)? $extends : 'Model');
    }

    /**
     * This will get the storage string.
     *
     * @return string
     */
    protected function getStorage(): string
    {
        $storage = $this->get('storage');
        if (!$storage)
        {
            return '';
        }

        $storageName = $this->getStorageClassName();
        $property = $this->getProtectedProperty('$storageType', $storageName, true);

        return <<<EOT

    /**
     * @var string \$storageType
     */
    $property
EOT;
    }

    /**
     * This will get the storage class name.
     *
     * @return string
     */
    protected function getStorageName(): string
    {
        return $this->get('className') . 'Storage';
    }

    /**
     * This will get the storage class.
     *
     * @return string
     */
    protected function getStorageClassName(): string
    {
        return $this->getStorageName() . '::class';
    }

    /**
     * This will get the table name.
     *
     * @return string
     */
    protected function getTable(): string
    {
        $property = $this->getProtectedProperty('$tableName', $this->quote($this->get('tableName')), true);

        return <<<EOT
/**
     * @var string \$tableName
     */
    $property
EOT;
    }

    /**
     * This will get the alias.
     *
     * @return string
     */
    protected function getAlias(): string
    {
        $property = $this->getProtectedProperty('$alias', $this->quote($this->get('alias')), true);

        return <<<EOT
/**
     * @var string \$alias
     */
    $property
EOT;
    }

    /**
     * This will get the table fields.
     *
     * @return string
     */
    protected function getFields(): string
    {
        $fields = $this->get('fields');
        if (!$fields || count($fields) < 1)
        {
            return '';
        }

        $values = '\'' . join('\',' . PHP_EOL . "\t\t'", $fields) . '\'';
        $columns = <<<EOT
[
        {$values}
    ]
EOT;
        $property = $this->getProtectedProperty('$fields', $columns, true);

        return <<<EOT
/**
     * @var array \$fields
     */
    $property
EOT;
    }

    /**
     * This will get the model joins.
     *
     * @return string
     */
    protected function getJoins(): string
    {
        $joins = $this->get('joins');
        if (!$joins)
        {
            return '';
        }

        return '
    /**
     * This will set up the model joins.
     *
     * @param object $builder
     * @return void
     */
    protected static function joins($builder)
    {
' . $joins . '
    }
';
    }

    /**
     * This will get the use string.
     *
     * @return string
     */
    protected function getUse(): string
    {
        $storage = $this->get('storage');
        if (!$storage)
        {
            return '';
        }

        $storageName = $this->getNamespace() . $this->getStorageName();

        return <<<EOT
use App\Storage\\{$storageName};
EOT;
    }

    /**
     * This will get the class content.
     *
     * @return string
     */
    protected function getClassContent(): string
    {
        $table = $this->getTable();
        $alias = $this->getAlias();
        $fields = $this->getFields();
        $joins = $this->getJoins();
        $storage = $this->getStorage();

        return <<<EOT
    {$table}
    {$alias}
    {$fields}{$joins}
    /**
     * @var bool \$passModel
     */
    protected \$passModel = true;
    {$storage}
EOT;
    }
}
