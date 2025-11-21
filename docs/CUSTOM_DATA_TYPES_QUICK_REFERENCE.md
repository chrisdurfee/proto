# Custom Data Types - Quick Reference

## Basic Usage

```php
// 1. Define in Model
class Location extends Model
{
    protected static array $dataTypes = [
        'position' => PointType::class,
        'metadata' => JsonType::class
    ];
}

// 2. Use normally
$location->position = '37.7749 -122.4194';
$location->metadata = ['key' => 'value'];
$location->add(); // Handles custom types automatically
```

## Built-in Types

| Type | Use Case | Input Format |
|------|----------|--------------|
| `PointType` | MySQL POINT(x,y) | `"lat lon"`, `[lat, lon]`, `{lat, lon}` |
| `JsonType` | JSON columns | Array, object, or JSON string |

## Create Custom Type

```php
class MyType extends DataType
{
    public function getPlaceholder(): string
    {
        return 'MY_FUNC(?)'; // SQL placeholder
    }

    public function toParams(mixed $value): array
    {
        return [$value]; // Array of params to bind
    }
}
```

## Key Methods

```php
// In Model
$model->getDataType('fieldName');  // Get handler for field
$model->getDataTypes();            // Get all mappings

// In DataType
->getPlaceholder()                 // e.g., "POINT(?, ?)"
->toParams($value)                 // e.g., [37.7, -122.4]
->getUpdateClause($col)            // e.g., "`col` = POINT(?, ?)"
->shouldHandle($value)             // Return true to use handler
```

## Common Patterns

### Multiple fields with same type
```php
protected static array $dataTypes = [
    'start_location' => PointType::class,
    'end_location' => PointType::class
];
```

### Mixed types
```php
protected static array $dataTypes = [
    'coordinates' => PointType::class,
    'properties' => JsonType::class,
    'tags' => JsonType::class
];
```

### With instances
```php
protected static array $dataTypes = [
    'field' => new MyType(['config' => 'value'])
];
```

## Migration Example

**Before:**
```php
// Custom Storage with manual buildParams() method
class MyStorage extends Storage
{
    public function insert(object $data): bool
    {
        // 50+ lines of manual parameter building
    }
}
```

**After:**
```php
// Model declares types
class MyModel extends Model
{
    protected static array $dataTypes = [
        'position' => PointType::class
    ];
}

// Storage class can be removed or simplified!
```

## Troubleshooting

| Issue | Solution |
|-------|----------|
| Type not used | Check field name matches exactly |
| Wrong param count | `toParams()` array length must match `?` count |
| NULL errors | Override `shouldHandle()` to return true for null |

## Pro Tips

- **Zero overhead**: No performance cost when `$dataTypes` is empty
- **Flexible input**: Support multiple formats in `toParams()` (string, array, object)
- **Test thoroughly**: Unit test each custom type with edge cases
- **Keep it simple**: One type = one SQL pattern
