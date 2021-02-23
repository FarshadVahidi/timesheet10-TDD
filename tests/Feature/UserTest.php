<?php

namespace Tests\Feature;

use App\Models\Hour;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class UserTest extends TestCase
{

    use RefreshDatabase;

    /** @test */
    public function user_must_first_login()
    {
        $this->withoutExceptionHandling();

        $user = $this->getModel();
        $role = $this->getRoleUser();

        $this->actingAs($user)->get('/dashboard')->assertRedirect('login');

        $user->attachRole($role);
        $response = $this->actingAs($user)->get('/dashboard')->assertViewIs('user.dashboard');
        $response->assertSee('user dashboard');
    }


    /** @test */
    public function user_can_add_hour()
    {
        $this->withoutExceptionHandling();

        $user = $this->getModel();
        $role = $this->getRoleUser();
        $permit = $this->getPermitCreateHour();

        $this->actingAs($user)->get('/addNewHour')->assertRedirect(route('login'));

        $role->attachPermission($permit);
        $user->attachRole($role);

        $response = $this->actingAs($user)->get('/addNewHour');
        $response->assertViewIs('user.addHour');
        $response->assertSee('add new hour');
    }


    /** @test */
    public function user_can_add_hour_POST()
    {
        $this->withoutExceptionHandling();

        $user = $this->getModel();
        $role = $this->getRoleUser();
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
    public function user_can_update_an_hour()
    {
        $this->withoutExceptionHandling();

        $user = $this->getModel();
        $role = $this->getRoleUser();
        $permit = $this->getPermitCreateHour();
        $role->attachPermission($permit);
        $role->attachPermission($this->getPermitUpdate());
        $user->attachRole($role);
        $this->actingAs($user)->post('/createNewHour', ['user_id' => $user->id, 'date' => '1983/02/01' , 'hour' => 800, 'nonWork' => 0]);

        $this->assertCount(1, Hour::all());
        $hour = Hour::first();
        $this->assertEquals(800, $hour->hour);

        $response = $this->actingAs($user)->get('/hour-update/' .$hour->id);
        $response->assertViewIs('user.edit-hour');
        $response->assertSee('user update hour');

    }

    /** @test */
    public function user_can_not_update_other_staff_hour()
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

        $user = $this->getModel();
        $roleUser = $this->getRoleUser();
        $permitUpdateHour = $this->getPermitUpdate();
        $roleUser->attachPermission($permitUpdateHour);
        $user->attachRole($roleUser);
        $response = $this->actingAs($user)->get('/hour-update/' .$hour->id);
        $response->assertSessionHas('ALERT');
    }


    /** @test */
    public function user_can_see_all_his_hour()
    {
        $this->withoutExceptionHandling();

        $user = $this->getModel();
        $role = $this->getRoleUser();
        $role->attachPermission($this->getPermitCreateHour());
        $role->attachPermission($this->getPermitReadHour());
        $user->attachRole($role);
        $this->actingAs($user)->post('/createNewHour', ['user_id' => $user->id, 'date' => '1983/02/01' , 'hour' => 800, 'nonWork' => 0]);
        $this->actingAs($user)->post('/createNewHour', ['user_id' => $user->id, 'date' => '1983/02/02' , 'hour' => 900, 'nonWork' => 0]);
        $this->actingAs($user)->post('/createNewHour', ['user_id' => $user->id, 'date' => '1983/02/03' , 'nonWork' => 1]);
        $this->actingAs($user)->post('/createNewHour', ['user_id' => $user->id, 'date' => '1983/02/04' , 'nonWork' => 1]);
        $this->actingAs($user)->post('/createNewHour', ['user_id' => $user->id, 'date' => '1983/02/05' , 'hour' => 1000, 'nonWork' => 0]);

        $this->assertCount(5, Hour::all());
        $response = $this->actingAs($user)->get('/allMyHours')->assertViewIs('user.allHours');
        $response->assertSeeInOrder(['1983/02/05', '1983/02/04', '1983/02/03', '1983/02/02', '1983/02/01']);
    }


    /** @test */
    public function user_can_not_add_new_person()
    {
        $this->withoutExceptionHandling();

        $user = $this->getModel();
        $role = $this->getRoleUser();
        $user->attachRole($role);

        $response = $this->actingAs($user)->post('/addNewPerson',['name'=> 'farshad', 'email' => 'farshad1@app.com', 'password' => '12345678', 'role_id' => 'user' ]);

        $this->assertCount(1, User::all());
        $response->assertSessionHas('RED');
    }


    /**
     * @return \Illuminate\Database\Eloquent\Collection|\Illuminate\Database\Eloquent\Model|mixed
     */
    private function getModel()
    {
        return User::factory()->create();
    }

    private function getRoleUser()
    {
        return Role::factory()->create(['name' => 'user']);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Collection|\Illuminate\Database\Eloquent\Model|mixed
     */
    private function getPermitCreateHour()
    {
        return Permission::factory()->create(['name' => 'hour-create']);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Collection|\Illuminate\Database\Eloquent\Model|mixed
     */
    private function getRoleSuper()
    {
        return Role::factory()->create(['name' => 'superadministrator']);
    }

    private function getPermitUpdate()
    {
        return Permission::factory()->create(['name' => 'hour-update']);
    }

    private function getPermitReadHour()
    {
        return Permission::factory()->create(['name' => 'hour-read']);
    }
}
