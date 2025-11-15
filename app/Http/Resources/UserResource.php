<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

class UserResource extends JsonResource
{

    public static $wrap = null;

    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'role' => $this->role,
            'avatar' => $this->avatar ? Storage::url($this->avatar) : null,
            'company' => $this->company ? [
                'id' => $this->company->id,
                'name' => $this->company->name,
            ] : null,
            'manager' => $this->manager ? [
                'id' => $this->manager->id,
                'name' => $this->manager->name,
            ] : null,
            'work_schedule' => $this->workSchedule ? [
                'id' => $this->workSchedule->id,
                'name' => $this->workSchedule->name,
            ] : null,
            'created_at' => $this->created_at->format('d-m-Y H:i:s'),
            'updated_at' => $this->updated_at->format('d-m-Y H:i:s'),
        ];
    }
}
