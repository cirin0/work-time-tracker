<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\TimeEntryRequest;
use App\Services\TimeEntryService;
use Illuminate\Support\Facades\Auth;


class TimeEntryController extends Controller
{
    public function __construct(protected TimeEntryService $timeEntryService)
    {
    }

    public function start(TimeEntryRequest $request)
    {
        $user = Auth::user();
        $comment = $request->input('comment');

        try {
            $timeEntry = $this->timeEntryService->startTimeEntry($user->id, $comment);
            return response()->json([
                'message' => 'Time entry started successfully',
                'data' => $timeEntry
            ], 201);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 400);
        }
    }

    public function stop(TimeEntryRequest $request)
    {
        $user = Auth::user();
        $comment = $request->input('comment');

        try {
            $timeEntry = $this->timeEntryService->stopTimeEntry($user->id, $comment);
            return response()->json([
                'message' => 'Time entry stopped successfully',
                'data' => $timeEntry
            ]);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 400);
        }
    }

    public function index()
    {
        $user = Auth::user();
        $timeEntries = $this->timeEntryService->getTimeEntries($user->id);

        return response()->json([
            'data' => $timeEntries
        ]);
    }

    public function summary()
    {
        $user = Auth::user();
        $summary = $this->timeEntryService->getTimeSummary($user->id);

        return response()->json([
            'data' => $summary
        ]);
    }

    public function destroy(string $id)
    {
        $user = Auth::user();

        try {
            $this->timeEntryService->deleteTimeEntry((int)$id, $user->id);
            return response()->json([
                'message' => 'Time entry deleted successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 400);
        }
    }
}
