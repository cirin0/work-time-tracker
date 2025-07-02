<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'UserResource',
    description: 'User resource representation',
    properties: [
        new OA\Property(property: 'name', type: 'string', example: 'John Doe'),
        new OA\Property(property: 'email', type: 'string', format: 'email', example: 'john@example.com'),
        new OA\Property(property: 'role', type: 'string', enum: ['user', 'admin', 'manager'], example: 'user'),
        new OA\Property(property: 'created_at', type: 'string', format: 'date-time', example: '2023-10-01T12:00:00Z'),
        new OA\Property(property: 'updated_at', type: 'string', format: 'date-time', example: '2023-10-01T12:00:00Z'),
    ],
    type: 'object'
)]
class UserResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'role' => $this->role,
            'avatar' => $this->avatar,
            'company' => $this->company ? [
                'id' => $this->company->id,
                'name' => $this->company->name,
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
