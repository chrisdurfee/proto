# Nested Feature Modules Architecture

Proto supports nested feature modules within parent modules, allowing for better organization of large modules with many related features.

## Overview

Instead of a flat module structure where all features live at the module root, features can be nested as subdirectories within a parent module. This creates a service container pattern where the parent module acts as a namespace for related features.

## Directory Structure

### Flat Module (Traditional)
```
modules/
  User/
    UserModule.php
    Api/
      api.php
      Account/
        api.php
    Controllers/
    Models/
    Services/
```

### Nested Feature Module (New)
```
modules/
  Community/
    CommunityModule.php           # Parent module class
    Main/                          # Root-level module code (optional)
      Api/
        api.php                    # Handles /api/community
      Controllers/
      Models/
    Group/                         # Nested feature
      Api/
        api.php                    # Handles /api/community/group
        Settings/
          api.php                  # Handles /api/community/group/settings
      Controllers/
      Models/
      Migrations/
      Services/
    Events/                        # Another nested feature
      Api/
        api.php                    # Handles /api/community/events
      Controllers/
      Models/
    Gateway/
      Gateway.php                  # Parent gateway with feature access
```

### Deep Nested Features
```
modules/
  Community/
    Group/
      Forum/                       # Deep nested feature
        Api/
          api.php                  # Handles /api/community/group/forum
        Controllers/
        Models/
        Migrations/
      Posts/                       # Another deep feature
        Api/
          api.php                  # Handles /api/community/group/posts
```

## URL Resolution

The `ResourceHelper` resolves URLs in the following order:

1. **Nested Feature**: `modules/{Seg1}/{Seg2}/Api/{Seg3...}/api.php`
2. **Nested Feature with Main**: `modules/{Seg1}/{Seg2}/Main/Api/{Seg3...}/api.php`
3. **Flat Module**: `modules/{Seg1}/Api/{Seg2...}/api.php`
4. **Main Folder Fallback**: `modules/{Seg1}/Main/Api/{Seg2...}/api.php`
5. **Recursive Fallback**: Try parent paths if specific file not found

### Examples

| URL | Resolution Path |
|-----|-----------------|
| `/api/community/group` | `modules/Community/Group/Api/api.php` |
| `/api/community/group/settings` | `modules/Community/Group/Api/Settings/api.php` |
| `/api/community/group/forum` | `modules/Community/Group/Forum/Api/api.php` |
| `/api/user` | `modules/User/Api/api.php` OR `modules/User/Main/Api/api.php` |
| `/api/user/account` | `modules/User/Api/Account/api.php` OR `modules/User/Account/Api/api.php` |

## The "Main" Folder Convention

For modules that need both root-level functionality AND nested features, use a `Main/` folder:

```
modules/
  User/
    UserModule.php
    Main/                          # Root user functionality
      Api/
        api.php                    # /api/user
      Controllers/
        UserController.php
      Models/
        User.php
    Profile/                       # Nested feature
      Api/
        api.php                    # /api/user/profile
      Controllers/
      Models/
    Settings/                      # Another nested feature
      Api/
        api.php                    # /api/user/settings
```

This allows `/api/user` to route to `Main/Api/api.php` while `/api/user/profile` routes to `Profile/Api/api.php`.

## Module Registration

Parent modules are registered as usual. Features within them are automatically available - no separate registration needed.

```php
// common/Config/.env
{
    "modules": ["Community", "User", "Auth"]
}
```

When `Community` is activated, all its nested features (Group, Events, etc.) are accessible via their API routes.

## Gateway Access Pattern

Parent gateways expose child features as methods for hierarchical access:

```php
// modules/Community/Gateway/Gateway.php
<?php declare(strict_types=1);

namespace Modules\Community\Gateway;

use Modules\Community\Group\Gateway\Gateway as GroupGateway;
use Modules\Community\Events\Gateway\Gateway as EventsGateway;

class Gateway
{
    public function group(): GroupGateway
    {
        return new GroupGateway();
    }

    public function events(): EventsGateway
    {
        return new EventsGateway();
    }

    public function v1(): V1\Gateway
    {
        return new V1\Gateway();
    }
}
```

**Usage**:
```php
// Access nested feature gateway
modules()->community()->group()->addMember($userId, $groupId);

// Access nested feature with versioning
modules()->community()->group()->v1()->createGroup($data);
```

## Migrations

Migrations are discovered recursively throughout the module structure:

- `modules/Community/Migrations/`
- `modules/Community/Group/Migrations/`
- `modules/Community/Group/Forum/Migrations/`

All migration directories up to 6 levels deep are automatically scanned.

## Namespacing

Features follow PSR-4 autoloading with the full path namespace:

```php
// modules/Community/Group/Models/Group.php
namespace Modules\Community\Group\Models;

use Proto\Models\Model;

class Group extends Model
{
    // ...
}
```

```php
// modules/Community/Group/Controllers/GroupController.php
namespace Modules\Community\Group\Controllers;

use Proto\Controllers\ResourceController;
use Modules\Community\Group\Models\Group;

class GroupController extends ResourceController
{
    public function __construct(protected ?string $model = Group::class)
    {
        parent::__construct();
    }
}
```

## Feature API Routes

Each feature has its own `Api/api.php` that registers routes:

```php
// modules/Community/Group/Api/api.php
<?php declare(strict_types=1);

use Modules\Community\Group\Controllers\GroupController;
use Modules\Community\Group\Controllers\MemberController;

// Resource routes for /api/community/group
router()->resource('community/group', GroupController::class);

// Nested resource for /api/community/group/:groupId/member
router()->resource('community/group/:groupId/member', MemberController::class);
```

## Policies and Feature Flags

Use policies at the controller level to handle authorization and feature flags:

```php
// modules/Community/Group/Auth/Policies/GroupPolicy.php
namespace Modules\Community\Group\Auth\Policies;

use Common\Auth\Policies\Policy;

class GroupPolicy extends Policy
{
    protected ?string $type = 'community.group';

    protected function before(): bool
    {
        // Check if feature is enabled
        return env('features.community.groups', true);
    }

    public function add(): bool
    {
        return auth()->user->canCreateGroups();
    }
}
```

## Best Practices

1. **Use nested features** when a module has 3+ distinct sub-domains
2. **Use Main folder** when the module needs root-level code alongside features
3. **Keep features self-contained** - each feature should have its own Controllers, Models, Services
4. **Share via Gateway** - expose cross-feature functionality through parent gateway methods
5. **Migrations per feature** - keep migrations in the feature that owns the tables
6. **Tests per feature** - organize tests alongside feature code in `Feature/Tests/`

## Code Generation

The `Generator` class supports the `featurePath` setting for generating files in nested feature directories:

```php
use Proto\Generators\Generator;

$generator = new Generator();

// Generate a resource in a nested feature
$settings = (object)[
    'moduleName' => 'Community',
    'featurePath' => 'Group',           // Creates in modules/Community/Group/
    'model' => (object)[
        'className' => 'GroupMember',
        'tableName' => 'group_members'
    ]
];

$generator->createResource($settings);
// Creates:
// - modules/Community/Group/Models/GroupMember.php
// - modules/Community/Group/Controllers/GroupMemberController.php
// - modules/Community/Group/Api/api.php
// - modules/Community/Group/Storage/GroupMemberStorage.php

// Generate in a deep nested feature
$settings = (object)[
    'moduleName' => 'Community',
    'featurePath' => 'Group/Forum',     // Creates in modules/Community/Group/Forum/
    'model' => (object)[
        'className' => 'ForumPost',
        'tableName' => 'forum_posts'
    ]
];

$generator->createResource($settings);
// Creates files in modules/Community/Group/Forum/

// Generate individual files with featurePath
$generator->generateFileResource('migration', (object)[
    'moduleName' => 'Community',
    'featurePath' => 'Group',
    'className' => 'CreateGroupMembersTable'
]);
// Creates: modules/Community/Group/Migrations/YYYY-MM-DDTHH.mm.ss.uuuuuu_CreateGroupMembersTable.php
```

The generated files will have proper namespaces:
```php
// Generated file: modules/Community/Group/Models/GroupMember.php
namespace Modules\Community\Group\Models;

use Proto\Models\Model;

class GroupMember extends Model
{
    // ...
}
```

## Migration Guide

### From Flat to Nested

1. Create feature subdirectory: `modules/User/Profile/`
2. Move related files: Controllers, Models, Services
3. Create `Api/api.php` in the feature directory
4. Update namespaces from `Modules\User\...` to `Modules\User\Profile\...`
5. Update route paths in api.php
6. Move migrations to `Feature/Migrations/`
7. Run `composer dump-autoload`

### Backward Compatibility

Existing flat modules continue to work. The router checks flat paths if nested paths don't match, so migration can be gradual.
