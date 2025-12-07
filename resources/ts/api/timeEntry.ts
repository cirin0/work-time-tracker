/**
 * Time entry interface matching Laravel TimeEntryResource
 * Endpoint: GET /api/time-entries/:id
 */
export interface TimeEntry {
    id: number;
    user_id: number;
    start_time: string; // Format: 'YYYY-MM-DD HH:mm:ss'
    stop_time: string | null; // Format: 'YYYY-MM-DD HH:mm:ss'
    duration: number | null; // Duration in minutes
    comment: string | null;
    created_at: string; // Format: 'YYYY-MM-DD HH:mm:ss'
    updated_at: string; // Format: 'YYYY-MM-DD HH:mm:ss'
}

/**
 * Time entry start response matching Laravel TimeEntryStartResource
 * Endpoint: POST /api/time-entries/start
 */
export interface TimeEntryStart {
    id: number;
    user_id: number;
    start_time: string; // Format: 'YYYY-MM-DD HH:mm:ss'
    comment: string | null;
}

/**
 * Time entry stop response matching Laravel TimeEntryStopResource
 * Endpoint: POST /api/time-entries/:id/stop
 */
export interface TimeEntryStop {
    id: number;
    user_id: number;
    start_time: string; // Format: 'YYYY-MM-DD HH:mm:ss'
    stop_time: string; // Format: 'YYYY-MM-DD HH:mm:ss'
    duration: number; // Duration in minutes
    comment: string | null;
}

/**
 * Time entry summary interface matching Laravel TimeEntrySummaryResource
 * Endpoint: GET /api/time-entries/summary
 */
export interface TimeEntrySummary {
    user_id: number;
    total_hours: number;
    total_minutes: number;
    entries_count: number;
    average_work_time: string; // Format: 'HH:mm'
    summary: {
        today: string; // Format: 'HH:mm'
        week: string; // Format: 'HH:mm'
        month: string; // Format: 'HH:mm'
    };
}
