<?php

namespace App\Services;

use App\Enums\UserRole;
use App\Enums\WorkMode;
use App\Models\EmailVerificationCode;
use App\Models\User;
use App\Models\WorkSchedule;
use App\Notifications\NewEmailNotification;
use App\Notifications\ProfileUpdatedNotification;
use App\Notifications\VerificationCodeNotification;
use App\Notifications\WorkScheduleUpdatedNotification;
use App\Repositories\UserRepository;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;

class UserService
{
    public function __construct(
        protected UserRepository $repository,
        protected CacheService   $cacheService
    )
    {
    }

    public function getAllPaginated(): array
    {
        $users = $this->repository->getPaginated();

        return ['users' => $users];
    }

    public function getById(User $user): array
    {
        return ['user' => $user];
    }

    public function updateRole(User $user, UserRole $role): array
    {
        $user->role = $role;
        $user->save();

        return ['user' => $user];
    }

    public function updateAvatar(User $user, UploadedFile $avatar): array
    {
        if ($user->avatar) {
            Storage::disk('public')->delete($user->avatar);
        }

        $path = $avatar->store('avatars', 'public');
        $user->update(['avatar' => $path]);
        $user->load(['company', 'manager', 'workSchedule']);

        return ['user' => $user];
    }

    public function delete(User $user): array
    {
        $deleted = $this->repository->delete($user);

        return ['deleted' => $deleted];
    }


    public function updateProfile(User $user, array $data): array
    {
        $profileData = array_intersect_key($data, array_flip(['name']));

        $user->update($profileData);
        $user->load(['company', 'manager', 'workSchedule']);

        return ['user' => $user];
    }

    public function updateByAdmin(User $user, array $data): array
    {
        $oldEmail = $user->email;
        $oldName = $user->name;

        $user->update($data);
        $user->load(['company', 'manager', 'workSchedule']);

        $changes = [];

        if (isset($data['name']) && $data['name'] !== $oldName) {
            $changes['name'] = $data['name'];
        }

        if (isset($data['email']) && $data['email'] !== $oldEmail) {
            $changes['email'] = $data['email'];
        }

        if (!empty($changes)) {
            dispatch(function () use ($oldEmail, $changes, $user) {
                Notification::route('mail', $oldEmail)
                    ->notify(new ProfileUpdatedNotification($changes));

                if (isset($changes['email'])) {
                    $user->notify(new NewEmailNotification($oldEmail));
                }
            })->afterResponse();
        }

        return ['user' => $user];
    }

    public function getWorkSchedule(User $user): array
    {
        $workSchedule = null;

        if ($user->work_schedule_id) {
            $workSchedule = $this->cacheService->getWorkSchedule($user->work_schedule_id);
        }

        return [
            'user' => $user,
            'work_schedule' => $workSchedule,
        ];
    }

    public function updateUserWorkSchedule(User $user, int $workScheduleId): array
    {
        if ($user->work_schedule_id) {
            $this->cacheService->clearWorkScheduleCache($user->work_schedule_id);
        }

        $user->update(['work_schedule_id' => $workScheduleId]);

        $workSchedule = WorkSchedule::find($workScheduleId);
        if ($workSchedule) {
            $user->notify(new WorkScheduleUpdatedNotification($workSchedule));
        }

        return ['user' => $user];
    }

    public function requestPasswordChangeCode(User $user): array
    {
        $code = str_pad((string)random_int(100000, 999999), 6, '0', STR_PAD_LEFT);

        EmailVerificationCode::query()
            ->where('user_id', $user->id)
            ->where('type', 'password_change')
            ->whereNull('verified_at')
            ->delete();

        EmailVerificationCode::query()->create([
            'user_id' => $user->id,
            'code' => $code,
            'type' => 'password_change',
            'expires_at' => now()->addMinutes(15),
        ]);

        if (config('app.env') === 'local') {
            return ['message' => 'Code generated for local environment: ' . $code];
        }
        $user->notify(new VerificationCodeNotification($code, 'зміни паролю'));

        return ['message' => 'Verification code sent to your email'];
    }

    public function changePasswordWithCode(User $user, array $data): array
    {
        if (!Hash::check($data['current_password'], $user->password)) {
            return ['error' => true, 'message' => 'The current password is incorrect.'];
        }

        $verificationCode = EmailVerificationCode::query()
            ->where('user_id', $user->id)
            ->where('code', $data['code'])
            ->where('type', 'password_change')
            ->whereNull('verified_at')
            ->first();

        if (!$verificationCode) {
            return ['error' => true, 'message' => 'Invalid verification code'];
        }

        if ($verificationCode->isExpired()) {
            return ['error' => true, 'message' => 'Verification code has expired'];
        }

        $verificationCode->update(['verified_at' => now()]);

        $user->update(['password' => Hash::make($data['new_password'])]);

        return ['message' => 'Password changed successfully'];
    }

    public function setupPinCode(User $user, string $pinCode): array
    {
        if ($user->pin_code) {
            return ['message' => 'Pin code is already set.'];
        }

        $user->update(['pin_code' => $pinCode]);

        return ['user' => $user];
    }

    public function changePinCode(User $user, string $oldPinCode, string $newPinCode): array
    {
        if (!Hash::check($oldPinCode, $user->pin_code)) {
            return ['message' => 'The current pin code is incorrect.'];
        }

        $user->update(['pin_code' => $newPinCode]);

        return ['user' => $user];
    }

    public function updateWorkMode(User $user, WorkMode $workMode): array
    {
        $user->work_mode = $workMode;
        $user->save();

        return ['user' => $user];
    }

    public function resetPassword(User $user, string $password): array
    {
        $user->update(['password' => Hash::make($password)]);


        return ['user' => $user];
    }
}
