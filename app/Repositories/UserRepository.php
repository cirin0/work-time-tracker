<?php

namespace App\Repositories;

use App\Models\User;
use Illuminate\Pagination\LengthAwarePaginator;

class UserRepository
{
    public function register(array $data): User
    {
        return User::query()->create($data);
    }

    public function getPaginated(int $perPage = 10): LengthAwarePaginator
    {
        return User::query()->paginate($perPage);
    }

    public function find(int $id): ?User
    {
        return User::query()->find($id);
    }

    public function delete(User $user): bool
    {
        return $user->delete();
    }
}
