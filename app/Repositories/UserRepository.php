<?php

namespace App\Repositories;

use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

class UserRepository
{
    public function find(int $id): ?User
    {
        return User::query()
            ->with(['company', 'manager', 'workSchedule'])
            ->find($id);
    }

    public function getAll(): Collection
    {
        return User::query()
            ->with(['company', 'manager', 'workSchedule'])
            ->get();
    }

    public function getPaginated(int $perPage = 10): LengthAwarePaginator
    {
        return User::query()
            ->with(['company', 'manager', 'workSchedule'])
            ->paginate($perPage);
    }

    public function create(array $data): User
    {
        return User::query()->create($data);
    }

    public function update(User $user, array $data): bool
    {
        return $user->update($data);
    }

    public function delete(User $user): ?bool
    {
        return $user->delete();
    }
}
