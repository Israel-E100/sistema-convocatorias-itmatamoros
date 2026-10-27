<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AuthenticationAndRolesTest extends TestCase
{
    use RefreshDatabase;

    public function test_registration_persists_profile_and_assigns_only_the_user_role(): void
    {
        $response = $this->post('/registro', [
            'nombre' => 'Ana',
            'apellido_paterno' => 'López',
            'apellido_materno' => 'Ruiz',
            'curp' => 'abcd001231hdfxxx01',
            'rfc' => 'abc001231xyz',
            'tipo_usuario' => 'aspirante_docente',
            'plaza_codigo' => 'E3807',
            'email' => 'ANA@example.com',
            'password' => 'password-seguro',
            'password_confirmation' => 'password-seguro',
            'role' => User::ROLE_ADMIN,
        ]);

        $response->assertRedirect(route('dashboard'));

        $user = User::query()->where('email', 'ana@example.com')->firstOrFail();
        $this->assertSame('Ana López Ruiz', $user->name);
        $this->assertSame('ABCD001231HDFXXX01', $user->curp);
        $this->assertSame('ABC001231XYZ', $user->rfc);
        $this->assertSame('aspirante_docente', $user->tipo_usuario);
        $this->assertSame(User::ROLE_USER, $user->role);
        $this->assertDatabaseHas('plazas', ['codigo' => 'E3807', 'user_id' => $user->id]);
        $this->assertTrue(Hash::check('password-seguro', $user->password));
        $this->assertAuthenticatedAs($user);
        $this->assertDatabaseHas('audit_logs', [
            'event' => 'user.created',
            'user_id' => $user->id,
        ]);
    }

    public function test_registration_rejects_duplicate_identity_and_invalid_applicant_type(): void
    {
        User::factory()->create([
            'curp' => 'ABCD001231HDFXXX01',
            'rfc' => 'ABC001231XYZ',
        ]);

        $this->from('/registro')->post('/registro', [
            'nombre' => 'Ana',
            'apellido_paterno' => 'López',
            'apellido_materno' => 'Ruiz',
            'curp' => 'abcd001231hdfxxx01',
            'rfc' => 'abc001231xyz',
            'tipo_usuario' => 'admin',
            'email' => 'ana@example.com',
            'password' => 'password-seguro',
            'password_confirmation' => 'password-seguro',
        ])->assertRedirect('/registro')->assertSessionHasErrors([
            'curp',
            'rfc',
            'tipo_usuario',
        ]);

        $this->assertDatabaseCount('users', 1);
    }

    public function test_registration_requires_a_current_position_for_teacher_profiles(): void
    {
        $this->from('/registro')->post('/registro', [
            'nombre' => 'Ana',
            'apellido_paterno' => 'López',
            'apellido_materno' => 'Ruiz',
            'curp' => 'abcd001231hdfxxx01',
            'rfc' => 'abc001231xyz',
            'tipo_usuario' => 'aspirante_docente',
            'email' => 'ana@example.com',
            'password' => 'password-seguro',
            'password_confirmation' => 'password-seguro',
        ])->assertRedirect('/registro')->assertSessionHasErrors('plaza_codigo');

        $this->assertDatabaseMissing('users', ['email' => 'ana@example.com']);
    }

    public function test_successful_login_writes_a_timestamped_audit_entry(): void
    {
        $user = User::factory()->create([
            'email' => 'ana@example.com',
            'password' => 'password-seguro',
        ]);
        $this->freezeTime();

        $this->post('/login', [
            'email' => 'ANA@example.com',
            'password' => 'password-seguro',
        ])->assertRedirect(route('dashboard'));

        $this->assertAuthenticatedAs($user);
        $log = AuditLog::query()->where('event', 'auth.login')->firstOrFail();
        $this->assertSame($user->id, $log->user_id);
        $this->assertSame($user->id, $log->actor_id);
        $this->assertSame(now()->toDateTimeString(), $log->created_at->toDateTimeString());
    }

    public function test_invalid_login_does_not_create_a_successful_login_record(): void
    {
        User::factory()->create(['email' => 'ana@example.com']);

        $this->from('/login')->post('/login', [
            'email' => 'ana@example.com',
            'password' => 'incorrecta',
        ])->assertRedirect('/login')->assertSessionHasErrors('email');

        $this->assertDatabaseMissing('audit_logs', ['event' => 'auth.login']);
    }

    public function test_only_admin_can_access_account_and_audit_management(): void
    {
        foreach ([User::ROLE_USER, User::ROLE_REVIEWER] as $role) {
            $this->actingAs(User::factory()->create(['role' => $role]));

            $this->get(route('admin.users.index'))->assertForbidden();
            $this->get(route('admin.audit-logs.index'))->assertForbidden();
        }

        $admin = User::factory()->create([
            'role' => User::ROLE_ADMIN,
            'nombre' => 'Ana',
            'apellido_paterno' => 'López',
            'apellido_materno' => 'Ruiz',
        ]);
        $this->actingAs($admin);

        $this->get(route('admin.users.index'))->assertOk();
        $this->get(route('admin.audit-logs.index'))->assertOk();
    }

    public function test_admin_can_create_update_and_delete_accounts_with_audit_history(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $this->actingAs($admin);

        $this->post(route('admin.users.store'), [
            'nombre' => 'Carlos',
            'apellido_paterno' => 'García',
            'apellido_materno' => 'Pérez',
            'email' => 'carlos@example.com',
            'role' => User::ROLE_REVIEWER,
            'password' => 'password-seguro',
            'password_confirmation' => 'password-seguro',
        ])->assertRedirect(route('admin.users.index'));

        $account = User::query()->where('email', 'carlos@example.com')->firstOrFail();

        $this->put(route('admin.users.update', $account), [
            'nombre' => 'Carlos',
            'apellido_paterno' => 'Pérez',
            'apellido_materno' => '',
            'email' => 'carlos.perez@example.com',
            'role' => User::ROLE_USER,
            'tipo_usuario' => 'aspirante_docente',
            'plaza_codigo' => 'E3807',
            'password' => '',
            'password_confirmation' => '',
        ])->assertRedirect(route('admin.users.index'));

        $account->refresh();
        $this->assertSame('Carlos Pérez', $account->name);
        $this->assertSame(User::ROLE_USER, $account->role);

        $this->delete(route('admin.users.destroy', $account))
            ->assertRedirect(route('admin.users.index'));

        $this->assertSoftDeleted($account);
        $created = AuditLog::query()->where('event', 'user.created')->firstOrFail();
        $updated = AuditLog::query()->where('event', 'user.updated')->firstOrFail();
        $this->assertSame($account->id, data_get($created->details, 'user.id'));
        $this->assertSame($account->id, data_get($updated->details, 'user.id'));
        $this->assertSame($account->id, $created->user_id);
        $this->assertSame($account->id, $updated->user_id);

        $deletion = AuditLog::query()->where('event', 'user.deleted')->firstOrFail();
        $this->assertSame('Carlos Pérez', data_get($deletion->details, 'user.name'));
        $this->assertArrayNotHasKey('password', $deletion->details);
    }

    public function test_admin_user_form_requests_given_name_only_once(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);

        $this->actingAs($admin)
            ->get(route('admin.users.create'))
            ->assertOk()
            ->assertSee('Nombre(s)')
            ->assertDontSee('Nombre completo');
    }

    public function test_admin_creates_account_name_from_given_and_surnames(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);

        $response = $this->actingAs($admin)->post(route('admin.users.store'), [
            'nombre' => 'Luis Alberto',
            'apellido_paterno' => 'García',
            'apellido_materno' => 'Pérez',
            'email' => 'luis@example.com',
            'role' => User::ROLE_REVIEWER,
            'password' => 'password-seguro',
            'password_confirmation' => 'password-seguro',
        ]);

        $response->assertRedirect(route('admin.users.index'));
        $this->assertDatabaseHas('users', [
            'email' => 'luis@example.com',
            'name' => 'Luis Alberto García Pérez',
            'nombre' => 'Luis Alberto',
        ]);
    }

    public function test_admin_cannot_remove_or_demote_the_last_admin(): void
    {
        $admin = User::factory()->create([
            'role' => User::ROLE_ADMIN,
            'nombre' => 'Ana',
            'apellido_paterno' => 'López',
            'apellido_materno' => 'Ruiz',
        ]);
        $this->actingAs($admin);

        $this->put(route('admin.users.update', $admin), [
            'nombre' => $admin->nombre,
            'apellido_paterno' => $admin->apellido_paterno,
            'apellido_materno' => $admin->apellido_materno,
            'email' => $admin->email,
            'role' => User::ROLE_USER,
            'tipo_usuario' => 'aspirante_docente',
            'plaza_codigo' => 'E3807',
            'password' => '',
            'password_confirmation' => '',
        ])->assertSessionHasErrors('role');

        $this->delete(route('admin.users.destroy', $admin))->assertNotFound();

        $this->assertDatabaseHas('users', ['id' => $admin->id, 'role' => User::ROLE_ADMIN]);
    }
}
