<?php

namespace App\Http\Controllers\Api;

use App\Exports\CompanyStatisticsExport;
use App\Exports\EmployeeStatisticsExport;
use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateUserWorkScheduleRequest;
use App\Http\Resources\CompanyStatisticsResource;
use App\Http\Resources\ManagerUserResource;
use App\Http\Resources\TimeEntryResource;
use App\Http\Resources\TimeEntrySummaryResource;
use App\Http\Resources\WorkScheduleResource;
use App\Models\User;
use App\Services\TimeEntryService;
use App\Services\UserService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Auth;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ManagerUserController extends Controller
{
    public function __construct(
        protected UserService      $userService,
        protected TimeEntryService $timeEntryService
    )
    {
    }

    public function getUserTimeEntries(User $user): JsonResponse
    {
        $manager = Auth::user();

        if ($user->company_id !== $manager->company_id) {
            return response()->json([
                'message' => 'You do not have permission to view this user\'s time entries.',
            ], 403);
        }

        $data = $this->timeEntryService->getUserTimeEntriesById($user->id);

        return response()->json([
            'message' => 'Time entries retrieved successfully.',
            'data' => TimeEntryResource::collection($data['time_entries']),
        ]);
    }

    public function getUserTimeSummary(User $user): JsonResponse
    {
        $manager = Auth::user();

        if ($user->company_id !== $manager->company_id) {
            return response()->json([
                'message' => 'You do not have permission to view this user\'s statistics.',
            ], 403);
        }

        $data = $this->timeEntryService->getTimeSummaryById($user->id);

        return response()->json([
            'message' => 'Time summary retrieved successfully.',
            'data' => new TimeEntrySummaryResource($data),
        ]);
    }

    public function getUserWorkSchedule(User $user): JsonResponse
    {
        $manager = Auth::user();

        if ($user->company_id !== $manager->company_id) {
            return response()->json([
                'message' => 'You do not have permission to view this user\'s work schedule.',
            ], 403);
        }

        $data = $this->userService->getWorkSchedule($user);

        if (!$data['work_schedule']) {
            return response()->json([
                'message' => 'User has no work schedule assigned.',
                'user' => new ManagerUserResource($data['user']),
            ]);
        }

        return response()->json([
            'message' => 'Work schedule retrieved successfully.',
            'user' => new ManagerUserResource($data['user']),
            'work_schedule' => new WorkScheduleResource($data['work_schedule']),
        ]);
    }

    public function updateUserWorkSchedule(UpdateUserWorkScheduleRequest $request, User $user): JsonResponse
    {
        $manager = Auth::user();

        if ($user->company_id !== $manager->company_id) {
            return response()->json([
                'message' => 'You do not have permission to update this user\'s work schedule.',
            ], 403);
        }

        $workScheduleId = $request->validated('work_schedule_id');

        if ($user->work_schedule_id === $workScheduleId) {
            $data = $this->userService->getWorkSchedule($user);

            return response()->json([
                'message' => 'User already has this work schedule assigned.',
                'user' => new ManagerUserResource($data['user']),
                'work_schedule' => new WorkScheduleResource($data['work_schedule']),
            ]);
        }

        $this->userService->updateUserWorkSchedule($user, $workScheduleId);
        $data = $this->userService->getWorkSchedule($user);

        return response()->json([
            'message' => 'Work schedule updated successfully.',
            'user' => new ManagerUserResource($data['user']),
            'work_schedule' => new WorkScheduleResource($data['work_schedule']),
        ]);
    }

    public function getCompanyUsers(): AnonymousResourceCollection
    {
        $manager = Auth::user();
        $search = request()->query('search');

        $users = User::query()
            ->where('company_id', $manager->company_id)
            ->with(['workSchedule', 'manager'])
            ->when(!empty($search), function ($query) use ($search) {
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'ilike', "%{$search}%")
                        ->orWhere('email', 'ilike', "%{$search}%");
                });
            })
            ->paginate(15);

        return ManagerUserResource::collection($users);
    }

    public function getActiveTimeEntries(): JsonResponse
    {
        $manager = Auth::user();

        $data = $this->timeEntryService->getActiveCompanyEntries($manager->company_id);

        return response()->json([
            'message' => 'Active time entries retrieved successfully.',
            'data' => TimeEntryResource::collection($data['time_entries']),
        ]);
    }

    public function getUser(User $user): JsonResponse
    {
        $manager = Auth::user();

        if ($user->company_id !== $manager->company_id) {
            return response()->json([
                'message' => 'You do not have permission to view this user.',
            ], 403);
        }

        $user->load(['company', 'workSchedule', 'manager']);

        return response()->json([
            'message' => 'User retrieved successfully.',
            'data' => new ManagerUserResource($user),
        ]);
    }

    public function getCompanyStatistics(): JsonResponse
    {
        $manager = Auth::user();
        $data = $this->timeEntryService->getCompanyStatistics($manager->company_id);

        return response()->json([
            'message' => 'Company statistics retrieved successfully.',
            'data' => new CompanyStatisticsResource($data),
        ]);
    }

    public function getUsersStatistics(): JsonResponse
    {
        $manager = Auth::user();
        $perPage = request()->get('per_page', 15);
        $data = $this->timeEntryService->getAllEmployeeStatistics($manager->company_id, (int)$perPage);

        return response()->json([
            'message' => 'Employee statistics retrieved successfully.',
            'data' => $data['statistics'],
            'pagination' => $data['pagination'],
        ]);
    }

    public function exportCompanyStatistics(): BinaryFileResponse
    {
        $manager = Auth::user();
        $data = $this->timeEntryService->getAllEmployeeStatistics($manager->company_id);
        $collection = collect($data['statistics']);

        $filename = 'company-statistics-' . now()->format('Y-m-d') . '.xlsx';

        return Excel::download(new CompanyStatisticsExport($collection), $filename);
    }

    public function exportUserStatistics(User $user): BinaryFileResponse|JsonResponse
    {
        $manager = Auth::user();

        if ($user->company_id !== $manager->company_id) {
            return response()->json([
                'message' => 'You do not have permission to export this user\'s statistics.',
            ], 403);
        }

        $stats = $this->timeEntryService->getTimeSummaryById($user->id);
        $stats['user'] = $user;

        $filename = 'employee-statistics-' . $user->id . '-' . now()->format('Y-m-d') . '.xlsx';

        return Excel::download(new EmployeeStatisticsExport($stats), $filename);
    }
}
