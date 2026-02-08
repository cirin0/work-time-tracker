<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreWorkScheduleRequest;
use App\Http\Requests\UpdateWorkScheduleRequest;
use App\Http\Resources\WorkScheduleResource;
use App\Models\WorkSchedule;
use App\Services\WorkScheduleService;
use Illuminate\Http\Request;

class WorkScheduleController extends Controller
{
    public function __construct(protected WorkScheduleService $workScheduleService) {}

    public function index(Request $request)
    {
        $companyId = $request->user()->company_id;
        $data = $this->workScheduleService->getAllWorkSchedulesById($companyId);

        return WorkScheduleResource::collection($data['schedules']);
    }

    public function show(string $id, Request $request)
    {
        $companyId = $request->user()->company_id;
        $data = $this->workScheduleService->getWorkScheduleById($id, $companyId);

        if (isset($data['message'])) {
            return response()->json(['message' => $data['message']], 404);
        }

        return new WorkScheduleResource($data['work_schedule']);
    }

    public function store(StoreWorkScheduleRequest $request)
    {
        $data = $request->validated();
        $data['company_id'] = $request->user()->company_id;

        $result = $this->workScheduleService->create($data);

        return new WorkScheduleResource($result['work_schedule']);
    }

    public function update(WorkSchedule $workSchedule, UpdateWorkScheduleRequest $request)
    {
        $data = $request->validated();
        $companyId = $request->user()->company_id;
        $data['company_id'] = $companyId;

        $result = $this->workScheduleService->update($workSchedule, $data, $companyId);

        if (isset($result['message'])) {
            return response()->json(['message' => $result['message']], 404);
        }

        return $result['work_schedule'];
    }

    public function destroy(WorkSchedule $workSchedule, Request $request)
    {
        $companyId = $request->user()->company_id;
        $result = $this->workScheduleService->delete($workSchedule, $companyId);

        if (isset($result['message'])) {
            return response()->json(['message' => $result['message']], 404);
        }

        return response()->noContent();
    }
}
