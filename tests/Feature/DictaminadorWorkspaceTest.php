<?php

namespace Tests\Feature;

use App\Models\Convocatoria;
use App\Models\Plaza;
use App\Models\Postulacion;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class DictaminadorWorkspaceTest extends TestCase
{
    use RefreshDatabase;

    public function test_reviewer_can_create_update_and_delete_convocatorias_without_applicants(): void
    {
        $reviewer = User::factory()->create(['role' => User::ROLE_REVIEWER]);
        $plaza = Plaza::query()->where('codigo', 'E3817')->firstOrFail();
        $this->actingAs($reviewer);

        $this->post(route('dictaminador.convocatorias.store'), [
            ...$this->convocatoriaData(),
            'plaza_id' => $plaza->id,
        ])
            ->assertRedirect(route('dictaminador.convocatorias.index'));

        $convocatoria = Convocatoria::query()->firstOrFail();
        $this->assertSame($reviewer->id, $convocatoria->created_by);
        $this->assertSame('Abierta', $convocatoria->tipo_concurso);
        $this->assertSame('Docente', $convocatoria->tipo_personal);
        $this->assertSame($plaza->id, $convocatoria->plaza_id);
        $this->assertDatabaseHas('audit_logs', [
            'event' => 'convocatoria.created',
            'actor_id' => $reviewer->id,
        ]);

        $this->put(route('dictaminador.convocatorias.update', $convocatoria), [
            ...$this->convocatoriaData(),
            'titulo' => 'Convocatoria actualizada',
        ])->assertRedirect(route('dictaminador.convocatorias.index'));
        $this->assertSame('Convocatoria actualizada', $convocatoria->refresh()->titulo);

        $this->delete(route('dictaminador.convocatorias.destroy', $convocatoria))
            ->assertRedirect(route('dictaminador.convocatorias.index'));
        $this->assertDatabaseMissing('convocatorias', ['id' => $convocatoria->id]);
        $this->assertDatabaseHas('audit_logs', [
            'event' => 'convocatoria.updated',
            'actor_id' => $reviewer->id,
        ]);
        $this->assertDatabaseHas('audit_logs', [
            'event' => 'convocatoria.deleted',
            'actor_id' => $reviewer->id,
        ]);
    }

    public function test_convocatoria_form_and_validation_use_fixed_personnel_and_contest_options(): void
    {
        $reviewer = User::factory()->create(['role' => User::ROLE_REVIEWER]);
        $plaza = Plaza::query()->where('codigo', 'E3817')->firstOrFail();

        $this->actingAs($reviewer)
            ->get(route('dictaminador.convocatorias.create'))
            ->assertOk()
            ->assertSee('<select id="tipo_personal"', false)
            ->assertSee('<option value="Docente"', false)
            ->assertSee('<option value="No docente"', false)
            ->assertSee('<select id="tipo_concurso"', false)
            ->assertSee('<option value="Abierta"', false)
            ->assertSee('<option value="Cerrada"', false)
            ->assertSee('<option value="'.$plaza->id.'"', false);

        $this->actingAs($reviewer)
            ->post(route('dictaminador.convocatorias.store'), [
                ...$this->convocatoriaData(),
                'tipo_personal' => 'Otro',
            ])
            ->assertSessionHasErrors('tipo_personal');

        $this->assertDatabaseCount('convocatorias', 0);
    }

    public function test_convocatoria_rejects_unsupported_contest_type(): void
    {
        $reviewer = User::factory()->create(['role' => User::ROLE_REVIEWER]);

        $this->actingAs($reviewer)
            ->post(route('dictaminador.convocatorias.store'), [
                ...$this->convocatoriaData(),
                'tipo_concurso' => 'Público Abierto',
            ])
            ->assertSessionHasErrors('tipo_concurso');

        $this->assertDatabaseCount('convocatorias', 0);
    }

    public function test_non_teaching_convocatoria_cannot_be_linked_to_a_plaza(): void
    {
        $reviewer = User::factory()->create(['role' => User::ROLE_REVIEWER]);
        $plaza = Plaza::query()->where('codigo', 'E3817')->firstOrFail();

        $this->actingAs($reviewer)
            ->post(route('dictaminador.convocatorias.store'), [
                ...$this->convocatoriaData(),
                'tipo_personal' => 'No docente',
                'plaza_id' => $plaza->id,
            ])
            ->assertSessionHasErrors('plaza_id');

        $this->assertDatabaseCount('convocatorias', 0);
    }

    public function test_reviewer_dashboard_shows_live_statistics_and_expiring_convocatorias(): void
    {
        $reviewer = User::factory()->create(['role' => User::ROLE_REVIEWER]);
        $applicant = User::factory()->create();
        $expiring = $this->createConvocatoria([
            'titulo' => 'Convocatoria por vencer',
            'fecha_fin' => today()->addDays(3),
        ]);
        Postulacion::create([
            'convocatoria_id' => $expiring->id,
            'user_id' => $applicant->id,
            'estatus' => Postulacion::STATUS_PENDING,
        ]);

        $this->actingAs($reviewer)
            ->get(route('dictaminador.dashboard'))
            ->assertOk()
            ->assertSee('Convocatoria por vencer')
            ->assertSee('Postulaciones pendientes')
            ->assertSee('Convocatorias a punto de vencer')
            ->assertViewHas('convocatoriasCount', 1)
            ->assertViewHas('pendingCount', 1)
            ->assertDontSee('Usuarios en el sistema');
    }

    public function test_admin_dashboard_shows_exact_live_system_counts(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $applicant = User::factory()->create();
        User::factory()->create(['role' => User::ROLE_REVIEWER]);
        $convocatoria = $this->createConvocatoria();
        Postulacion::create([
            'convocatoria_id' => $convocatoria->id,
            'user_id' => $applicant->id,
            'estatus' => Postulacion::STATUS_PENDING,
        ]);

        $this->actingAs($admin)
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->assertViewHas('convocatoriasCount', 1)
            ->assertViewHas('usersCount', 3)
            ->assertViewHas('pendingCount', 1);
    }

    public function test_applicant_pdf_is_private_and_reviewer_decision_notifies_the_applicant(): void
    {
        Storage::fake('local');
        $applicant = User::factory()->create(['tipo_usuario' => 'aspirante_docente']);
        $reviewer = User::factory()->create(['role' => User::ROLE_REVIEWER]);
        $convocatoria = $this->createConvocatoria();

        $this->actingAs($applicant)
            ->post(route('postulaciones.store', $convocatoria), [
                'informacion' => 'Experiencia docente de cinco años.',
                'documento' => UploadedFile::fake()->create('expediente.pdf', 50, 'application/pdf'),
            ])
            ->assertRedirect();

        $postulacion = Postulacion::query()->firstOrFail();
        $this->assertDatabaseHas('postulaciones', [
            'id' => $postulacion->id,
            'estatus' => Postulacion::STATUS_PENDING,
            'user_id' => $applicant->id,
        ]);
        Storage::disk('local')->assertExists($postulacion->documento_path);
        $this->assertDatabaseHas('notifications', [
            'notifiable_id' => $reviewer->id,
            'type' => 'App\\Notifications\\NuevaPostulacion',
        ]);

        $this->actingAs($reviewer)
            ->get(route('dashboard'))
            ->assertSee('Notificaciones, 1 nuevas');

        $this->actingAs($applicant)
            ->get(route('dictaminador.postulantes.document', $postulacion))
            ->assertForbidden();
        $this->actingAs($reviewer)
            ->get(route('dictaminador.postulantes.document', $postulacion))
            ->assertOk()
            ->assertHeader('content-type', 'application/pdf');

        $this->actingAs($reviewer)
            ->patch(route('dictaminador.postulantes.review', $postulacion), [
                'estatus' => Postulacion::STATUS_APPROVED,
            ])
            ->assertRedirect(route('dictaminador.postulantes.show', $postulacion));

        $this->assertSame(Postulacion::STATUS_APPROVED, $postulacion->refresh()->estatus);
        $this->assertSame($reviewer->id, $postulacion->revisado_por);
        $this->assertDatabaseHas('notifications', [
            'notifiable_id' => $applicant->id,
            'type' => 'App\\Notifications\\PostulacionActualizada',
        ]);
        $this->assertDatabaseHas('audit_logs', [
            'event' => 'postulacion.reviewed',
            'actor_id' => $reviewer->id,
            'user_id' => $applicant->id,
        ]);

        $this->actingAs($applicant)
            ->get(route('dashboard'))
            ->assertSee('Notificaciones, 1 nuevas');

        $this->get(route('notifications.index'))
            ->assertOk()
            ->assertSee('Tu postulación a Convocatoria de prueba fue aprobada.');
    }

    public function test_convocatoria_with_postulations_cannot_be_deleted(): void
    {
        $reviewer = User::factory()->create(['role' => User::ROLE_REVIEWER]);
        $applicant = User::factory()->create();
        $convocatoria = $this->createConvocatoria();
        $postulacion = Postulacion::create([
            'convocatoria_id' => $convocatoria->id,
            'user_id' => $applicant->id,
        ]);

        $this->actingAs($reviewer)
            ->delete(route('dictaminador.convocatorias.destroy', $convocatoria))
            ->assertSessionHasErrors('convocatoria');

        $this->assertModelExists($convocatoria);
        $this->assertModelExists($postulacion);
    }

    public function test_user_cannot_apply_to_a_closed_convocatoria(): void
    {
        $applicant = User::factory()->create();
        $convocatoria = $this->createConvocatoria(['estatus' => 'Cerrada']);

        $this->actingAs($applicant)
            ->post(route('postulaciones.store', $convocatoria), [
                'informacion' => 'Información',
            ])
            ->assertSessionHasErrors('convocatoria');

        $this->assertDatabaseCount('postulaciones', 0);
        $this->assertDatabaseCount('notifications', 0);
    }

    public function test_duplicate_application_is_rejected_without_creating_a_second_record(): void
    {
        $applicant = User::factory()->create();
        $convocatoria = $this->createConvocatoria();
        Postulacion::create([
            'convocatoria_id' => $convocatoria->id,
            'user_id' => $applicant->id,
        ]);

        $this->actingAs($applicant)
            ->post(route('postulaciones.store', $convocatoria), [
                'informacion' => 'Información duplicada',
            ])
            ->assertSessionHasErrors('convocatoria');

        $this->assertDatabaseCount('postulaciones', 1);
    }

    public function test_applicant_cannot_review_or_access_another_applicants_postulation(): void
    {
        $owner = User::factory()->create();
        $otherApplicant = User::factory()->create();
        $reviewer = User::factory()->create(['role' => User::ROLE_REVIEWER]);
        $convocatoria = $this->createConvocatoria();
        $postulacion = Postulacion::create([
            'convocatoria_id' => $convocatoria->id,
            'user_id' => $owner->id,
        ]);

        $this->actingAs($otherApplicant)
            ->get(route('postulaciones.show', $postulacion))
            ->assertNotFound();

        $this->actingAs($owner)
            ->patch(route('dictaminador.postulantes.review', $postulacion), [
                'estatus' => Postulacion::STATUS_APPROVED,
            ])
            ->assertForbidden();

        $this->actingAs($reviewer)
            ->patch(route('dictaminador.postulantes.review', $postulacion), [
                'estatus' => Postulacion::STATUS_REJECTED,
            ])
            ->assertSessionHasErrors('comentarios_revision');

        $this->assertSame(Postulacion::STATUS_PENDING, $postulacion->refresh()->estatus);
    }

    public function test_only_reviewers_can_manage_convocatorias_and_review_applicants(): void
    {
        $user = User::factory()->create();
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);

        foreach ([$user, $admin] as $account) {
            $this->actingAs($account);
            $this->get(route('dictaminador.convocatorias.index'))->assertForbidden();
            $this->get(route('dictaminador.postulantes.index'))->assertForbidden();
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function convocatoriaData(): array
    {
        return [
            'titulo' => 'Convocatoria de prueba',
            'tipo_concurso' => 'Abierta',
            'tipo_personal' => 'Docente',
            'categoria_nivel' => 'Profesor Asociado',
            'plaza_disponible' => '2 plazas',
            'jornada_horario' => 'Tiempo completo',
            'salario' => '18000.00',
            'fecha_inicio' => today()->toDateString(),
            'fecha_fin' => today()->addMonth()->toDateString(),
            'estatus' => 'Activa',
            'requisitos_especificos' => 'Título profesional.',
        ];
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    private function createConvocatoria(array $overrides = []): Convocatoria
    {
        return Convocatoria::create([
            ...$this->convocatoriaData(),
            ...$overrides,
        ]);
    }
}
