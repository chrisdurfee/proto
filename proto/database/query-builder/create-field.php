<?php declare(strict_types=1);
namespace Proto\Database\QueryBuilder;

/**
 * CreateField
 *
 * This will handle the create field query.
 *
 * @package Proto\Database\QueryBuilder
 */
class CreateField extends Template
{
    /**
     * @var string $type
     */
    protected string $type = '';

    /**
     * @var string $name
     */
    protected string $name;

    /**
     * @var int $length
     */
    protected int $length;

    /**
     * @var string $null
     */
    protected string $null = 'NOT NULL';

    /**
     * @var string $default
     */
    protected string $default = '';

    /**
     * @var string $primary
     */
    protected string $primary = '';

    /**
     * @var string $after
     */
    protected string $after = '';

    /**
     * @var string $rename
     */
    protected string $rename = '';

    /**
     * @var string $autoIncrement
     */
    protected string $autoIncrement = '';

    /**
     * This will construct the create field.
     *
     * @param string $name
     * @return void
     */
    public function __construct(string $name)
    {
        $this->name = $name;
    }

    /**
     * This will add the type.
     *
     * @param string $type
     * @param mixed $value
     * @return self
     */
    public function addType(string $type, $value = null): self
    {
        $type = strtoupper($type);
        $this->type = (isset($value))? "{$type}({$value})" : $type;

        return $this;
    }

    /**
     * This will add the type as int.
     *
     * @param int $length
     * @return self
     */
    public function int(int $length): self
    {
        $this->addType('int', $length);

        return $this;
    }

    /**
     * This will add the type as bit.
     *
     * @return self
     */
    public function bit(): self
    {
        $this->addType('bit', 1);

        return $this;
    }

    /**
     * This will add the type as smallint.
     *
     * @param int $length
     * @return self
     */
    public function smallInt(int $length): self
    {
        $this->addType('smallint', $length);

        return $this;
    }

    /**
     * This will add the type as mediumint.
     *
     * @param int $length
     * @return self
     */
    public function mediumInt(int $length): self
    {
        $this->addType('mediumint', $length);

        return $this;
    }

    /**
     * This will add the type as bigint.
     *
     * @param int $length
     * @return self
     */
    public function bigInt(int $length): self
    {
        $this->addType('bigint', $length);

        return $this;
    }

    /**
     * This will add the type as decimal.
     *
     * @param int $length
     * @return self
     */
    public function decimal(int $length): self
    {
        $this->addType('decimal', $length);

        return $this;
    }

    /**
     * This will add the type as float.
     *
     * @param int $length
     * @return self
     */
    public function float(int $length): self
    {
        $this->addType('float', $length);

        return $this;
    }

    /**
     * This will add the type as double.
     *
     * @param int $length
     * @return self
     */
    public function double(int $length): self
    {
        $this->addType('double', $length);

        return $this;
    }

    /**
     * This will add the type as char.
     *
     * @param int $length
     * @return self
     */
    public function char(int $length): self
    {
        $this->addType('char', $length);

        return $this;
    }

    /**
     * This will add the type as varchar.
     *
     * @param int $length
     * @return self
     */
    public function varchar(int $length): self
    {
        $this->addType('varchar', $length);

        return $this;
    }

    /**
     * This will add the type as binary.
     *
     * @param int $length
     * @return self
     */
    public function binary(int $length): self
    {
        $this->addType('binary', $length);

        return $this;
    }

    /**
     * This will add the type as tinyblob.
     *
     * @return self
     */
    public function tinyBlob(): self
    {
        $this->addType('tinyblob');

        return $this;
    }

    /**
     * This will add the type as blob.
     *
     * @param int $length
     * @return self
     */
    public function blob(int $length): self
    {
        $this->addType('blob', $length);

        return $this;
    }

    /**
     * This will add the type as mediumblob.
     *
     * @param int $length
     * @return self
     */
    public function mediumBlob(int $length): self
    {
        $this->addType('mediumblob', $length);

        return $this;
    }

    /**
     * This will add the type as longblob.
     *
     * @param int $length
     * @return self
     */
    public function longBlob(int $length): self
    {
        $this->addType('longblob', $length);

        return $this;
    }

    /**
     * This will add the type as tinytext.
     *
     * @return self
     */
    public function tinyText(): self
    {
        $this->addType('tinytext');

        return $this;
    }

    /**
     * This will add the type as text.
     *
     * @return self
     */
    public function text(): self
    {
        $this->addType('text');

        return $this;
    }

    /**
     * This will add the type as mediumtext.
     *
     * @return self
     */
    public function mediumText(): self
    {
        $this->addType('mediumtext');

        return $this;
    }

    /**
     * This will add the type as longtext.
     *
     * @return self
     */
    public function longText(): self
    {
        $this->addType('longtext');

        return $this;
    }

    /**
     * This will add the type as json.
     *
     * @return self
     */
    public function json(): self
    {
        $this->addType('json');

        return $this;
    }

    /**
     * This will add the type as a point.
     *
     * @return self
     */
    public function point(): self
    {
        $this->addType('point');

        return $this;
    }

    /**
     * This will add the type as enum.
     *
     * @param mixed ...$fields
     * @return self
     */
    public function enum(...$fields): self
    {
        $sql = "'" . implode("','", $fields) . "'";
        $this->addType('enum', $sql);

        return $this;
    }

    /**
     * This will add the type as date.
     *
     * @return self
     */
    public function date(): self
    {
        $this->addType('date');

        return $this;
    }

    /**
     * This will add the type as datetime.
     *
     * @return self
     */
    public function datetime(): self
    {
        $this->addType('datetime');

        return $this;
    }

    /**
     * This will add the type as timestamp.
     *
     * @return self
     */
    public function timestamp(): self
    {
        $this->addType('timestamp');

        return $this;
    }

    /**
     * This will add the default value.
     *
     * @param mixed $value
     * @return self
     */
    public function default($value): self
    {
        $this->default = "DEFAULT {$value}";

        return $this;
    }

    /**
     * This will add the default value as current timestamp.
     *
     * @return self
     */
    public function utcTimestamp(): self
    {
        $this->default("UTC_TIMESTAMP");

        return $this;
    }

    /**
     * This will add the default value as current timestamp.
     *
     * @return self
     */
    public function currentTimestamp(): self
    {
        $this->default("CURRENT_TIMESTAMP");

        return $this;
    }

    /**
     * This will rename a column.
     *
     * @param string $newName
     * @return self
     */
    public function rename(string $newName): self
    {
        $this->rename = "`{$this->name}` `{$newName}`";

        return $this;
    }

    /**
     * This will add the after field.
     *
     * @param string $field
     * @return self
     */
    public function after(string $field): self
    {
        $this->after = "AFTER {$field}";

        return $this;
    }

    /**
     * This will allow null values.
     *
     * @return self
     */
    public function null(): self
    {
        $this->null = "NULL";

        return $this;
    }

    /**
     * This will add auto increment.
     *
     * @return self
     */
    public function autoIncrement(): self
    {
        $this->autoIncrement = "AUTO_INCREMENT";

        return $this;
    }

    /**
     * This will add primary.
     *
     * @return self
     */
    public function primary(): self
    {
        $this->primary = "PRIMARY KEY";

        return $this;
    }

    /**
     * This will render the sql.
     *
     * @return string
     */
    public function render(): string
    {
        $name = (!empty($this->rename))? $this->rename : "`{$this->name}`";
        return "{$name} {$this->type} {$this->null} {$this->default} {$this->primary} {$this->autoIncrement} {$this->after}";
    }
}