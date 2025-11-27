import type {UserRole} from '../enums/UserRole';

/**
 * Nested company object in User
 */
export interface UserCompany {
    id: number;
    name: string;
}

/**
 * Nested manager object in User
 */
export interface UserManager {
    id: number;
    name: string;
}

/**
 * Nested work_schedule object in User
 */
export interface UserWorkSchedule {
    id: number;
    name: string;
}

/**
 * User interface matching Laravel UserResource
 * Endpoint: GET /api/users/:id
 */
export interface User {
    id: number;
    name: string;
    email: string;
    role: UserRole;
    avatar: string | null;
    company: UserCompany | null;
    manager: UserManager | null;
    work_schedule: UserWorkSchedule | null;
    created_at: string; // Format: 'dd-mm-yyyy HH:mm:ss'
    updated_at: string; // Format: 'dd-mm-yyyy HH:mm:ss'
}

/**
 * Auth user interface matching Laravel AuthResource
 * Endpoint: GET /api/me
 */
export interface AuthUser {
    id: number;
    name: string;
    email: string;
    role: UserRole;
    avatar: string | null;
    created_at: string; // Format: 'dd-mm-yyyy HH:mm:ss'
    updated_at: string; // Format: 'dd-mm-yyyy HH:mm:ss'
}
