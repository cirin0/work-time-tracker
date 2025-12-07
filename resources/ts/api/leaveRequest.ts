import type {LeaveRequestStatus} from '../enums/LeaveRequestStatus';
import type {LeaveRequestType} from '../enums/LeaveRequestType';

/**
 * Leave request interface matching Laravel LeaveRequestResource
 * Note: Uses parent::toArray() so returns all model attributes
 * Endpoint: GET /api/leave-requests/:id
 */
export interface LeaveRequest {
    id: number;
    user_id: number;
    type: LeaveRequestType;
    start_date: string; // Format: 'YYYY-MM-DD'
    end_date: string; // Format: 'YYYY-MM-DD'
    reason: string;
    status: LeaveRequestStatus;
    processed_by_manager_id: number | null;
    manager_comments: string | null;
    created_at: string; // ISO timestamp
    updated_at: string; // ISO timestamp
}
