# Custom Data Types Guide

## Overview

The Proto framework now supports **custom data type handlers** for complex SQL types like `POINT`, `JSON`, `GEOMETRY`, etc. This system allows you to:

1. **Declare** data types in your model using the `$dataTypes` property
2. **Automatically handle** complex SQL placeholders and parameter binding
3. **Eliminate boilerplate** in custom Storage classes

## Quick Start

### 1. Define Data Types in Your Model

```php
<?php declare(strict_types=1);
namespace Modules\Auth\Models\Multifactor;

use Proto\Models\Model;
use Proto\Storage\DataTypes\PointType;

class UserAuthedLocation extends Model
{
    protected static ?string $tableName = 'user_authed_locations';

    protected static array $fields = [
        'id',
        'city',
        'position',
        [['X(`position`)'], 'latitude'],  // Extract X coordinate
        [['Y(`position`)'], 'longitude'], // Extract Y coordinate
    ];

    /**
     * Map field names to DataType handlers
     */
    protected static array $dataTypes = [
        'position' => PointType::class
    ];
}
```

### 2. Use the Model Normally

```php
// Insert with POINT
$location = new UserAuthedLocation();
$location->city = 'San Francisco';
$location->position = '37.7749 -122.4194'; // lat lon format
$location->add(); // Automatically converts to POINT(?, ?)

// Update with POINT
$location->position = '37.8044 -122.2712';
$location->update(); // Automatically handles SET position = POINT(?, ?)
```

### 3. No Custom Storage Needed!

The base `Storage` class now handles everything automatically. You only need a custom Storage class if you have other unique requirements.

## Built-in Data Types

### PointType

Handles MySQL `POINT(x, y)` spatial data.

**Supported input formats:**
- String: `"37.7749 -122.4194"` (space-separated lat/lon)
- Array: `[37.7749, -122.4194]`
- Object: `{lat: 37.7749, lon: -122.4194}` or `{x: 37.7749, y: 37.7749}`

**SQL output:**
- INSERT: `POINT(?, ?)`
- UPDATE: `position = POINT(?, ?)`

**Example:**
```php
protected static array $dataTypes = [
    'position' => PointType::class
];
```

### JsonType

Handles automatic JSON encoding.

**Supported input formats:**
- String: Already encoded JSON
- Array/Object: Will be encoded automatically

**SQL output:**
- INSERT: `?`
- UPDATE: `metadata = ?`

**Example:**
```php
protected static array $dataTypes = [
    'metadata' => JsonType::class,
    'settings' => JsonType::class
];

// Usage
$model->metadata = ['key' => 'value']; // Auto-encoded to JSON
```

## Creating Custom Data Types

### Step 1: Extend DataType Base Class

```php
<?php declare(strict_types=1);
namespace Proto\Storage\DataTypes;

class GeometryType extends DataType
{
    /**
     * Return the SQL placeholder for prepared statements
     */
    public function getPlaceholder(): string
    {
        return 'ST_GeomFromText(?)';
    }

    /**
     * Convert the model value to parameter array
     */
    public function toParams(mixed $value): array
    {
        // Return WKT (Well-Known Text) format
        return [$value]; // e.g., "POLYGON((0 0, 10 0, 10 10, 0 10, 0 0))"
    }

    /**
     * Optional: Customize UPDATE clause
     */
    public function getUpdateClause(string $column): string
    {
        return "`{$column}` = ST_GeomFromText(?)";
    }

    /**
     * Optional: Control when to use this handler
     */
    public function shouldHandle(mixed $value): bool
    {
        return $value !== null && is_string($value);
    }
}
```

### Step 2: Register in Model

```php
class Location extends Model
{
    protected static array $dataTypes = [
        'shape' => GeometryType::class
    ];
}
```

## Advanced Examples

### Multiple Custom Types

```php
class Event extends Model
{
    protected static array $fields = [
        'id',
        'name',
        'location',
        'metadata',
        'tags',
        'createdAt'
    ];

    protected static array $dataTypes = [
        'location' => PointType::class,
        'metadata' => JsonType::class,
        'tags' => JsonType::class
    ];
}
```

### Custom Instance Configuration

You can also pass configured instances instead of class names:

```php
protected static array $dataTypes = [
    'position' => new PointType(),
    'metadata' => new CustomJsonType(['pretty' => true])
];
```

## Migration Guide

### Before (Manual Handling)

```php
class UserAuthedLocationStorage extends Storage
{
    public function insert(object $data): bool
    {
        $params = $this->buildParams($data);
        return $this->table()
            ->insert()
            ->fields($params->cols)
            ->values($params->placeholders)
            ->execute($params->params);
    }

    private function buildParams(object $data, bool $forUpdate = false): object
    {
        $cols = [];
        $params = [];
        $placeholders = [];

        foreach ($data as $key => $val)
        {
            $cleanKey = '`' . Sanitize::cleanColumn($key) . '`';

            if ($key === 'position')
            {
                // Manual POINT handling
                $parts = explode(' ', $val);
                $params = array_merge($params, $parts);

                if ($forUpdate)
                {
                    $cols[] = "{$cleanKey} = POINT(?, ?)";
                }
                else
                {
                    $cols[] = $cleanKey;
                    $placeholders[] = 'POINT(?, ?)';
                }
            }
            else
            {
                $params[] = $val;
                // ... standard handling
            }
        }

        return (object)['cols' => $cols, 'params' => $params, 'placeholders' => $placeholders];
    }
}
```

### After (Declarative)

```php
class UserAuthedLocation extends Model
{
    protected static array $dataTypes = [
        'position' => PointType::class
    ];
}

// Storage class can now be empty or removed entirely!
class UserAuthedLocationStorage extends Storage
{
    // Only override exists() if you need custom logic
    protected function exists(object $data): bool
    {
        // Your custom existence check
    }
}
```

## How It Works

1. **Model Declaration**: You declare which fields use custom types in `$dataTypes`
2. **Storage Detection**: When `insert()` or `update()` is called, Storage checks if any custom types are defined
3. **ParamsBuilder**: If custom types exist, `ParamsBuilder` iterates through data:
   - For custom type fields: Calls `getPlaceholder()` and `toParams()`
   - For standard fields: Uses regular `?` placeholder
4. **Query Building**: Builds the full SQL with proper placeholders and executes with flattened params

## Performance Notes

- **Zero overhead** when no custom types are defined (falls back to standard insert/update)
- **Lazy instantiation** of DataType classes only when needed
- **Type instances cached** per model for reuse

## Testing Custom Data Types

```php
use PHPUnit\Framework\TestCase;
use Proto\Storage\DataTypes\PointType;

class PointTypeTest extends TestCase
{
    public function testStringFormat()
    {
        $type = new PointType();
        $params = $type->toParams('37.7749 -122.4194');

        $this->assertEquals(['37.7749', '-122.4194'], $params);
        $this->assertEquals('POINT(?, ?)', $type->getPlaceholder());
    }

    public function testArrayFormat()
    {
        $type = new PointType();
        $params = $type->toParams([37.7749, -122.4194]);

        $this->assertEquals([37.7749, -122.4194], $params);
    }
}
```

## API Reference

### DataType Abstract Class

#### Methods

**`abstract public function getPlaceholder(): string`**
- Returns the SQL placeholder for prepared statements
- Example: `"POINT(?, ?)"`, `"?"`, `"ST_GeomFromText(?)"`

**`abstract public function toParams(mixed $value): array`**
- Converts model value to array of parameters for binding
- Example: `"37.7 -122.4"` → `[37.7, -122.4]`

**`public function getUpdateClause(string $column): string`**
- Returns the SET clause fragment for UPDATE statements
- Default: `` `column` = {placeholder} ``
- Override for custom logic

**`public function shouldHandle(mixed $value): bool`**
- Determines if the handler should process this value
- Default: `$value !== null`
- Override to skip null values or add validation

### Model Methods

**`public function getDataType(string $field): ?DataType`**
- Retrieves the DataType handler for a specific field
- Returns null if no custom type is defined

**`public function getDataTypes(): array`**
- Returns all data type mappings for the model

## Troubleshooting

### Issue: Custom type not being used

**Check:**
1. Is the field name in `$dataTypes` exactly matching your data object property?
2. Is the DataType class properly namespaced and autoloaded?
3. Does `shouldHandle()` return true for your value?

### Issue: Wrong number of parameters

**Check:**
- `toParams()` must return an array with the same number of elements as `?` in `getPlaceholder()`
- Example: `POINT(?, ?)` requires `toParams()` to return exactly 2 elements

### Issue: NULL handling

If you want to allow NULL values for a custom type:

```php
public function shouldHandle(mixed $value): bool
{
    return true; // Handle even null values
}

public function toParams(mixed $value): array
{
    if ($value === null) {
        return [null, null]; // For POINT(?, ?)
    }
    // ... normal processing
}
```

## Best Practices

1. **Keep DataTypes focused**: One type per SQL function/pattern
2. **Support multiple input formats**: Make your types flexible (string, array, object)
3. **Validate input**: Use `shouldHandle()` to reject invalid data early
4. **Document format expectations**: Be clear about what input formats are supported
5. **Test thoroughly**: Unit test each DataType with various input formats

## Future Enhancements

Potential additions to consider:

- `LineStringType` for spatial line data
- `PolygonType` for spatial polygon data
- `EncryptedType` for automatic encryption/decryption
- `UuidType` for UUID generation and formatting
- `EnumType` for enum validation and conversion
