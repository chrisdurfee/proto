# SimpleFaker - New Methods Quick Reference

## Overview
SimpleFaker has been enhanced with 6 new methods to improve compatibility with FakerPHP patterns commonly used in factories.

## New Methods

### 1. Geographic Coordinates

#### `latitude(float $min = -90.0, float $max = 90.0, int $decimals = 6): float`
Generates a random latitude coordinate between -90.0 and 90.0.

```php
// Default: full range
$lat = $this->faker->latitude();
// Output: 42.123456

// US-specific range
$lat = $this->faker->latitude(25.0, 50.0);
// Output: 37.456789

// Custom precision
$lat = $this->faker->latitude(decimals: 4);
// Output: -23.4567
```

#### `longitude(float $min = -180.0, float $max = 180.0, int $decimals = 6): float`
Generates a random longitude coordinate between -180.0 and 180.0.

```php
// Default: full range
$lng = $this->faker->longitude();
// Output: -98.765432

// US-specific range
$lng = $this->faker->longitude(-125.0, -65.0);
// Output: -112.345678

// Custom precision
$lng = $this->faker->longitude(decimals: 4);
// Output: 45.6789
```

**Use Cases:**
- Location factories (stores, events, users)
- Geographic data generation
- Map coordinate testing

---

### 2. Random Float (Faker-Compatible)

#### `randomFloat(int $decimals = 2, float $min = 0.0, float $max = 100.0): float`
Alias for `floatBetween()` with FakerPHP-compatible parameter order.

```php
// Equivalent to FakerPHP: randomFloat(2, 10, 100)
$price = $this->faker->randomFloat(2, 10.0, 100.0);
// Output: 45.67

// Different precision
$rating = $this->faker->randomFloat(1, 0.0, 5.0);
// Output: 4.2
```

**Migration Pattern:**
```php
// OLD (FakerPHP)
$value = $faker->randomFloat(2, 10, 100);

// NEW (SimpleFaker) - Same call!
$value = $this->faker->randomFloat(2, 10, 100);
```

---

### 3. Optional Values

#### `optional(float $weight = 0.5): OptionalProxy`
Returns a proxy that generates null or a value based on probability.

```php
// 50% chance of null
$middleName = $this->faker->optional()->firstName();
// Output: "John" or null

// 70% chance of value (30% null)
$nickname = $this->faker->optional(0.7)->username();
// Output: "john_doe" or null

// 10% chance of value (90% null)
$suffix = $this->faker->optional(0.1)->randomElement(['Jr.', 'Sr.', 'III']);
// Output: "Jr." or null (mostly null)
```

**Use in Factories:**
```php
protected function definition(): array
{
    return [
        'firstName' => $this->faker->firstName(),
        'middleName' => $this->faker->optional(0.3)->firstName(), // 30% have middle name
        'lastName' => $this->faker->lastName(),
        'suffix' => $this->faker->optional(0.1)->randomElement(['Jr.', 'Sr.']), // 10% have suffix
        'bio' => $this->faker->optional(0.6)->paragraph(), // 60% have bio
    ];
}
```

**Migration Pattern:**
```php
// OLD (FakerPHP)
$value = $faker->optional(0.7)->value;

// NEW (SimpleFaker) - Use method call
$value = $this->faker->optional(0.7)->firstName();

// OLD (ternary workaround)
$value = $faker->boolean(70) ? $faker->firstName() : null;

// NEW (cleaner)
$value = $this->faker->optional(0.7)->firstName();
```

---

### 4. Date/Time Convenience

#### `dateTimeThisMonth(): string`
Generates a random date/time within the last month.

```php
$recentDate = $this->faker->dateTimeThisMonth();
// Output: "2026-01-15 14:32:45"
```

**Equivalent to:**
```php
$date = $this->faker->dateTimeBetween('-1 month', 'now');
```

**Use Cases:**
- Recent activity timestamps
- Current month data generation
- Recent post/comment dates

---

### 5. Placeholder Images

#### `imageUrl(int $width = 640, int $height = 480, ?string $category = null): string`
Generates a placeholder image URL using picsum.photos.

```php
// Default size (640x480)
$avatar = $this->faker->imageUrl();
// Output: "https://picsum.photos/640/480"

// Custom dimensions
$banner = $this->faker->imageUrl(1200, 300);
// Output: "https://picsum.photos/1200/300"

// With category (optional)
$thumbnail = $this->faker->imageUrl(200, 200, 'nature');
// Output: "https://picsum.photos/200/200?category=nature"
```

**Use in Factories:**
```php
protected function definition(): array
{
    return [
        'avatar' => $this->faker->imageUrl(200, 200),
        'coverPhoto' => $this->faker->imageUrl(1200, 400),
        'thumbnail' => $this->faker->imageUrl(150, 150),
    ];
}
```

---

## Complete Factory Example

Here's a factory using all new methods:

```php
<?php declare(strict_types=1);

namespace Modules\Location\Factories;

use Proto\Models\Factory;
use Modules\Location\Models\Store;

class StoreFactory extends Factory
{
    protected static ?string $model = Store::class;

    protected function definition(): array
    {
        return [
            'name' => $this->faker->company(),
            'slug' => $this->faker->slug(3),

            // Geographic coordinates
            'latitude' => $this->faker->latitude(25.0, 50.0, 6),
            'longitude' => $this->faker->longitude(-125.0, -65.0, 6),

            // Address
            'address' => $this->faker->streetAddress(),
            'city' => $this->faker->city(),
            'state' => $this->faker->stateAbbr(),
            'zipCode' => $this->faker->postcode(),

            // Optional fields
            'suite' => $this->faker->optional(0.3)->randomElement(['A', 'B', 'C']),
            'description' => $this->faker->optional(0.7)->paragraph(),
            'website' => $this->faker->optional(0.5)->url(),

            // Images
            'logo' => $this->faker->imageUrl(200, 200),
            'coverPhoto' => $this->faker->imageUrl(1200, 400),

            // Numeric data
            'rating' => $this->faker->randomFloat(1, 0.0, 5.0),
            'squareFeet' => $this->faker->randomFloat(0, 500.0, 5000.0),

            // Timestamps
            'openedAt' => $this->faker->dateTimeThisMonth(),
            'status' => $this->faker->randomElement(['open', 'closed', 'pending']),
        ];
    }
}
```

## Testing the Methods

Run tests to verify functionality:

```bash
composer test -- --filter SimpleFakerTest
```

All 12 tests should pass, covering:
- Latitude/longitude generation and ranges
- randomFloat() with various parameters
- dateTimeThisMonth() timestamp validation
- imageUrl() generation and formatting
- optional() probability distribution

## Method Count Update

SimpleFaker now has **58 methods** (up from 52):
- `latitude()` - Geographic coordinate generation
- `longitude()` - Geographic coordinate generation
- `randomFloat()` - FakerPHP-compatible float generation
- `optional()` - Probabilistic null/value generation
- `dateTimeThisMonth()` - Recent date convenience
- `imageUrl()` - Placeholder image URLs

## Notes

1. **`optional()` uses OptionalProxy**: A lightweight proxy class that intercepts method calls and returns null based on probability.

2. **Image URLs**: Uses picsum.photos for realistic placeholder images. These URLs work in tests and can be replaced with real uploads in production.

3. **Coordinate Precision**: Default 6 decimal places provides ~0.11m accuracy, suitable for most location needs.

4. **Probability Ranges**: `optional(weight)` accepts 0.0 to 1.0, automatically clamped to valid range.

5. **Backward Compatible**: All existing SimpleFaker functionality remains unchanged.
