<?php

namespace Tests\Feature;

use App\Models\User;
use App\Notifications\UserCredentialsNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class UserManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_authorized_user_can_create_account_and_send_credentials(): void
    {
        Notification::fake();
        $permission = Permission::create(['name' => 'users.create']);
        $role = Role::create(['name' => 'admin']);
        $role->givePermissionTo($permission);
        $admin = User::factory()->create(['email_verified_at' => now()]);
        $admin->assignRole($role);
        Role::create(['name' => 'tecnico']);

        $response = $this->actingAs($admin)->post(route('admin.users.store'), [
            'name' => 'Usuario Institucional',
            'email' => 'usuario@institucion.test',
            'role' => 'tecnico',
            'is_active' => '1',
        ]);

        $response->assertRedirect(route('admin.users.index'));
        $user = User::where('email', 'usuario@institucion.test')->firstOrFail();
        $this->assertTrue($user->is_active);
        $this->assertTrue($user->must_change_password);
        $this->assertTrue($user->hasRole('tecnico'));
        Notification::assertSentTo($user, UserCredentialsNotification::class);
    }

    public function test_user_without_permission_cannot_open_user_administration(): void
    {
        Permission::create(['name' => 'users.view']);
        $user = User::factory()->create(['email_verified_at' => now()]);

        $this->actingAs($user)->get(route('admin.users.index'))->assertForbidden();
    }
}
