<?php

namespace Tests\Feature;

use App\Models\Hour;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class AdministratorTest extends TestCase
{

    use RefreshDatabase;

    /** @test */
    public function administrator_must_first_login()
    {
        $this->withoutExceptionHandling();

        $user = $this->getModel();
        $role = $this->getRoleAdmin();

        $this->actingAs($user)->get('/dashboard')->assertRedirect('login');

        $user->attachRole($role);
        $response = $this->actingAs($user)->get('/dashboard')->assertViewIs('admin.dashboard');
        $response->assertSee('super administrator dashboard');
    }


    /** @test */
    public function administrator_can_add_hour()
    {
        $this->withoutExceptionHandling();

        $user = $this->getModel();
        $role = $this->getRoleAdmin();
        $permit = $this->getPermitCreateHour();

        $this->actingAs($user)->get('/addNewHour')->assertRedirect(route('login'));

        $role->attachPermission($permit);
        $user->attachRole($role);

        $response = $this->actingAs($user)->get('/addNewHour');
        $response->assertViewIs('admin.addHour');
        $response->assertSee('add new hour');
    }


    /** @test */
    public function administrator_can_add_hour_POST()
    {
        $this->withoutExceptionHandling();

        $user = $this->getModel();
        $role = $this->getRoleAdmin();
        $permit = $this->getPermitCreateHour();

        $response = $this->actingAs($user)->post('createNewHour', ['user_id' => $user->id, 'date' => '1983/02/01', 'hour' => 800, 'nonWork' => 0]);
        $response->assertSessionHas('RED');

        $role->attachPermission($permit);
        $user->attachRole($role);

        $this->actingAs($user)->post('/createNewHour', ['user_id' => $user->id, 'date' => '1983/02/01', 'hour' => 800, 'nonWork' => 0]);

        $hour = Hour::first();

        $this->assertCount(1, Hour::all());
        $this->assertEquals($user->id, $hour->user_id );
    }


    /** @test */
    public function administrator_duplicate_hour()
    {
        $this->withoutExceptionHandling();

        $user = $this->getModel();
        $role = $this->getRoleAdmin();
        $permit = $this->getPermitCreateHour();
        $role->attachPermission($permit);
        $user->attachRole($role);
        $this->actingAs($user)->post('/createNewHour', ['user_id' => $user->id, 'date' => '1983/02/01' , 'hour' => 800, 'nonWork' => 0]);

        $response = $this->actingAs($user)->post('/createNewHour', ['user_id' => $user->id, 'date' => '1983/02/01' , 'hour' => 800, 'nonWork' => 0]);
        $response->assertSessionHas('DUPLICATE');
        $response->assertStatus(302);
    }



    /** @test */
    public function administrator_can_update_an_hour()
    {
        $this->withoutExceptionHandling();

        $user = $this->getModel();
        $role = $this->getRoleAdmin();
        $permit = $this->getPermitCreateHour();
        $role->attachPermission($permit);
        $role->attachPermission($this->getPermitUpdate());
        $user->attachRole($role);
        $this->actingAs($user)->post('/createNewHour', ['user_id' => $user->id, 'date' => '1983/02/01' , 'hour' => 800, 'nonWork' => 0]);

        $this->assertCount(1, Hour::all());
        $hour = Hour::first();
        $this->assertEquals(800, $hour->hour);

        $response = $this->actingAs($user)->get('/hour-update/' .$hour->id);
        $response->assertViewIs('admin.edit-hour');
        $response->assertSee('administrator update hour');

    }

    /** @test */
    public function administrator_can_not_update_other_staff_hour()
    {
        $this->withoutExceptionHandling();

        $super = $this->getModel();
        $roleSuper = $this->getRoleSuper();
        $roleSuper->attachPermission($this->getPermitCreateHour());
        $super->attachRole($roleSuper);
        $this->actingAs($super)->post('/createNewHour', ['user_id' => $super->id, 'date' => '1983/02/01' , 'hour' => 800, 'nonWork' => 0]);
        $this->assertCount(1, Hour::all());

        $hour = Hour::first();
        $this->assertEquals(800, $hour->hour);

        $admin = $this->getModel();
        $roleAdmin = $this->getRoleAdmin();
        $permitUpdateHour = $this->getPermitUpdate();
        $roleAdmin->attachPermission($permitUpdateHour);
        $admin->attachRole($roleAdmin);
        $response = $this->actingAs($admin)->get('/hour-update/' .$hour->id);
        $response->assertSessionHas('ALERT');
    }

    /** @test */
    public function administrator_can_see_all_his_hour()
    {
        $this->withoutExceptionHandling();

        $user = $this->getModel();
        $role = $this->getRoleAdmin();
        $role->attachPermission($this->getPermitCreateHour());
        $role->attachPermission($this->getPermitReadHour());
        $user->attachRole($role);
        $this->actingAs($user)->post('/createNewHour', ['user_id' => $user->id, 'date' => '1983/02/01' , 'hour' => 800, 'nonWork' => 0]);
        $this->actingAs($user)->post('/createNewHour', ['user_id' => $user->id, 'date' => '1983/02/02' , 'hour' => 900, 'nonWork' => 0]);
        $this->actingAs($user)->post('/createNewHour', ['user_id' => $user->id, 'date' => '1983/02/03' , 'nonWork' => 1]);
        $this->actingAs($user)->post('/createNewHour', ['user_id' => $user->id, 'date' => '1983/02/04' , 'nonWork' => 1]);
        $this->actingAs($user)->post('/createNewHour', ['user_id' => $user->id, 'date' => '1983/02/05' , 'hour' => 1000, 'nonWork' => 0]);

        $this->assertCount(5, Hour::all());
        $response = $this->actingAs($user)->get('/allMyHours')->assertViewIs('admin.allHours');
        $response->assertSeeInOrder(['1983/02/05', '1983/02/04', '1983/02/03', '1983/02/02', '1983/02/01']);

    }




    /**
     * @return \Illuminate\Database\Eloquent\Collection|\Illuminate\Database\Eloquent\Model|mixed
     */
    private function getModel()
    {
        return User::factory()->create();
    }

    /**
     * @return \Illuminate\Database\Eloquent\Collection|\Illuminate\Database\Eloquent\Model|mixed
     */
    private function getPermitCreateHour()
    {
        return Permission::factory()->create(['name' => 'hour-create']);
    }

    private function getRoleAdmin()
    {
        return Role::factory()->create(['name' => 'administrator']);
    }

    private function getPermitUpdate()
    {
        return Permission::factory()->create(['name' => 'hour-update']);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Collection|\Illuminate\Database\Eloquent\Model|mixed
     */
    private function getRoleSuper()
    {
        return Role::factory()->create(['name' => 'superadministrator']);
    }

    private function getPermitReadHour()
    {
        return Permission::factory()->create(['name' => 'hour-read']);
    }
}
