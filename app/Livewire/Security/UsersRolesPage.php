<?php

namespace App\Livewire\Security;

use App\Livewire\Security\Concerns\LogsSecurityAudit;
use App\Models\User;
use App\NotificationsV2\Application\Services\LegacyUserAccountStatusNotifier;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Password;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;
use Spatie\Permission\Models\Role;
use Throwable;

#[Layout('layouts.app')]
class UsersRolesPage extends Component
{
    use LogsSecurityAudit;
    use WithPagination;

    #[Url(as: 'q', except: '')]
    public string $q = '';

    public array $selectedRoles = [];

    public function updatingQ(): void
    {
        $this->resetPage();
    }

    public function saveUserRoles(int $userId): void
    {
        Gate::authorize('navbar.seguridad.editable');

        $user = User::query()->with('roles')->findOrFail($userId);
        $roles = array_values(array_unique($this->selectedRoles[$userId] ?? []));

        $this->validate([
            "selectedRoles.{$userId}" => ['nullable', 'array'],
            "selectedRoles.{$userId}.*" => ['string', 'exists:roles,name'],
        ]);

        $beforeRoles = $user->roles()->pluck('name')->sort()->values()->all();

        if ($user->id === Auth::id() && ! in_array('SuperAdmin', $roles, true) && $user->hasRole('SuperAdmin')) {
            session()->flash('error', 'No puedes quitarte a ti mismo el rol SuperAdmin.');

            return;
        }

        $superAdminRole = Role::query()->where('name', 'SuperAdmin')->first();
        if ($superAdminRole) {
            $currentSuperAdmins = User::role('SuperAdmin')->count();
            $removingLastSuperAdmin = $user->hasRole('SuperAdmin')
                && ! in_array('SuperAdmin', $roles, true)
                && $currentSuperAdmins <= 1;

            if ($removingLastSuperAdmin) {
                session()->flash('error', 'No se puede dejar el sistema sin al menos un SuperAdmin.');

                return;
            }
        }

        $user->syncRoles($roles);
        $afterRoles = $user->roles()->pluck('name')->sort()->values()->all();

        $this->logSecurityAudit(
            action: 'user.roles.updated',
            entityType: 'user',
            entityId: (string) $user->id,
            before: ['roles' => $beforeRoles],
            after: ['roles' => $afterRoles, 'email' => $user->email],
        );

        session()->flash('ok', 'Roles de usuario actualizados correctamente.');
    }

    public function approveUser(int $userId): void
    {
        Gate::authorize('navbar.seguridad.editable');

        $user = User::query()->findOrFail($userId);

        if ($user->id === Auth::id()) {
            session()->flash('error', 'No puedes cambiar tu propio estado de aprobacion.');

            return;
        }

        $before = [
            'approved_at' => optional($user->approved_at)?->toDateTimeString(),
            'approved_by' => $user->approved_by,
            'rejected_at' => optional($user->rejected_at)?->toDateTimeString(),
            'rejected_by' => $user->rejected_by,
            'rejection_note' => $user->rejection_note,
        ];

        $user->approved_at = now();
        $user->approved_by = Auth::id();
        $user->approval_note = 'Aprobado desde Seguridad';
        $user->rejected_at = null;
        $user->rejected_by = null;
        $user->rejection_note = null;
        $user->save();

        $this->sendStatusEmail($user, 'approved');

        $this->logSecurityAudit(
            action: 'user.approved',
            entityType: 'user',
            entityId: (string) $user->id,
            before: $before,
            after: [
                'approved_at' => optional($user->approved_at)?->toDateTimeString(),
                'approved_by' => $user->approved_by,
                'email' => $user->email,
            ],
        );

        session()->flash('ok', 'Usuario aprobado correctamente.');
    }

    public function rejectUser(int $userId): void
    {
        Gate::authorize('navbar.seguridad.editable');

        $user = User::query()->findOrFail($userId);

        if ($user->id === Auth::id()) {
            session()->flash('error', 'No puedes cambiar tu propio estado de aprobacion.');

            return;
        }

        $before = [
            'approved_at' => optional($user->approved_at)?->toDateTimeString(),
            'approved_by' => $user->approved_by,
            'rejected_at' => optional($user->rejected_at)?->toDateTimeString(),
            'rejected_by' => $user->rejected_by,
            'rejection_note' => $user->rejection_note,
        ];

        $user->approved_at = null;
        $user->approved_by = null;
        $user->approval_note = null;
        $user->rejected_at = now();
        $user->rejected_by = Auth::id();
        $user->rejection_note = 'Rechazado desde Seguridad';
        $user->save();

        $this->sendStatusEmail($user, 'rejected');

        $this->logSecurityAudit(
            action: 'user.rejected',
            entityType: 'user',
            entityId: (string) $user->id,
            before: $before,
            after: [
                'rejected_at' => optional($user->rejected_at)?->toDateTimeString(),
                'rejected_by' => $user->rejected_by,
                'rejection_note' => $user->rejection_note,
                'email' => $user->email,
            ],
        );

        session()->flash('ok', 'Usuario rechazado correctamente.');
    }

    public function sendResetPasswordLink(int $userId): void
    {
        Gate::authorize('navbar.seguridad.editable');

        $user = User::query()->findOrFail($userId);

        if ($user->isSuspended()) {
            session()->flash('error', 'No se puede enviar reset a un usuario suspendido.');

            return;
        }

        $status = Password::sendResetLink(['email' => $user->email]);

        if ($status !== Password::RESET_LINK_SENT) {
            session()->flash('error', __($status));

            return;
        }

        $this->logSecurityAudit(
            action: 'user.reset_password_link.sent',
            entityType: 'user',
            entityId: (string) $user->id,
            before: null,
            after: ['email' => $user->email],
        );

        session()->flash('ok', 'Correo de recuperacion enviado correctamente.');
    }

    public function setUserSuspension(int $userId, bool $suspend): void
    {
        Gate::authorize('navbar.seguridad.editable');

        $user = User::query()->findOrFail($userId);

        if ($user->id === Auth::id() && $suspend) {
            session()->flash('error', 'No puedes suspender tu propio usuario.');

            return;
        }

        $before = [
            'suspended_at' => optional($user->suspended_at)?->toDateTimeString(),
            'suspension_reason' => $user->suspension_reason,
        ];

        $user->suspended_at = $suspend ? now() : null;
        $user->suspension_reason = $suspend ? 'Suspendido por administrador' : null;
        $user->save();

        $this->sendStatusEmail($user, $suspend ? 'suspended' : 'unsuspended');

        $this->logSecurityAudit(
            action: $suspend ? 'user.suspended' : 'user.unsuspended',
            entityType: 'user',
            entityId: (string) $user->id,
            before: $before,
            after: [
                'suspended_at' => optional($user->suspended_at)?->toDateTimeString(),
                'suspension_reason' => $user->suspension_reason,
                'email' => $user->email,
            ],
        );

        session()->flash('ok', $suspend ? 'Usuario suspendido correctamente.' : 'Usuario reactivado correctamente.');
    }

    public function render()
    {
        Gate::authorize('navbar.seguridad.editable');

        $users = User::query()
            ->with('roles')
            ->when($this->q !== '', function ($query) {
                $query->where(function ($sub) {
                    $sub->where('name', 'like', "%{$this->q}%")
                        ->orWhere('email', 'like', "%{$this->q}%");
                });
            })
            ->orderBy('name')
            ->paginate(20);

        foreach ($users as $user) {
            $this->selectedRoles[$user->id] ??= $user->roles->pluck('name')->values()->all();
        }

        return view('livewire.security.users-roles-page', [
            'users' => $users,
            'roles' => Role::query()->orderBy('name')->get(),
        ]);
    }

    private function sendStatusEmail(User $user, string $status): void
    {
        try {
            app(LegacyUserAccountStatusNotifier::class)->notify($user, $status, Auth::user()?->name);
        } catch (Throwable $e) {
            Log::warning('No fue posible enviar correo de estado de cuenta.', [
                'user_id' => $user->id,
                'email' => $user->email,
                'status' => $status,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
