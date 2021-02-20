<?php

namespace Tests\Feature;

use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class superAdministratorTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function super_administrator_must_first_login()
    {
        $this->withoutExceptionHandling();

        $user = User::factory()->create();
        $role = Role::factory()->create(['name' => 'superadministrator']);
        $permit = Permission::factory()->create(['name'=>'superadministrator']);
        $role->attachPermission($permit);

        $this->actingAs($user)->get('/dashboard')->assertRedirect('login');

        $user->attachRole($role);
        $response = $this->actingAs($user)->get('/dashboard')->assertViewIs('super.dashboard');
        $response->assertSee('super administrator dashboard');
    }

    /** @test */
    public function super_administrator_can_add_hour()
    {
        $this->withoutExceptionHandling();

        $user = User::factory()->create();
        $role = Role::factory()->create(['name' => 'superadministrator']);
        $permit = Permission::factory()->create(['name'=>'superadministrator']);

        $this->actingAs($user)->get('/addNewHour')->assertRedirect(route('login'));

        $role->attachPermission($permit);
        $user->attachRole($role);

        $response = $this->actingAs($user)->get('/addNewHour');
        $response->assertViewIs('super.addHour');
        $response->assertSee('add new hour');
    }
}
