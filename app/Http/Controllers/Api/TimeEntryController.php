<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\TimeEntryRequest;
use App\Http\Resources\TimeEntryResource;
use App\Http\Resources\TimeEntryStartResource;
use App\Http\Resources\TimeEntryStopResource;
use App\Http\Resources\TimeEntrySummaryResource;
use App\Services\TimeEntryService;
use Illuminate\Support\Facades\Auth;
use OpenApi\Attributes as OA;


class TimeEntryController extends Controller
{
    public function __construct(protected TimeEntryService $timeEntryService)
    {
    }

    #[OA\Post(
        path: '/api/clock-in',
        operationId: 'startTimeEntry',
        description: 'Start a new time entry for the authenticated user.',
        summary: 'Start a time entry',
        requestBody: new OA\RequestBody(
            content: new OA\MediaType(
                mediaType: 'application/json',
                schema: new OA\Schema(
                    ref: '#/components/schemas/TimeEntryRequest'
                )
            )
        ),
        tags: ['Time Entries'],
        responses: [
            new OA\Response(
                response: '201',
                description: 'Time entry started successfully',
                content: new OA\MediaType(
                    mediaType: 'application/json',
                    schema: new OA\Schema(
                        ref: '#/components/schemas/TimeEntryStartResource'
                    )
                )
            ),
            new OA\Response(
                response: '400',
                description: 'Bad Request'
            )
        ]
    )]
    public function start(TimeEntryRequest $request)
    {
        $user = Auth::user();
        $comment = $request->input('comment');

        try {
            $timeEntry = $this->timeEntryService->startTimeEntry($user->id, $comment);
            return response()->json([
                'message' => 'Time entry started successfully',
                'data' => new TimeEntryStartResource($timeEntry)
            ], 201);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 400);
        }
    }

    #[OA\Post(
        path: '/api/clock-out',
        operationId: 'stopTimeEntry',
        description: 'Stop the currently active time entry for the authenticated user.',
        summary: 'Stop a time entry',
        requestBody: new OA\RequestBody(
            content: new OA\MediaType(
                mediaType: 'application/json',
                schema: new OA\Schema(
                    ref: '#/components/schemas/TimeEntryRequest'
                )
            )
        ),
        tags: ['Time Entries'],
        responses: [
            new OA\Response(
                response: '200',
                description: 'Time entry stopped successfully',
                content: new OA\MediaType(
                    mediaType: 'application/json',
                    schema: new OA\Schema(
                        ref: '#/components/schemas/TimeEntryStopResource'
                    )
                )
            ),
            new OA\Response(
                response: '400',
                description: 'Bad Request'
            )
        ]
    )]
    public function stop(TimeEntryRequest $request)
    {
        $user = Auth::user();
        $comment = $request->input('comment');

        try {
            $timeEntry = $this->timeEntryService->stopTimeEntry($user->id, $comment);
            return response()->json([
                'message' => 'Time entry stopped successfully',
                'data' => new TimeEntryStopResource($timeEntry)
            ]);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 400);
        }
    }

    #[OA\Get(
        path: 'api/time-entries',
        operationId: 'getTimeEntries',
        description: 'Retrieve all time entries for the authenticated user.',
        summary: 'Get time entries',
        tags: ['Time Entries'],
        responses: [
            new OA\Response(
                response: '200',
                description: 'List of time entries retrieved successfully',
                content: new OA\MediaType(
                    mediaType: 'application/json',
                    schema: new OA\Schema(
                        type: 'array',
                        items: new OA\Items(ref: '#/components/schemas/TimeEntryStopResource')
                    )
                )
            ),
            new OA\Response(
                response: '401',
                description: 'Unauthorized'
            )
        ]
    )]
    public function index()
    {
        $user = Auth::user();
        $timeEntries = $this->timeEntryService->getTimeEntries($user->id);

        return TimeEntryResource::collection($timeEntries);
    }

    #[OA\Get(
        path: 'api/me/time-summary',
        operationId: 'getTimeSummary',
        description: 'Retrieve a summary of time entries for the authenticated user.',
        summary: 'Get time summary',
        tags: ['Time Entries'],
        responses: [
            new OA\Response(
                response: '200',
                description: 'Time summary retrieved successfully',
                content: new OA\MediaType(
                    mediaType: 'application/json',
                    schema: new OA\Schema(
                        ref: '#/components/schemas/TimeEntrySummaryResource'
                    )
                )
            ),
            new OA\Response(
                response: '401',
                description: 'Unauthorized'
            )
        ]
    )]
    public function summary()
    {
        $user = Auth::user();
        $summary = $this->timeEntryService->getTimeSummary($user->id);
        return response()->json([
            'message' => 'Time summary retrieved successfully',
            'data' => new TimeEntrySummaryResource($summary)
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
