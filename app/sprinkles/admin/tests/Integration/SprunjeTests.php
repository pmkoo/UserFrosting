<?php

namespace UserFrosting\Tests\Integration;

use Exception;
use Illuminate\Database\Capsule\Manager as DB;
use Symfony\Component\Console\Style\SymfonyStyle;
use UserFrosting\Sprinkle\Admin\Sprunje\UserPermissionSprunje;
use UserFrosting\Sprinkle\Core\Util\ClassMapper;
use UserFrosting\Tests\DatabaseTransactions;
use UserFrosting\Tests\TestCase;

/**
 * Integration tests for the built-in Sprunje classes.
 */
class SprunjeTests extends TestCase
{
    use DatabaseTransactions;

    protected $classMapper;

    /**
     * Setup the database schema.
     *
     * @return void
     */
    public function setUp()
    {
        parent::setUp();

        $this->classMapper = new ClassMapper();
    }

    /**
     * Tests...
     */
    public function testUserPermissionSprunje()
    {
        $fm = $this->ci->factory;

        // Generate some test models
        $users = $fm->seed(3, 'UserFrosting\Sprinkle\Account\Database\Models\User');
        $roles = $fm->seed(3, 'UserFrosting\Sprinkle\Account\Database\Models\Role');
        $permissions = $fm->seed(3, 'UserFrosting\Sprinkle\Account\Database\Models\Permission');

        // Create some relationships
        $roles[0]->permissions()->attach($permissions[1]);
        $roles[0]->permissions()->attach($permissions[2]);
        $roles[1]->permissions()->attach($permissions[2]);
        $roles[2]->permissions()->attach($permissions[0]);
        $roles[2]->permissions()->attach($permissions[1]);

        $users[0]->roles()->attach($roles[1]);
        $users[0]->roles()->attach($roles[2]);
        $users[1]->roles()->attach($roles[0]);
        $users[1]->roles()->attach($roles[1]);
        $users[2]->roles()->attach($roles[1]);

        $this->classMapper->setClassMapping('user', 'UserFrosting\Sprinkle\Account\Database\Models\User');

        // Test user 0
        $sprunje = new UserPermissionSprunje($this->classMapper, [
            'user_id' => $users[0]->id
        ]);

        list($count, $countFiltered, $models) = $sprunje->getModels();

        // Check that counts are correct
        $this->assertEquals(count($models), $count);
        $this->assertEquals(count($models), $countFiltered);

        // Ignore pivot and roles_via.  These are covered by the tests for the relationships themselves.
        static::ignoreRelations($models);
        $this->assertCollectionsSame(collect($permissions), $models);

        // Test user 1
        $sprunje = new UserPermissionSprunje($this->classMapper, [
            'user_id' => $users[1]->id
        ]);

        list($count, $countFiltered, $models) = $sprunje->getModels();

        // Check that counts are correct
        $this->assertEquals(count($models), $count);
        $this->assertEquals(count($models), $countFiltered);

        // Ignore pivot and roles_via.  These are covered by the tests for the relationships themselves.
        static::ignoreRelations($models);
        $this->assertCollectionsSame(collect([
            $permissions[1],
            $permissions[2]
        ]), $models);

        // Test user 2
        $sprunje = new UserPermissionSprunje($this->classMapper, [
            'user_id' => $users[2]->id
        ]);

        list($count, $countFiltered, $models) = $sprunje->getModels();

        // Check that counts are correct
        $this->assertEquals(count($models), $count);
        $this->assertEquals(count($models), $countFiltered);

        // Ignore pivot and roles_via.  These are covered by the tests for the relationships themselves.
        static::ignoreRelations($models);
        $this->assertCollectionsSame(collect([
            $permissions[2]
        ]), $models);
    }
}
