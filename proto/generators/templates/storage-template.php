<?php declare(strict_types=1);
namespace Proto\Generators\Templates;

/**
 * StorageTemplate
 *
 * This will create a storage template generator.
 *
 * @package Proto\Generators\Templates
 */
class StorageTemplate extends ClassTemplate
{
    /**
     * This will get the extends string.
     *
     * @return string
     */
    protected function getExtends(): string
    {
        $extends = $this->get('extends');
        return 'extends ' . (($extends)? $extends : 'Storage');
    }

    /**
     * This will get the connection string.
     *
     * @return string
     */
    protected function getConnection(): string
    {
        $connection = $this->get('connection');
        if (!$connection)
        {
            return '';
        }

        $property = $this->getProtectedProperty('$connection', $this->quote($connection));

        return <<<EOT
/**
     * @var string \$connection
     */
    $property
EOT;
    }

    /**
     * This will get the className.
     *
     * @return string
     */
    protected function getClassName(): string
    {
        return $this->get('className') . 'Storage';
    }

    /**
     * This will get the class content.
     *
     * @return string
     */
    protected function getClassContent(): string
    {
        $connection = $this->getConnection();

        return <<<EOT
    {$connection}
EOT;
    }
}
