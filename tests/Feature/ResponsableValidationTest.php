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
            'nombre_completo', 'cargo_rol', 'area_ids', 'institucion', 'telefono', 'email',
        ]);
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
