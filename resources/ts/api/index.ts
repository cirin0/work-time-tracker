/**
 * Standard API response wrapper
 */
export interface ApiResponse<T> {
    data: T;
    message?: string;
    success?: boolean;
}

/**
 * API error response
 */
export interface ApiError {
    message: string;
    errors?: Record<string, string[]>;
}

// export interface ApiResponse<T = unknown> {
//   success: boolean;
//   message?: string;
//   data?: T;
//   errors?: Record<string, string[]>;
// }

/**
 * Paginated response from Laravel
 */
export interface PaginatedResponse<T> {
    data: T[];
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
    from: number | null;
    to: number | null;
    links: {
        first: string | null;
        last: string | null;
        prev: string | null;
        next: string | null;
    };
}

/**
 * Authentication response
 */
export interface AuthResponse {
    access_token: string;
    token_type: string;
    expires_in: number;
}

// Re-export all types
export * from './user';
export * from './company';
export * from './timeEntry';
export * from './leaveRequest';
export * from './workSchedule';
export * from '../enums/UserRole';
export * from '../enums/LeaveRequestStatus';
export * from '../enums/LeaveRequestType';
