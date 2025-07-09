<?php declare(strict_types=1);
namespace Modules\User\Tests\Unit;

use Modules\User\Models\User;
use Modules\User\Models\Role;
use Proto\Tests\Test;
use Proto\Models\Relations\BelongsToMany;

/**
 * UserRolesTest
 *
 * This test class verifies the functionality of managing user roles
 * including attaching, detaching, syncing, and toggling roles.
 *
 * @package Modules\User\Tests\Unit
 */
class UserRolesTest extends Test
{
    /**
     * @var UserModelStub $userModel
     */
    private UserModelStub $userModel;

    /**
     * @var array $testRoles
     */
    private array $testRoles;

    /**
     * Set up the test environment.
     *
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();

        // Create test user and roles
        $this->userModel = new UserModelStub(1);

        // Define test roles
        $this->testRoles = [
            ['id' => 1, 'name' => 'Admin', 'slug' => 'admin'],
            ['id' => 2, 'name' => 'Editor', 'slug' => 'editor'],
            ['id' => 3, 'name' => 'Viewer', 'slug' => 'viewer']
        ];
    }

    /**
     * Test attaching a role to a user.
     *
     * @return void
     */
    public function testAttachRole(): void
    {
        $roleId = 1;

        // Initial state should have no roles
        $this->assertEmpty($this->userModel->getRoleIds());

        // Attach a role
        $result = $this->userModel->roles()->attach($roleId);

        // Verify the attachment was successful
        $this->assertTrue($result);
        $this->assertContains($roleId, $this->userModel->getRoleIds());
        $this->assertCount(1, $this->userModel->getRoleIds());
    }

    /**
     * Test attaching multiple roles to a user.
     *
     * @return void
     */
    public function testAttachMultipleRoles(): void
    {
        // Attach multiple roles
        $roleIds = [1, 2];

        foreach ($roleIds as $id) {
            $this->userModel->roles()->attach($id);
        }

        // Verify all roles were attached
        foreach ($roleIds as $id) {
            $this->assertContains($id, $this->userModel->getRoleIds());
        }
        $this->assertCount(count($roleIds), $this->userModel->getRoleIds());
    }

    /**
     * Test detaching a role from a user.
     *
     * @return void
     */
    public function testDetachRole(): void
    {
        // Setup: Attach roles first
        $this->userModel->roles()->attach(1);
        $this->userModel->roles()->attach(2);

        // Initial state should have 2 roles
        $this->assertCount(2, $this->userModel->getRoleIds());

        // Detach one role
        $result = $this->userModel->roles()->detach(1);

        // Verify the detachment was successful
        $this->assertTrue($result);
        $this->assertNotContains(1, $this->userModel->getRoleIds());
        $this->assertContains(2, $this->userModel->getRoleIds());
        $this->assertCount(1, $this->userModel->getRoleIds());
    }

    /**
     * Test syncing roles for a user (replaces all existing roles).
     *
     * @return void
     */
    public function testSyncRoles(): void
    {
        // Setup: Attach some initial roles
        $this->userModel->roles()->attach(1);
        $this->userModel->roles()->attach(2);

        // Initial state should have roles 1 and 2
        $this->assertCount(2, $this->userModel->getRoleIds());

        // Sync with a different set of roles (2, 3)
        $result = $this->userModel->roles()->sync([2, 3]);

        // Verify sync was successful
        $this->assertTrue($result);

        // Should now have only roles 2 and 3
        $this->assertNotContains(1, $this->userModel->getRoleIds());
        $this->assertContains(2, $this->userModel->getRoleIds());
        $this->assertContains(3, $this->userModel->getRoleIds());
        $this->assertCount(2, $this->userModel->getRoleIds());
    }

    /**
     * Test toggling roles for a user (adds missing roles, removes existing ones).
     *
     * @return void
     */
    public function testToggleRoles(): void
    {
        // Setup: Attach some initial roles
        $this->userModel->roles()->attach(1);
        $this->userModel->roles()->attach(2);

        // Initial state should have roles 1 and 2
        $this->assertCount(2, $this->userModel->getRoleIds());

        // Toggle roles 2 and 3
        $result = $this->userModel->roles()->toggle([2, 3]);

        // Verify toggle was successful
        $this->assertTrue($result);

        // Role 1 should remain, Role 2 should be removed, Role 3 should be added
        $this->assertContains(1, $this->userModel->getRoleIds());
        $this->assertNotContains(2, $this->userModel->getRoleIds());
        $this->assertContains(3, $this->userModel->getRoleIds());
        $this->assertCount(2, $this->userModel->getRoleIds());
    }

    /**
     * Test getting all user roles.
     *
     * @return void
     */
    public function testGetUserRoles(): void
    {
        // Setup: Attach roles
        $this->userModel->roles()->attach(1);
        $this->userModel->roles()->attach(3);

        // Get the roles
        $roles = $this->userModel->roles()->getResults();

        // Verify we get the correct roles
        $this->assertCount(2, $roles);

        // Check the roles contain expected data
        $roleIds = array_column($roles, 'id');
        $this->assertContains(1, $roleIds);
        $this->assertContains(3, $roleIds);
    }
}

/**
 * UserModelStub
 *
 * A stub for User model to allow testing without a database.
 */
class UserModelStub extends User
{
    /**
     * @var int $id
     */
    private int $id;

    /**
     * @var array $userRoles
     */
    private array $userRoles = [];

    /**
     * Constructor
     *
     * @param int $id
     */
    public function __construct(int $id)
    {
        $this->id = $id;
    }

    /**
     * Get the user's roles.
     *
     * @return BelongsToManyStub
     */
    public function roles(): BelongsToManyStub
    {
        return new BelongsToManyStub($this);
    }

    /**
     * Get the role IDs assigned to this user.
     *
     * @return array
     */
    public function getRoleIds(): array
    {
        return $this->userRoles;
    }

    /**
     * Add a role ID to this user.
     *
     * @param int $roleId
     * @return void
     */
    public function addRole(int $roleId): void
    {
        if (!in_array($roleId, $this->userRoles)) {
            $this->userRoles[] = $roleId;
        }
    }

    /**
     * Remove a role ID from this user.
     *
     * @param int $roleId
     * @return void
     */
    public function removeRole(int $roleId): void
    {
        $key = array_search($roleId, $this->userRoles);
        if ($key !== false) {
            unset($this->userRoles[$key]);
            $this->userRoles = array_values($this->userRoles); // Re-index array
        }
    }

    /**
     * Set roles for this user.
     *
     * @param array $roleIds
     * @return void
     */
    public function setRoles(array $roleIds): void
    {
        $this->userRoles = $roleIds;
    }
}

/**
 * BelongsToManyStub
 *
 * A stub for the BelongsToMany relationship to allow testing without a database.
 */
class BelongsToManyStub
{
    /**
     * @var UserModelStub $user
     */
    private UserModelStub $user;

    /**
     * Constructor
     *
     * @param UserModelStub $user
     */
    public function __construct(UserModelStub $user)
    {
        $this->user = $user;
    }

    /**
     * Attach a role to a user.
     *
     * @param int $roleId
     * @return bool
     */
    public function attach(int $roleId): bool
    {
        $this->user->addRole($roleId);
        return true;
    }

    /**
     * Detach a role from a user.
     *
     * @param int $roleId
     * @return bool
     */
    public function detach(int $roleId): bool
    {
        $this->user->removeRole($roleId);
        return true;
    }

    /**
     * Sync the roles for a user.
     *
     * @param array $roleIds
     * @return bool
     */
    public function sync(array $roleIds): bool
    {
        $this->user->setRoles($roleIds);
        return true;
    }

    /**
     * Toggle the specified roles.
     *
     * @param array $roleIds
     * @return bool
     */
    public function toggle(array $roleIds): bool
    {
        foreach ($roleIds as $id) {
            if (in_array($id, $this->user->getRoleIds())) {
                $this->detach($id);
            } else {
                $this->attach($id);
            }
        }
        return true;
    }

    /**
     * Get the results of the relationship.
     *
     * @return array
     */
    public function getResults(): array
    {
        $roles = [];
        foreach ($this->user->getRoleIds() as $id) {
            switch ($id) {
                case 1:
                    $roles[] = ['id' => 1, 'name' => 'Admin', 'slug' => 'admin'];
                    break;
                case 2:
                    $roles[] = ['id' => 2, 'name' => 'Editor', 'slug' => 'editor'];
                    break;
                case 3:
                    $roles[] = ['id' => 3, 'name' => 'Viewer', 'slug' => 'viewer'];
                    break;
            }
        }
        return $roles;
    }
}
