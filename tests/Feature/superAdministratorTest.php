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

        $response = $this->actingAs($user)->post('createNewHour', ['user_id' => $user->id, 'date' => '1983/02/01', 'hour' => 800]);
        $response->assertSessionHas('RED');

        $role->attachPermission($permit);
        $user->attachRole($role);

        $this->actingAs($user)->post('/createNewHour', ['user_id' => $user->id, 'date' => '1983/02/01', 'hour' => 800]);

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
        $this->actingAs($user)->post('/createNewHour', ['user_id' => $user->id, 'date' => '1983/02/01' , 'hour' => 800]);

        $response = $this->actingAs($user)->post('/createNewHour', ['user_id' => $user->id, 'date' => '1983/02/01' , 'hour' => 800]);
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
        $this->actingAs($user)->post('/createNewHour', ['user_id' => $user->id, 'date' => '1983/02/01' , 'hour' => 800, 'nonWork' => false]);

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
        $this->actingAs($user)->post('/createNewHour', ['user_id' => $user->id, 'date' => '1983/02/01' , 'hour' => 800]);
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
        $this->actingAs($user)->post('/createNewHour', ['user_id' => $user->id, 'date' => '1983/02/01' , 'nonWork' => 'true']);

        $hour = Hour::first();

        $this->assertCount(1, Hour::all());
        $this->assertNull($hour->hour);
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
        $this->actingAs($user)->post('/createNewHour', ['user_id' => $user->id, 'date' => '1983/02/01' , 'nonWork' => 'true']);
        $this->actingAs($user)->post('/createNewHour', ['user_id' => $user->id, 'date' => '1983/02/02' , 'nonWork' => 'true']);
        $this->actingAs($user)->post('/createNewHour', ['user_id' => $user->id, 'date' => '1983/02/03' , 'hour' => 800]);
        $this->actingAs($user)->post('/createNewHour', ['user_id' => $user->id, 'date' => '1983/02/04' , 'hour' => 900]);
        $this->actingAs($user)->post('/createNewHour', ['user_id' => $user->id, 'date' => '1983/02/05' , 'hour' => 1000]);


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

}
