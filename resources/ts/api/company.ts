/**
 * Company manager object
 */
export interface CompanyManager {
    id: number;
    name: string;
    email: string;
}

/**
 * Company employee object
 */
export interface CompanyEmployee {
    id: number;
    name: string;
    email: string;
}

/**
 * Company interface matching Laravel CompanyResource
 * Endpoint: GET /api/companies/:id
 */
export interface Company {
    id: number;
    name: string;
    email: string;
    phone: string;
    logo: string;
    description: string;
    address: string;
    manager: CompanyManager | null;
    employees: CompanyEmployee[];
    users_count: number;
    created_at: string; // Format: 'dd-mm-yyyy HH:mm:ss'
    updated_at: string; // Format: 'dd-mm-yyyy HH:mm:ss'
}

/**
 * Company store/create response interface matching Laravel CompanyStoreResource
 * Endpoint: POST /api/companies
 */
export interface CompanyStoreResponse {
    id: number;
    name: string;
    address: string;
    email: string;
    phone: string;
    description: string;
    logo: string | null;
    manager_id: number;
    created_at: string; // ISO timestamp
    updated_at: string; // ISO timestamp
}
