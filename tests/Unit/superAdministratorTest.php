<?php

namespace Tests\Unit;

use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class superAdministratorTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function super_administrator_role_check()
    {
        $this->withoutExceptionHandling();

        $user = User::factory()->create();
        $role = Role::factory()->create(['name' => 'superadministrator']);
        $permit = Permission::factory()->create(['name'=>'superadministrator']);
        $role->attachPermission($permit);

        $this->assertFalse($user->isSuper());

        $user->attachRole($role);
        $this->assertTrue($user->isSuper());
    }
}
