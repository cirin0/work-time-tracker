<?php

namespace App\Http\Controllers\Api;

use App\Exports\TimeEntryExport;
use App\Http\Controllers\Controller;
use App\Http\Requests\StopTimeEntryRequest;
use App\Http\Requests\StoreTimeEntryRequest;
use App\Http\Resources\TimeEntryResource;
use App\Http\Resources\TimeEntrySummaryResource;
use App\Models\TimeEntry;
use App\Services\CacheService;
use App\Services\TimeEntryService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class TimeEntryController extends Controller
{
    public function __construct(
        protected TimeEntryService $timeEntryService,
        protected CacheService     $cacheService
    )
    {
    }

    public function active(): JsonResponse
    {
        $data = $this->timeEntryService->getActiveTimeEntry(Auth::user());
        $activeEntry = $data['time_entry'];

        if (!$activeEntry) {
            return response()->json([
                'message' => 'No active time entry found.',
                'data' => null,
            ]);
        }

        return response()->json([
            'message' => 'Active time entry retrieved successfully.',
            'data' => new TimeEntryResource($activeEntry),
        ]);
    }

    public function summary(): JsonResponse
    {
        $data = $this->timeEntryService->getTimeSummary(Auth::user());

        return response()->json([
            'message' => 'Time summary retrieved successfully.',
            'data' => new TimeEntrySummaryResource($data),
        ]);
    }

    public function index(Request $request): AnonymousResourceCollection
    {
        $perPage = $request->input('per_page', 15);
        $data = $this->timeEntryService->getUserTimeEntries(Auth::user(), $perPage);

        return TimeEntryResource::collection($data['time_entries']);
    }

    public function show(TimeEntry $timeEntry): JsonResponse
    {
        $data = $this->timeEntryService->getTimeEntryById(Auth::user(), $timeEntry);

        return response()->json([
            'message' => 'Time entry retrieved successfully.',
            'data' => new TimeEntryResource($data['time_entry']),
        ]);
    }

    public function store(StoreTimeEntryRequest $request): JsonResponse
    {
        $data = $this->timeEntryService->startTimeEntry(
            Auth::user(),
            $request->validated()
        );

        if (isset($data['message'])) {
            return response()->json(['message' => $data['message']], 400);
        }

        $entry = $data['time_entry']->load('user');

        return response()->json([
            'message' => 'Time entry started successfully.',
            'data' => new TimeEntryResource($entry),
        ], 201);
    }

    public function stopActive(StopTimeEntryRequest $request): JsonResponse
    {
        $data = $this->timeEntryService->stopActiveTimeEntry(
            Auth::user(),
            $request->validated()
        );

        $entry = $data['time_entry']->load('user');

        return response()->json([
            'message' => 'Time entry stopped successfully.',
            'data' => new TimeEntryResource($entry),
        ]);
    }

    public function destroy(TimeEntry $timeEntry): Response
    {
        $this->timeEntryService->deleteTimeEntry(Auth::user(), $timeEntry);

        return response()->noContent();
    }

    public function export(Request $request): BinaryFileResponse
    {
        $user = Auth::user();
        $from = $request->input('from');
        $to = $request->input('to');

        $entries = $this->timeEntryService->getTimeEntriesForExport($user, $from, $to);
        $collection = collect($entries['time_entries']);

        $filename = 'time-entries-' . now()->format('Y-m-d') . '.xlsx';

        return Excel::download(new TimeEntryExport($collection), $filename);
    }

    public function getDailyQrCode(Request $request): JsonResponse
    {
        $user = $request->user();
        $company = $user->company;

        if (!$company || !$company->qr_secret) {
            return response()->json([
                'message' => 'Company QR secret not configured.',
            ], 400);
        }

        $qrData = $this->cacheService->getDailyQrCode($company);

        return response()->json([
            'message' => 'Daily QR code retrieved successfully.',
            'data' => $qrData,
        ]);
    }
}
