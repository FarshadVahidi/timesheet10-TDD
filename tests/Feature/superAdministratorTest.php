<?php

namespace Tests\Feature;

use App\Models\Hour;
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

        $user = $this->getModel();
        $role = $this->getRoleSuper();
        $permit = $this->getPermitCreateHour();
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

        $user = $this->getModel();
        $role = $this->getRoleSuper();
        $permit = $this->getPermitCreateHour();

        $this->actingAs($user)->get('/addNewHour')->assertRedirect(route('login'));

        $role->attachPermission($permit);
        $user->attachRole($role);

        $response = $this->actingAs($user)->get('/addNewHour');
        $response->assertViewIs('super.addHour');
        $response->assertSee('add new hour');
    }

    /** @test */
    public function super_administrator_can_add_hour_POST()
    {
        $this->withoutExceptionHandling();

        $user = $this->getModel();
        $role = $this->getRoleSuper();
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
    public function super_administrator_duplicate_hour()
    {
        $this->withoutExceptionHandling();

        $user = $this->getModel();
        $role = $this->getRoleSuper();
        $permit = $this->getPermitCreateHour();
        $role->attachPermission($permit);
        $user->attachRole($role);
        $this->actingAs($user)->post('/createNewHour', ['user_id' => $user->id, 'date' => '1983/02/01' , 'hour' => 800, 'nonWork' => 0]);

        $response = $this->actingAs($user)->post('/createNewHour', ['user_id' => $user->id, 'date' => '1983/02/01' , 'hour' => 800, 'nonWork' => 0]);
        $response->assertSessionHas('DUPLICATE');
        $response->assertStatus(302);
    }


    /** @test */
    public function super_administrator_can_update_an_hour()
    {
        $this->withoutExceptionHandling();

        $user = $this->getModel();
        $role = $this->getRoleSuper();
        $permit = $this->getPermitCreateHour();
        $role->attachPermission($permit);
        $role->attachPermission($this->getPermitUpdate());
        $user->attachRole($role);
        $this->actingAs($user)->post('/createNewHour', ['user_id' => $user->id, 'date' => '1983/02/01' , 'hour' => 800, 'nonWork' => 0]);

        $this->assertCount(1, Hour::all());
        $hour = Hour::first();
        $this->assertEquals(800, $hour->hour);

        $response = $this->actingAs($user)->get('/hour-update/' .$hour->id);
        $response->assertViewIs('super.edit-hour');
        $response->assertSee('super administrator update hour');

    }

    /** @test */
    public function access_denied_for_update_hour_for_unAuth_user()
    {
        $this->withoutExceptionHandling();

        $user = $this->getModel();
        $role = $this->getRoleSuper();
        $role->attachPermission($this->getPermitCreateHour());
        $user->attachRole($role);
        $this->actingAs($user)->post('/createNewHour', ['user_id' => $user->id, 'date' => '1983/02/01' , 'hour' => 800, 'nonWork' => 0]);
        $this->assertCount(1, Hour::all());
        $hour = Hour::first();

        $this->actingAs($user)->get('/hour-update/' .$hour->id)->assertRedirect(route('login'));

    }

    /** @test */
    public function if_user_entered_wrong_id_for_update_hour()
    {
        $this->withoutExceptionHandling();

        $user = $this->getModel();
        $role = $this->getRoleSuper();
        $role->attachPermission($this->getPermitUpdate());
        $user->attachRole($role);
        $this->assertCount(0, Hour::all());

        $this->actingAs($user)->get('/hour-update/1')->assertSessionHas('NOTEXIST');
    }

    /** @test */
    public function the_day_can_be_holiday_or_weekend()
    {
        $this->withoutExceptionHandling();

        $user = $this->getModel();
        $role = $this->getRoleSuper();
        $role->attachPermission($this->getPermitCreateHour());
        $user->attachRole($role);
        $this->actingAs($user)->post('/createNewHour', ['user_id' => $user->id, 'date' => '1983/02/01' , 'nonWork' => 1]);

        $hour = Hour::first();

        $this->assertCount(1, Hour::all());
        $this->assertEquals(0, $hour->hour);
        $this->assertEquals(1, $hour->ferie);
    }

    /** @test */
    public function super_administrator_can_see_all_his_hour()
    {
        $this->withoutExceptionHandling();

        $user = $this->getModel();
        $role = $this->getRoleSuper();
        $role->attachPermission($this->getPermitCreateHour());
        $role->attachPermission($this->getPermitReadHour());
        $user->attachRole($role);
        $this->actingAs($user)->post('/createNewHour', ['user_id' => $user->id, 'date' => '1983/02/01' , 'nonWork' => 1]);
        $this->actingAs($user)->post('/createNewHour', ['user_id' => $user->id, 'date' => '1983/02/02' , 'nonWork' => 1]);
        $this->actingAs($user)->post('/createNewHour', ['user_id' => $user->id, 'date' => '1983/02/03' , 'hour' => 800, 'nonWork' => 0]);
        $this->actingAs($user)->post('/createNewHour', ['user_id' => $user->id, 'date' => '1983/02/04' , 'hour' => 900, 'nonWork' => 0]);
        $this->actingAs($user)->post('/createNewHour', ['user_id' => $user->id, 'date' => '1983/02/05' , 'hour' => 1000, 'nonWork' => 0]);


        $this->assertCount(5, Hour::all());
        $response = $this->actingAs($user)->get('/allMyHours')->assertViewIs('super.allHours');
        $response->assertSeeInOrder(['1983/02/05', '1983/02/04', '1983/02/03', '1983/02/02', '1983/02/01']);

    }

    /** @test */
    public function if_user_without_permit_readHour_want_to_access_this_secction()
    {
        $this->withoutExceptionHandling();
        $user = new User();
        $response = $this->actingAs($user)->get('/allMyHours');
        $response->assertRedirect(route('login'));
    }

    /** @test */
    public function super_administrator_can_add_new_staff()
    {
        $this->withoutExceptionHandling();

        $user = $this->getModel();
        $role = $this->getRoleSuper();
        $permit = $this->getPermitCreateUser();
        $role->attachPermission($permit);
        $user->attachRole($role);

        $response = $this->actingAs($user)->get('/addNewPerson');
        $response->assertViewIs('super.registration');
    }

    /** @test */
    public function access_denied_for_user_without_users_create_permit()
    {
        $this->withoutExceptionHandling();

        $user = new User();
        $this->actingAs($user)->get('/addNewPerson')->assertRedirect(route('login'));
    }

    /** @test */
    public function super_administrator_send_post_request_to_add_new_user()
    {
        $this->withoutExceptionHandling();

        $user = $this->getModel();
        $role = $this->getRoleSuper();
        $permit = $this->getPermitCreateUser();
        $role->attachPermission($permit);
        $user->attachRole($role);
        $this->createRoleUser();

        $response = $this->actingAs($user)->post('/addNewPerson',['name'=> 'farshad', 'email' => 'farshad@app.com', 'password' => '12345678', 'role_id' => 'user' ]);

        $this->assertCount(2, User::all());
        $this->assertEquals('farshad', User::find(2)->name);
        $response->assertSessionHas('USER-ADDED');
    }

    /** @test */
    public function unAuth_user_can_not_access_to_route_post_addNewPerson()
    {
        $this->withoutExceptionHandling();

        $user = $this->getModel();
        $this->getRoleSuper();

        $response = $this->actingAs($user)->post('/addNewPerson',['name'=> 'farshad', 'email' => 'farshad@app.com', 'password' => '12345678', 'role_id' => 'superadministrator' ]);

        $response->assertSessionHas('RED');
        $this->assertCount(1, User::all());
    }

    /** @test */
    public function super_admin_can_see_allHour_of_staff()
    {
        $this->withoutExceptionHandling();
        //setting add hour for super administrator
        $super = $this->getModel();
        $roleSuper = $this->getRoleSuper();
        $permit = $this->getPermitCreateHour();
        $roleSuper->attachPermission($permit);
        $super->attachRole($roleSuper);
        $this->actingAs($super)->post('/createNewHour', ['user_id' => $super->id, 'date' => '1983/02/01' , 'nonWork' => 1]);
        $this->actingAs($super)->post('/createNewHour', ['user_id' => $super->id, 'date' => '1983/02/02' , 'nonWork' => 1]);
        $this->actingAs($super)->post('/createNewHour', ['user_id' => $super->id, 'date' => '1983/02/03' , 'hour' => 800, 'nonWork' => 0]);
        $this->actingAs($super)->post('/createNewHour', ['user_id' => $super->id, 'date' => '1983/02/04' , 'hour' => 900, 'nonWork' => 0]);
        $this->actingAs($super)->post('/createNewHour', ['user_id' => $super->id, 'date' => '1983/02/05' , 'hour' => 1000, 'nonWork' => 0]);

        //setting add hour for administrator
        $admin = $this->getModel();
        $roleAdmin = $this->createRoleAdmin();
        $roleAdmin->attachPermission($permit);
        $admin->attachRole($roleAdmin);
        $this->actingAs($admin)->post('/createNewHour', ['user_id' => $admin->id, 'date' => '1983/02/01' , 'nonWork' => 1]);
        $this->actingAs($admin)->post('/createNewHour', ['user_id' => $admin->id, 'date' => '1983/02/02' , 'nonWork' => 1]);
        $this->actingAs($admin)->post('/createNewHour', ['user_id' => $admin->id, 'date' => '1983/02/03' , 'hour' => 700, 'nonWork' => 0]);
        $this->actingAs($admin)->post('/createNewHour', ['user_id' => $admin->id, 'date' => '1983/02/04' , 'hour' => 600, 'nonWork' => 0]);
        $this->actingAs($admin)->post('/createNewHour', ['user_id' => $admin->id, 'date' => '1983/02/05' , 'hour' => 500, 'nonWork' => 0]);

        //setting add hour for user
        $user = $this->getModel();
        $roleSuper = $this->createRoleUser();
        $roleSuper->attachPermission($permit);
        $user->attachRole($roleSuper);
        $this->actingAs($user)->post('/createNewHour', ['user_id' => $user->id, 'date' => '1983/02/01' , 'nonWork' => 1]);
        $this->actingAs($user)->post('/createNewHour', ['user_id' => $user->id, 'date' => '1983/02/02' , 'nonWork' => 1]);
        $this->actingAs($user)->post('/createNewHour', ['user_id' => $user->id, 'date' => '1983/02/03' , 'hour' => 500, 'nonWork' => 0]);
        $this->actingAs($user)->post('/createNewHour', ['user_id' => $user->id, 'date' => '1983/02/04' , 'hour' => 500, 'nonWork' => 0]);
        $this->actingAs($user)->post('/createNewHour', ['user_id' => $user->id, 'date' => '1983/02/05' , 'hour' => 500, 'nonWork' => 0]);

        $this->assertCount(15, Hour::all());
        $super->attachPermission($this->getPermitReadHour());

        $response = $this->actingAs($super)->get('/staffHour')->assertViewIs('super.staffHour');
        $response->assertViewHas('staffHour');
        $response->assertSee('1500');
    }

    /** @test */
    public function just_super_admin_can_see_detail_of_hour_of_staff()
    {

        $this->withoutExceptionHandling();
        //setting add hour for super administrator
        $super = $this->getModel();
        $roleSuper = $this->getRoleSuper();
        $permit = $this->getPermitCreateHour();
        $roleSuper->attachPermission($permit);
        $super->attachRole($roleSuper);
        $this->actingAs($super)->post('/createNewHour', ['user_id' => $super->id, 'date' => '1983/02/01' , 'nonWork' => 1]);
        $this->actingAs($super)->post('/createNewHour', ['user_id' => $super->id, 'date' => '1983/02/02' , 'nonWork' => 1]);
        $this->actingAs($super)->post('/createNewHour', ['user_id' => $super->id, 'date' => '1983/02/03' , 'hour' => 800, 'nonWork' => 0]);
        $this->actingAs($super)->post('/createNewHour', ['user_id' => $super->id, 'date' => '1983/02/04' , 'hour' => 900, 'nonWork' => 0]);
        $this->actingAs($super)->post('/createNewHour', ['user_id' => $super->id, 'date' => '1983/02/05' , 'hour' => 1000, 'nonWork' => 0]);

        //setting add hour for administrator
        $admin = $this->getModel();
        $roleAdmin = $this->createRoleAdmin();
        $roleAdmin->attachPermission($permit);
        $admin->attachRole($roleAdmin);
        $this->actingAs($admin)->post('/createNewHour', ['user_id' => $admin->id, 'date' => '1983/02/01' , 'nonWork' => 1]);
        $this->actingAs($admin)->post('/createNewHour', ['user_id' => $admin->id, 'date' => '1983/02/02' , 'nonWork' => 1]);
        $this->actingAs($admin)->post('/createNewHour', ['user_id' => $admin->id, 'date' => '1983/02/03' , 'hour' => 700, 'nonWork' => 0]);
        $this->actingAs($admin)->post('/createNewHour', ['user_id' => $admin->id, 'date' => '1983/02/04' , 'hour' => 600, 'nonWork' => 0]);
        $this->actingAs($admin)->post('/createNewHour', ['user_id' => $admin->id, 'date' => '1983/02/05' , 'hour' => 500, 'nonWork' => 0]);

        //setting add hour for user
        $user = $this->getModel();
        $roleSuper = $this->createRoleUser();
        $roleSuper->attachPermission($permit);
        $user->attachRole($roleSuper);
        $this->actingAs($user)->post('/createNewHour', ['user_id' => $user->id, 'date' => '1983/02/01' , 'nonWork' => 1]);
        $this->actingAs($user)->post('/createNewHour', ['user_id' => $user->id, 'date' => '1983/02/02' , 'nonWork' => 1]);
        $this->actingAs($user)->post('/createNewHour', ['user_id' => $user->id, 'date' => '1983/02/03' , 'hour' => 500, 'nonWork' => 0]);
        $this->actingAs($user)->post('/createNewHour', ['user_id' => $user->id, 'date' => '1983/02/04' , 'hour' => 500, 'nonWork' => 0]);
        $this->actingAs($user)->post('/createNewHour', ['user_id' => $user->id, 'date' => '1983/02/05' , 'hour' => 2000, 'nonWork' => 0]);

        $response = $this->actingAs($super)->get('/hours-detail/' . $user->id)->assertViewIs('super.hourdetail');
        $response->assertSee(2000);
    }

    /** @test */
    public function non_superAdmin_can_not_access_to_other_staff_hour_detail()
    {

        $this->withoutExceptionHandling();
        //setting add hour for super administrator
        $super = $this->getModel();
        $roleSuper = $this->getRoleSuper();
        $permit = $this->getPermitCreateHour();
        $roleSuper->attachPermission($permit);
        $super->attachRole($roleSuper);
        $this->actingAs($super)->post('/createNewHour', ['user_id' => $super->id, 'date' => '1983/02/01' , 'nonWork' => 1]);
        $this->actingAs($super)->post('/createNewHour', ['user_id' => $super->id, 'date' => '1983/02/02' , 'nonWork' => 1]);
        $this->actingAs($super)->post('/createNewHour', ['user_id' => $super->id, 'date' => '1983/02/03' , 'hour' => 800, 'nonWork' => 0]);
        $this->actingAs($super)->post('/createNewHour', ['user_id' => $super->id, 'date' => '1983/02/04' , 'hour' => 900, 'nonWork' => 0]);
        $this->actingAs($super)->post('/createNewHour', ['user_id' => $super->id, 'date' => '1983/02/05' , 'hour' => 1000, 'nonWork' => 0]);

        //setting add hour for administrator
        $admin = $this->getModel();
        $roleAdmin = $this->createRoleAdmin();
        $roleAdmin->attachPermission($permit);
        $admin->attachRole($roleAdmin);
        $this->actingAs($admin)->post('/createNewHour', ['user_id' => $admin->id, 'date' => '1983/02/01' , 'nonWork' => 1]);
        $this->actingAs($admin)->post('/createNewHour', ['user_id' => $admin->id, 'date' => '1983/02/02' , 'nonWork' => 1]);
        $this->actingAs($admin)->post('/createNewHour', ['user_id' => $admin->id, 'date' => '1983/02/03' , 'hour' => 700, 'nonWork' => 0]);
        $this->actingAs($admin)->post('/createNewHour', ['user_id' => $admin->id, 'date' => '1983/02/04' , 'hour' => 600, 'nonWork' => 0]);
        $this->actingAs($admin)->post('/createNewHour', ['user_id' => $admin->id, 'date' => '1983/02/05' , 'hour' => 500, 'nonWork' => 0]);

        //setting add hour for user
        $user = $this->getModel();
        $roleSuper = $this->createRoleUser();
        $roleSuper->attachPermission($permit);
        $user->attachRole($roleSuper);
        $this->actingAs($user)->post('/createNewHour', ['user_id' => $user->id, 'date' => '1983/02/01' , 'nonWork' => 1]);
        $this->actingAs($user)->post('/createNewHour', ['user_id' => $user->id, 'date' => '1983/02/02' , 'nonWork' => 1]);
        $this->actingAs($user)->post('/createNewHour', ['user_id' => $user->id, 'date' => '1983/02/03' , 'hour' => 500, 'nonWork' => 0]);
        $this->actingAs($user)->post('/createNewHour', ['user_id' => $user->id, 'date' => '1983/02/04' , 'hour' => 500, 'nonWork' => 0]);
        $this->actingAs($user)->post('/createNewHour', ['user_id' => $user->id, 'date' => '1983/02/05' , 'hour' => 2000, 'nonWork' => 0]);

        $this->actingAs($user)->get('/hours-detail/' . $super->id)->assertSessionHas('hasNotPermission');
    }

    /** @test */
    public function super_admin_can_update_other_staff_hours_method_get()
    {
        $this->withoutExceptionHandling();
        //setting add hour for super administrator
        $super = $this->getModel();
        $roleSuper = $this->getRoleSuper();
        $permit = $this->getPermitCreateHour();
        $roleSuper->attachPermission($permit);
        $super->attachRole($roleSuper);
        $super->attachPermission($this->getPermitUpdate());

        //setting add hour for user
        $user = $this->getModel();
        $roleSuper = $this->createRoleUser();
        $roleSuper->attachPermission($permit);
        $user->attachRole($roleSuper);
        $this->actingAs($user)->post('/createNewHour', ['user_id' => $user->id, 'date' => '1983/02/04' , 'hour' => 2000, 'nonWork' => 0]);
        $this->actingAs($user)->post('/createNewHour', ['user_id' => $user->id, 'date' => '1983/02/05' , 'hour' => 500, 'nonWork' => 0]);
        $this->actingAs($user)->post('/createNewHour', ['user_id' => $user->id, 'date' => '1983/02/01' , 'nonWork' => 1]);
        $this->actingAs($user)->post('/createNewHour', ['user_id' => $user->id, 'date' => '1983/02/02' , 'nonWork' => 1]);
        $this->actingAs($user)->post('/createNewHour', ['user_id' => $user->id, 'date' => '1983/02/03' , 'hour' => 500, 'nonWork' => 0]);


        $this->assertCount(5, Hour::all());
        $this->assertCount(2, User::all());
        $hour = Hour::first();

        $response = $this->actingAs($super)->get('/hours-update/'.$hour->id);
        $response->assertViewIs('super.edit-staff-hour');
        $response->assertSee(2000);
    }


    /** @test */
    public function super_admin_can_delete_other_staff_hours_method_get()
    {
        $this->withoutExceptionHandling();
        //setting add hour for super administrator
        $super = $this->getModel();
        $roleSuper = $this->getRoleSuper();
        $permit = $this->getPermitCreateHour();
        $roleSuper->attachPermission($permit);
        $super->attachRole($roleSuper);
        $super->attachPermission($this->getPermitUpdate());

        //setting add hour for user
        $user = $this->getModel();
        $roleSuper = $this->createRoleUser();
        $roleSuper->attachPermission($permit);
        $user->attachRole($roleSuper);
        $this->actingAs($user)->post('/createNewHour', ['user_id' => $user->id, 'date' => '1983/02/05' , 'hour' => 2000, 'nonWork' => 0]);
        $this->actingAs($user)->post('/createNewHour', ['user_id' => $user->id, 'date' => '1983/02/04' , 'hour' => 500, 'nonWork' => 0]);
        $this->actingAs($user)->post('/createNewHour', ['user_id' => $user->id, 'date' => '1983/02/01' , 'nonWork' => 1]);
        $this->actingAs($user)->post('/createNewHour', ['user_id' => $user->id, 'date' => '1983/02/02' , 'nonWork' => 1]);
        $this->actingAs($user)->post('/createNewHour', ['user_id' => $user->id, 'date' => '1983/02/03' , 'hour' => 500, 'nonWork' => 0]);



        $this->assertCount(5, Hour::all());
        $this->assertCount(2, User::all());
        $hour = Hour::first();
        $this->actingAs($super)->get('/hours-delete/'.$hour->id);
        $this->assertCount(4, Hour::all());

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
    private function getRoleSuper()
    {
        return Role::factory()->create(['name' => 'superadministrator']);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Collection|\Illuminate\Database\Eloquent\Model|mixed
     */
    private function getPermitCreateHour()
    {
        return Permission::factory()->create(['name' => 'hour-create']);
    }

    private function getPermitUpdate()
    {
        return Permission::factory()->create(['name' => 'hour-update']);
    }

    private function getPermitReadHour()
    {
        return Permission::factory()->create(['name' => 'hour-read']);
    }

    private function getPermitCreateUser()
    {
        return Permission::factory()->create(['name' => 'users-create']);
    }

    private function createRoleUser()
    {
        return Role::factory()->create(['name' => 'user']);
    }

    private function createRoleAdmin()
    {
        return Role::factory()->create(['name' => 'administrator']);
    }

}
