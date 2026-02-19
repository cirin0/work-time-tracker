<?php

namespace App\Http\Resources;

use App\Enums\UserRole;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

class UserResource extends JsonResource
{
    public static $wrap = null;

    public function toArray(Request $request): array
    {
        $authUser = $request->user();
        $isAdmin = $authUser && $authUser->role === UserRole::ADMIN;
        $isManager = $authUser && $authUser->role === UserRole::MANAGER;

        $data = [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'avatar' => $this->avatar ? Storage::url($this->avatar) : null,
        ];

        if ($isManager || $isAdmin) {
            $data['work_mode'] = $this->work_mode;
            $data['company'] = $this->whenLoaded('company', fn() => [
                'id' => $this->company->id,
                'name' => $this->company->name,
            ]);
            $data['manager'] = $this->whenLoaded('manager', fn() => [
                'id' => $this->manager->id,
                'name' => $this->manager->name,
            ]);
            $data['work_schedule'] = $this->whenLoaded('workSchedule', fn() => [
                'id' => $this->workSchedule->id,
                'name' => $this->workSchedule->name,
            ]);
            $data['created_at'] = $this->created_at->format('d-m-Y H:i:s');
            $data['updated_at'] = $this->updated_at->format('d-m-Y H:i:s');
        }

        if ($isAdmin) {
            $data['role'] = $this->role;
            $data['has_pin_code'] = !empty($this->pin_code);
        }

        return $data;
    }
}
