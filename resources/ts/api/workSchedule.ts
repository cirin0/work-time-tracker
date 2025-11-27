/**
 * Daily schedule interface matching Laravel DailyScheduleResource
 */
export interface DailySchedule {
    id: number;
    day_of_week: number; // 0 = Sunday, 1 = Monday, ..., 6 = Saturday
    start_time: string; // Format: 'HH:mm'
    end_time: string; // Format: 'HH:mm'
    break_duration: number; // Break duration in minutes
    is_working_day: boolean;
}

/**
 * Work schedule interface matching Laravel WorkScheduleResource
 * Endpoint: GET /api/work-schedules/:id
 */
export interface WorkSchedule {
    id: number;
    name: string;
    is_default: boolean;
    daily_schedules?: DailySchedule[]; // Optional, loaded with 'dailySchedules' relationship
}
