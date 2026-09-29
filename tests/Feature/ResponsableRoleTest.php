<?php

namespace Tests\Feature;

use App\Models\AccionCompromiso;
use App\Models\AccionEjecucion;
use App\Models\Area;
use App\Models\Necesidad;
use App\Models\Responsable;
use App\Models\User;
use App\Notifications\UserCredentialsNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ResponsableRoleTest extends TestCase
{
    use RefreshDatabase;

    public function test_responsable_can_be_created_with_linked_user_account(): void
    {
        Notification::fake();
        Permission::create(['name' => 'responsables.create']);
        $admin = Role::create(['name' => 'admin']);
        $admin->givePermissionTo('responsables.create');
        $user = User::factory()->create(['email_verified_at' => now()]);
        $user->assignRole($admin);
        $area = Area::create(['nombre' => 'Salud']);

        $this->actingAs($user)->post(route('responsables.store'), [
            'nombre_completo' => 'María Responsable', 'cargo_rol' => 'Coordinadora',
            'area_ids' => [$area->id], 'institucion' => '__nueva__',
            'institucion_nueva' => 'Gobernación', 'telefono' => '60000000',
            'email' => 'responsable@example.com', 'crear_usuario' => '1',
        ])->assertRedirect(route('responsables.index'));

        $cuenta = User::where('email', 'responsable@example.com')->firstOrFail();
        $this->assertTrue($cuenta->hasRole('responsable'));
        $this->assertSame($cuenta->id, Responsable::where('email', 'responsable@example.com')->value('user_id'));
        Notification::assertSentTo($cuenta, UserCredentialsNotification::class);
    }

    public function test_responsable_only_sees_assigned_actions(): void
    {
        $role = Role::where('name', 'responsable')->firstOrFail();
        $user = User::factory()->create(['email_verified_at' => now()]);
        $user->assignRole($role);
        $responsable = Responsable::create(['user_id' => $user->id, 'nombre_completo' => 'Responsable', 'email' => 'r@example.com']);
        $necesidad = Necesidad::create(['titulo' => 'Necesidad']);
        $compromiso = AccionCompromiso::create(['necesidad_id' => $necesidad->id, 'titulo' => 'Compromiso']);
        $propia = AccionEjecucion::create(['accion_compromiso_id' => $compromiso->id, 'titulo' => 'Propia']);
        $propia->responsables()->attach($responsable);
        AccionEjecucion::withoutGlobalScopes()->create(['accion_compromiso_id' => $compromiso->id, 'titulo' => 'Ajena']);

        $this->actingAs($user);
        $this->assertSame(['Propia'], AccionEjecucion::pluck('titulo')->all());
    }
}
