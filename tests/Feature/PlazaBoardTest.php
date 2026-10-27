<?php

namespace Tests\Feature;

use App\Models\Convocatoria;
use App\Models\Plaza;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PlazaBoardTest extends TestCase
{
    use RefreshDatabase;

    public function test_only_users_and_reviewers_can_view_the_plaza_board(): void
    {
        $this->get(route('plazas.index'))->assertRedirect(route('login'));

        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $this->actingAs($admin)->get(route('plazas.index'))->assertForbidden();

        $applicant = User::factory()->create([
            'role' => User::ROLE_USER,
            'tipo_usuario' => 'aspirante_docente',
        ]);
        $this->actingAs($applicant)
            ->get(route('plazas.index'))
            ->assertOk()
            ->assertSee('Tablero de plazas')
            ->assertSee('Tiempo completo')
            ->assertSee('3/4 tiempo')
            ->assertSee('1/2 Tiempo')
            ->assertSee('E3817');

        $reviewer = User::factory()->create(['role' => User::ROLE_REVIEWER]);
        $this->actingAs($reviewer)
            ->get(route('plazas.index'))
            ->assertOk()
            ->assertSee('Tablero de plazas')
            ->assertDontSee('Solicitudes de ascenso');
    }

    public function test_plaza_codes_are_displayed_in_the_requested_row_order(): void
    {
        $applicant = User::factory()->create([
            'role' => User::ROLE_USER,
            'tipo_usuario' => 'aspirante_docente',
        ]);
        $response = $this->actingAs($applicant)->get(route('plazas.index'));

        $response->assertOk()->assertSeeInOrder([
            'E3817', 'E3717', 'E3617',
            'E3815', 'E3715', 'E3615',
            'E3813', 'E3713', 'E3613',
            'E3811', 'E3711', 'E3611',
            'E3809', 'E3709', 'E3609',
            'E3807', 'E3707', 'E3607',
        ]);
    }

    public function test_board_state_reports_only_available_and_occupied_plazas(): void
    {
        $applicant = User::factory()->create([
            'role' => User::ROLE_USER,
            'tipo_usuario' => 'aspirante_docente',
        ]);
        Plaza::query()->where('codigo', 'E3807')->update(['user_id' => $applicant->id]);

        $this->actingAs($applicant)
            ->getJson(route('plazas.live-state'))
            ->assertOk()
            ->assertJsonPath('plazas.0.estado', Plaza::STATUS_AVAILABLE)
            ->assertJsonFragment([
                'codigo' => 'E3807',
                'estado' => Plaza::STATUS_OCCUPIED,
                'etiqueta' => 'Ocupada',
                'ocupante' => $applicant->name,
            ])
            ->assertJsonMissingPath('plazas.0.solicitud');
    }

    public function test_available_plaza_links_to_its_active_convocatoria_or_shows_the_upcoming_notice(): void
    {
        $applicant = User::factory()->create([
            'role' => User::ROLE_USER,
            'tipo_usuario' => 'aspirante_docente',
        ]);
        $plaza = Plaza::query()->where('codigo', 'E3817')->firstOrFail();
        $convocatoria = $this->createConvocatoria(['plaza_id' => $plaza->id]);

        $this->actingAs($applicant)
            ->get(route('plazas.index'))
            ->assertOk()
            ->assertSee('data-convocatoria-url="'.route('convocatorias.show', $convocatoria).'"', false)
            ->assertSee('data-plaza-notice', false)
            ->assertSee('Próximas convocatorias');

        $this->getJson(route('plazas.live-state'))
            ->assertOk()
            ->assertJsonFragment([
                'codigo' => 'E3817',
                'convocatoria_url' => route('convocatorias.show', $convocatoria),
            ]);
    }

    public function test_available_plaza_without_an_associated_call_keeps_the_upcoming_notice_path(): void
    {
        $applicant = User::factory()->create([
            'role' => User::ROLE_USER,
            'tipo_usuario' => 'aspirante_docente',
        ]);
        $plaza = Plaza::query()->where('codigo', 'E3817')->firstOrFail();

        $this->actingAs($applicant)
            ->get(route('plazas.index'))
            ->assertOk()
            ->assertSee('data-convocatoria-url=""', false)
            ->assertSee('data-codigo="'.$plaza->codigo.'"', false)
            ->assertSee('data-plaza-notice', false)
            ->assertSee('Próximas convocatorias');
    }

    public function test_teacher_can_assign_an_available_current_position_only_once(): void
    {
        $applicant = User::factory()->create([
            'role' => User::ROLE_USER,
            'tipo_usuario' => 'aspirante_docente',
        ]);

        $this->actingAs($applicant)
            ->post(route('plazas.assign-current'), ['plaza_codigo' => 'E3807'])
            ->assertRedirect(route('plazas.index'));

        $this->assertDatabaseHas('plazas', ['codigo' => 'E3807', 'user_id' => $applicant->id]);

        $this->from(route('plazas.index'))
            ->post(route('plazas.assign-current'), ['plaza_codigo' => 'E3809'])
            ->assertRedirect(route('plazas.index'))
            ->assertSessionHasErrors('plaza_codigo');

        $this->assertDatabaseMissing('plazas', ['codigo' => 'E3809', 'user_id' => $applicant->id]);
    }

    public function test_legacy_teacher_can_assign_a_key_from_the_board(): void
    {
        $applicant = User::factory()->create([
            'role' => User::ROLE_USER,
            'tipo_usuario' => 'aspirante_docente',
        ]);

        $this->actingAs($applicant)
            ->get(route('plazas.index'))
            ->assertOk()
            ->assertSee('Asigna tu clave de puesto actual');

        $this->post(route('plazas.assign-current'), ['plaza_codigo' => 'E3707'])
            ->assertRedirect(route('plazas.index'));

        $this->assertDatabaseHas('plazas', ['codigo' => 'E3707', 'user_id' => $applicant->id]);
        $this->assertDatabaseHas('audit_logs', ['event' => 'plaza.assigned']);
    }

    public function test_teacher_cannot_assign_an_occupied_key_or_assign_a_key_as_non_teacher(): void
    {
        $currentHolder = User::factory()->create([
            'role' => User::ROLE_USER,
            'tipo_usuario' => 'aspirante_docente',
        ]);
        $otherApplicant = User::factory()->create([
            'role' => User::ROLE_USER,
            'tipo_usuario' => 'aspirante_docente',
        ]);
        $nonTeacher = User::factory()->create([
            'role' => User::ROLE_USER,
            'tipo_usuario' => 'aspirante_no_docente',
        ]);
        Plaza::query()->where('codigo', 'E3807')->update(['user_id' => $currentHolder->id]);

        $this->actingAs($otherApplicant)
            ->from(route('plazas.index'))
            ->post(route('plazas.assign-current'), ['plaza_codigo' => 'E3807'])
            ->assertRedirect(route('plazas.index'))
            ->assertSessionHasErrors('plaza_codigo');

        $this->actingAs($nonTeacher)
            ->from(route('plazas.index'))
            ->post(route('plazas.assign-current'), ['plaza_codigo' => 'E3809'])
            ->assertRedirect(route('plazas.index'))
            ->assertSessionHasErrors('plaza_codigo');

        $this->assertDatabaseHas('plazas', ['codigo' => 'E3807', 'user_id' => $currentHolder->id]);
        $this->assertDatabaseMissing('plazas', ['codigo' => 'E3809', 'user_id' => $nonTeacher->id]);
    }

    public function test_owner_can_offer_a_plaza_and_only_one_user_can_reserve_it(): void
    {
        $owner = User::factory()->create([
            'role' => User::ROLE_USER,
            'tipo_usuario' => 'aspirante_docente',
        ]);
        $claimant = User::factory()->create([
            'role' => User::ROLE_USER,
            'tipo_usuario' => 'aspirante_docente',
        ]);
        $anotherClaimant = User::factory()->create([
            'role' => User::ROLE_USER,
            'tipo_usuario' => 'aspirante_docente',
        ]);
        $plaza = Plaza::query()->where('codigo', 'E3807')->firstOrFail();
        $plaza->user_id = $owner->id;
        $plaza->save();
        $convocatoria = $this->createConvocatoria(['plaza_id' => $plaza->id]);

        $this->actingAs($owner)
            ->post(route('plazas.promotion-offer'))
            ->assertRedirect(route('plazas.index'));

        $this->assertDatabaseHas('plazas', [
            'id' => $plaza->id,
            'ofrecida_para_ascenso' => true,
            'reservada_por' => null,
        ]);
        $this->actingAs($owner)
            ->getJson(route('plazas.live-state'))
            ->assertOk()
            ->assertJsonFragment([
                'codigo' => 'E3807',
                'estado' => Plaza::STATUS_PROMOTION_PENDING,
                'etiqueta' => 'Disponible por ascenso',
                'es_mi_plaza' => true,
            ]);

        $this->actingAs($claimant)
            ->post(route('plazas.claim', $plaza))
            ->assertRedirect(route('convocatorias.show', $convocatoria));

        $this->assertDatabaseHas('plazas', [
            'id' => $plaza->id,
            'user_id' => $owner->id,
            'ofrecida_para_ascenso' => true,
            'reservada_por' => $claimant->id,
        ]);
        $this->assertDatabaseHas('audit_logs', ['event' => 'plaza.promotion_reserved']);

        $this->actingAs($anotherClaimant)
            ->from(route('plazas.index'))
            ->post(route('plazas.claim', $plaza))
            ->assertRedirect(route('plazas.index'))
            ->assertSessionHasErrors('plaza');

        $this->actingAs($anotherClaimant)
            ->from(route('convocatorias.show', $convocatoria))
            ->post(route('postulaciones.store', $convocatoria))
            ->assertRedirect(route('convocatorias.show', $convocatoria))
            ->assertSessionHasErrors('convocatoria');

        $this->assertDatabaseCount('postulaciones', 0);
        $this->assertDatabaseHas('plazas', [
            'id' => $plaza->id,
            'reservada_por' => $claimant->id,
        ]);

        $this->actingAs($claimant)
            ->post(route('postulaciones.store', $convocatoria))
            ->assertRedirect();

        $this->assertDatabaseHas('postulaciones', [
            'convocatoria_id' => $convocatoria->id,
            'user_id' => $claimant->id,
        ]);

        $this->actingAs($owner)
            ->getJson(route('plazas.live-state'))
            ->assertOk()
            ->assertJsonFragment([
                'codigo' => 'E3807',
                'estado' => Plaza::STATUS_RESERVED,
                'etiqueta' => 'Interés reservado',
                'ocupante' => $owner->name,
                'es_mi_plaza' => true,
            ]);
    }

    public function test_claiming_an_offered_plaza_falls_back_to_the_latest_active_teacher_call(): void
    {
        $owner = User::factory()->create([
            'role' => User::ROLE_USER,
            'tipo_usuario' => 'aspirante_docente',
        ]);
        $claimant = User::factory()->create([
            'role' => User::ROLE_USER,
            'tipo_usuario' => 'aspirante_docente',
        ]);
        $plaza = Plaza::query()->where('codigo', 'E3807')->firstOrFail();
        $plaza->user_id = $owner->id;
        $plaza->ofrecida_para_ascenso = true;
        $plaza->save();
        $fallback = $this->createConvocatoria(['titulo' => 'Convocatoria por defecto']);

        $this->actingAs($claimant)
            ->post(route('plazas.claim', $plaza))
            ->assertRedirect(route('convocatorias.show', $fallback));

        $this->assertDatabaseHas('plazas', [
            'id' => $plaza->id,
            'reservada_por' => $claimant->id,
        ]);
    }

    public function test_offered_plaza_remains_unreserved_when_no_teacher_call_is_open(): void
    {
        $owner = User::factory()->create([
            'role' => User::ROLE_USER,
            'tipo_usuario' => 'aspirante_docente',
        ]);
        $claimant = User::factory()->create([
            'role' => User::ROLE_USER,
            'tipo_usuario' => 'aspirante_docente',
        ]);
        $plaza = Plaza::query()->where('codigo', 'E3807')->firstOrFail();
        $plaza->user_id = $owner->id;
        $plaza->ofrecida_para_ascenso = true;
        $plaza->save();
        $this->createConvocatoria([
            'estatus' => 'Cerrada',
            'fecha_fin' => today()->subDay(),
        ]);

        $this->actingAs($claimant)
            ->from(route('plazas.index'))
            ->post(route('plazas.claim', $plaza))
            ->assertRedirect(route('plazas.index'))
            ->assertSessionHasErrors('plaza');

        $this->assertDatabaseHas('plazas', [
            'id' => $plaza->id,
            'ofrecida_para_ascenso' => true,
            'reservada_por' => null,
        ]);
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    private function createConvocatoria(array $overrides = []): Convocatoria
    {
        return Convocatoria::create([
            'titulo' => 'Convocatoria de prueba',
            'tipo_concurso' => 'Abierta',
            'tipo_personal' => 'Docente',
            'categoria_nivel' => 'Profesor Asociado',
            'plaza_disponible' => '1 plaza',
            'jornada_horario' => 'Tiempo completo',
            'fecha_inicio' => today(),
            'fecha_fin' => today()->addMonth(),
            'estatus' => 'Activa',
            ...$overrides,
        ]);
    }
}
