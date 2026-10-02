<?php

namespace Tests\Feature;

use App\Models\Area;
use App\Models\Responsable;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ResponsableValidationTest extends TestCase
{
    use RefreshDatabase;

    public function test_main_responsible_person_fields_are_required(): void
    {
        $role = Role::create(['name' => 'admin']);
        $user = User::factory()->create(['email_verified_at' => now()]);
        $user->assignRole($role);

        $response = $this->actingAs($user)->post(route('responsables.store'), []);

        $response->assertSessionHasErrors([
            'nombre_completo', 'cargo_rol', 'institucion',
        ]);
    }

    public function test_responsible_person_can_be_created_without_contact_details(): void
    {
        $role = Role::create(['name' => 'admin']);
        $user = User::factory()->create(['email_verified_at' => now()]);
        $user->assignRole($role);

        $this->actingAs($user)->post(route('responsables.store'), [
            'nombre_completo' => 'Responsable sin contacto',
            'cargo_rol' => 'Técnico',
            'institucion' => 'Institución de prueba',
            'telefono' => '',
            'email' => '',
        ])->assertSessionHasNoErrors()->assertRedirect(route('responsables.index'));

        $this->assertDatabaseHas('responsables', [
            'nombre_completo' => 'Responsable sin contacto',
            'telefono' => null,
            'email' => null,
        ]);
    }

    public function test_email_is_required_when_creating_an_access_account(): void
    {
        $role = Role::create(['name' => 'admin']);
        $user = User::factory()->create(['email_verified_at' => now()]);
        $user->assignRole($role);

        $this->actingAs($user)->post(route('responsables.store'), [
            'nombre_completo' => 'Responsable con cuenta',
            'cargo_rol' => 'Técnico',
            'institucion' => 'Institución de prueba',
            'crear_usuario' => '1',
            'email' => '',
        ])->assertSessionHasErrors(['email'])->assertSessionDoesntHaveErrors(['telefono']);
    }

    public function test_responsible_person_can_be_created_without_an_area(): void
    {
        $role = Role::create(['name' => 'admin']);
        $user = User::factory()->create(['email_verified_at' => now()]);
        $user->assignRole($role);

        $this->actingAs($user)->post(route('responsables.store'), [
            'nombre_completo' => 'Responsable sin área',
            'cargo_rol' => 'Técnico',
            'institucion' => '__nueva__',
            'institucion_nueva' => 'Institución de prueba',
            'telefono' => '60000001',
            'email' => 'sin-area@example.test',
        ])->assertRedirect(route('responsables.index'));

        $responsable = Responsable::where('email', 'sin-area@example.test')->firstOrFail();
        $this->assertNull($responsable->area);
        $this->assertCount(0, $responsable->areas);
    }

    public function test_new_area_and_institution_can_be_created_from_responsible_person_form(): void
    {
        $role = Role::create(['name' => 'admin']);
        $user = User::factory()->create(['email_verified_at' => now()]);
        $user->assignRole($role);
        $areaUno = Area::create(['nombre' => 'Salud']);
        $areaDos = Area::create(['nombre' => 'Educación']);

        $response = $this->actingAs($user)->post(route('responsables.store'), [
            'nombre_completo' => 'Responsable de prueba',
            'cargo_rol' => 'Coordinador',
            'area_ids' => [$areaUno->id, $areaDos->id],
            'area_nueva' => 'Unidad de Monitoreo',
            'institucion' => '__nueva__',
            'institucion_nueva' => 'Gobernación de Tarija',
            'telefono' => '60000000',
            'email' => 'responsable@tarija.gob.bo',
        ]);

        $response->assertRedirect(route('responsables.index'));
        $this->assertDatabaseHas('responsables', [
            'email' => 'responsable@tarija.gob.bo',
            'institucion' => 'Gobernación de Tarija',
        ]);
        $responsable = Responsable::where('email', 'responsable@tarija.gob.bo')->firstOrFail();
        $this->assertCount(3, $responsable->areas);
        $this->assertEqualsCanonicalizing(
            ['Salud', 'Educación', 'Unidad de Monitoreo'],
            $responsable->areas->pluck('nombre')->all(),
        );
    }
}
