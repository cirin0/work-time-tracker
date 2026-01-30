export type AuthUser = {
    id: number;
    name: string;
    email: string;
    role: UserRole;
    avatar: string | null;
    created_at: string;
    updated_at: string;
};
export type Company = {
    id: number;
    name: string;
    email: string | null;
    phone: string | null;
    logo: string | null;
    description: string | null;
    address: string | null;
};
export type DailySchedule = {
    id: number;
    day_of_week: string;
    start_time: string | null;
    end_time: string | null;
    is_working_day: boolean;
};
export type LeaveRequest = {
    id: number;
    user_id: number;
    type: LeaveRequestType;
    start_date: string;
    end_date: string;
    reason: string | null;
    status: LeaveRequestStatus;
    processed_by_manager_id: number | null;
    manager_comments: string | null;
    created_at: string;
    user: UserBasic | null;
    manager: UserBasic | null;
};
export type LeaveRequestStatus = 'pending' | 'approved' | 'rejected';
export type LeaveRequestType = 'sick' | 'vacation' | 'unpaid' | 'personal';
export type Message = {
    id: number;
    sender_id: number;
    receiver_id: number;
    message: string;
    created_at: string;
    sender: UserBasic | null;
    receiver: UserBasic | null;
};
export type TimeEntry = {
    id: number;
    user_id: number;
    start_time: string;
    stop_time: string | null;
    duration: number | null;
    start_comment: string | null;
    stop_comment: string | null;
    created_at: string;
    user: UserBasic | null;
};
export type TimeEntrySummary = {
    user_id: number;
    total_hours: number;
    total_minutes: number;
    entries_count: number;
    average_work_time: number;
    summary: TimeEntrySummaryPeriod;
};
export type TimeEntrySummaryPeriod = {
    today: number;
    week: number;
    month: number;
};
export type User = {
    id: number;
    name: string;
    email: string;
    role: UserRole;
    company_id: number | null;
    manager_id: number | null;
    avatar: string | null;
    work_schedule_id: number | null;
    company: Company | null;
    manager: UserBasic | null;
    work_schedule: WorkSchedule | null;
};
export type UserBasic = {
    id: number;
    name: string;
    email: string;
    avatar: string | null;
};
export type UserRole = 'employee' | 'admin' | 'manager';
export type WorkSchedule = {
    id: number;
    name: string;
    description: string | null;
    is_default: boolean;
    company_id: number;
    daily_schedules: Array<DailySchedule>;
};
