<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\WorkScheduleRequest;
use App\Http\Requests\WorkScheduleUpdateRequest;
use App\Http\Resources\WorkScheduleResource;
use App\Services\WorkScheduleService;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\HttpException;


class WorkScheduleController extends Controller
{
    public function __construct(protected WorkScheduleService $workScheduleService)
    {
    }

    public function index(Request $request)
    {
        $companyId = $request->user()->company_id;
        $schedules = $this->workScheduleService->getAllWorkSchedulesById($companyId);
        return WorkScheduleResource::collection($schedules);
    }

    public function show(string $id)
    {
        try {
            $workSchedule = $this->workScheduleService->getWorkScheduleById($id);
            return new WorkScheduleResource($workSchedule);
        } catch (HttpException $e) {
            return response()->json(['message' => 'Work schedule not found'], 404);
        }
    }

    public function store(WorkScheduleRequest $request)
    {
        $data = $request->validated();
        $data['company_id'] = $request->user()->company_id;

        return $this->workScheduleService->create($data);
    }

    public function update(string $id, WorkScheduleUpdateRequest $request)
    {
        $data = $request->validated();
        $data['company_id'] = $request->user()->company_id;

        return $this->workScheduleService->update($id, $data);
    }

    public function destroy(string $id)
    {
        $this->workScheduleService->delete($id);
        return response()->noContent();
    }
}
