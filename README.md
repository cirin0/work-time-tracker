# Work Time Tracker

RESTful API for work time tracking built with Laravel 12, JWT authentication, WebSocket support, GPS/QR attendance.

## Architecture

The project uses the Repository-Service pattern:

- **Controllers** - Handle HTTP requests
- **Services** - Business logic
- **Repositories** - Data access layer
- **Resources** - Format API responses
- **Form Requests** - Request validation

## Key Features

### Authentication & Authorization

- JWT authentication (php-open-source-saver/jwt-auth)
- Email verification with 6-digit codes (15-minute expiry, resend with 1-minute cooldown)
- Role-based system: Employee, Manager, Admin
- Middleware for role-based access control

### Profile & Security

- View and update own profile
- Avatar upload
- PIN code setup and change (required for clock-out)
- Password change via 6-digit email code

### Company Management

- View company details
- Admin: full CRUD, logo upload, assign manager
- Manager: view employees, view company statistics

### Time Tracking

- Clock-in / Clock-out with GPS and/or QR code validation
- Entry type auto-assigned: `gps`, `qr`, `gps_qr`, `remote`, `manual`
- Active entry tracking
- Time entry history and deletion
- Work time summary and statistics (lateness, early leave, overtime)

### GPS & QR Attendance Validation

- Office/Hybrid workers validated by GPS coordinates (Haversine formula) and/or daily rotating QR token
- Companies store `latitude`, `longitude`, `radius_meters` for geo-fencing
- `GET /api/qr-code/daily` returns today's QR token

### Work Mode

- `WorkMode` enum: `office`, `remote`, `hybrid`
- Determines clock-in validation rules
- Admin can update per user

### Work Schedules

- Create and manage schedules with daily entries (`DailySchedule`)
- Assign schedules to users
- Used for lateness/overtime calculation

### Leave Requests

- Create, view leave requests (sick, vacation, unpaid, personal, business_trip)
- Manager: view all / pending, approve/reject
- Push notifications (Email + Firebase FCM) on status change

### Real-time Messaging

- WebSocket chat via Laravel Reverb
- Private messages between users (encrypted at rest)
- Rate limiting: 60 messages/minute

### Audit Logging

- `Auditable` trait auto-logs create/update/delete on: `User`, `Company`, `TimeEntry`, `WorkSchedule`, `LeaveRequest`
- Users see their own logs; managers see company-scoped logs; admins see all
- `audit-logs:cleanup --days=90` artisan command

### Notifications

- Push notifications via Email and Firebase FCM (Cloud Messaging)
- Triggered on: leave request approve/reject, work schedule update
- FCM token registered per device via `PATCH /api/me/fcm-token`
- Configure `FIREBASE_CREDENTIALS` in `.env`

### Report Export

- Export own time entries to Excel (.xlsx) via `GET /api/time-entries/export`
- Optional query params: `from` and `to` (date format `Y-m-d`) to filter by date range
- Columns: ID, Employee, Email, Date, Start/Stop Time, Duration (min), Entry Type, Lateness, Early Leave, Overtime,
  Comments
- Powered by `maatwebsite/excel` (PhpSpreadsheet)

### Documentation & Monitoring

- Automatic OpenAPI documentation (Scramble)
- Laravel Telescope for debugging (development only)
- Postman collection export (laravel-postman)
- PHPStan static analysis (larastan)

## Tech Stack

### Core

- **PHP**: ^8.2
- **Laravel Framework**: ^12.53.0
- **Laravel Octane**: ^2.14 (FrankenPHP)
- **PostgreSQL**: Primary database

### Packages

- **JWT Auth**: ^2.8.3 (php-open-source-saver/jwt-auth)
- **Reverb**: ^1.8.0 (WebSocket server)
- **Scramble**: ^0.12.36 (API documentation)
- **Excel**: ^3.1 (maatwebsite/excel — xlsx export)
- **Telescope**: ^5.18.0 (development)
- **Pest**: ^3.8.5 (testing)
- **Larastan**: ^3.9.3 (static analysis)
- **Laravel Boost**: ^2.2.2 (dev tooling)

## Installation

### Requirements

- PHP 8.2 or higher
- Composer
- PostgreSQL 16+
- Git

### Installation Steps

1. **Clone the repository**
   ```bash
   git clone https://github.com/cirin0/work-time-tracker.git && cd work-time-tracker
   ```

2. **Install dependencies**
   ```bash
   composer install
   ```

3. **Environment setup**
   ```bash
   cp .env.example .env
   php artisan key:generate
   ```

4. **Configure the database**
    - Create PostgreSQL database `work-time-tracker` or your preferred name
    - Update `.env` file:
      ```env
      DB_CONNECTION=pgsql
      DB_HOST=127.0.0.1
      DB_PORT=5432
      DB_DATABASE=work-time-tracker
      DB_USERNAME=postgres
      DB_PASSWORD=postgres
      ```

5. **Generate JWT secret**
   ```bash
   php artisan jwt:secret
   ```

6. **Configure Reverb (WebSocket)**
   ```bash
   php artisan reverb:install
   ```

7. **Run migrations and seed data**
   ```bash
   php artisan migrate
   php artisan db:seed
   ```

8. **Start the server**
   ```bash
   php artisan octane:start --host=0.0.0.0 --port=8000
   ```
   For WebSocket support, run Reverb in a separate terminal:
   ```bash
   php artisan reverb:start
   ```

   Or run both together:
   ```bash
   composer run dev
   ```

   API will be available at: http://localhost:8000/api

9. **Run tests**
    - Create testing database `work-time-tracker_testing`
    - Update `phpunit.xml` with testing database name
   ```bash
   composer test
   ```

## Useful Links

- **API Documentation**: http://localhost:8000/docs/api
- **Telescope (dev)**: http://localhost:8000/telescope

## API Endpoints

### Authentication

- `POST /api/auth/register` - Register (sends verification code)
- `POST /api/auth/verify-email` - Verify email with 6-digit code
- `POST /api/auth/resend-verification-code` - Resend verification code
- `POST /api/auth/login` - Login
- `POST /api/auth/logout` - Logout
- `POST /api/auth/refresh` - Refresh JWT token

### Profile (authenticated user)

- `GET /api/me` - Current user profile
- `PATCH /api/me` - Update profile
- `POST /api/me/avatar` - Upload avatar
- `POST /api/me/request-password-change-code` - Request password change code via email
- `POST /api/me/change-password` - Change password (requires current password + code)
- `POST /api/me/pin-code` - Set up PIN code
- `PATCH /api/me/pin-code` - Change PIN code
- `GET /api/me/work-schedule` - View own work schedule
- `PATCH /api/me/fcm-token` - Register/update Firebase FCM device token

### Users

- `GET /api/users` - List users
- `GET /api/users/{user}` - User details

### Companies

- `GET /api/companies/{company}` - Company details
- `GET /api/companies/name/{company}` - Search company by name

### Time Tracking

- `GET /api/time-entries` - Entry history
- `GET /api/time-entries/active` - Current active entry
- `GET /api/time-entries/summary/me` - Own time summary & statistics
- `GET /api/time-entries/export` - Export entries to Excel (.xlsx); optional `from` / `to` date params
- `POST /api/time-entries` - Clock in (GPS/QR data optional based on work mode)
- `PATCH /api/time-entries/active/stop` - Clock out (requires PIN)
- `GET /api/time-entries/{timeEntry}` - Entry details
- `DELETE /api/time-entries/{timeEntry}` - Delete entry
- `GET /api/qr-code/daily` - Get today's QR token

### Leave Requests

- `GET /api/leave-requests` - My requests
- `GET /api/leave-requests/{leaveRequest}` - Request details
- `POST /api/leave-requests` - Create request

### Work Schedules

- `GET /api/work-schedules` - List schedules
- `POST /api/work-schedules` - Create schedule
- `GET /api/work-schedules/{id}` - Schedule details
- `PUT /api/work-schedules/{id}` - Update schedule
- `DELETE /api/work-schedules/{id}` - Delete schedule

### Messages

- `GET /api/messages/{receiverId}` - Chat history with user
- `POST /api/messages` - Send message (rate limit: 60/min)

### Audit Logs

- `GET /api/audit-logs` - Own audit log
- `GET /api/audit-logs/all` - All logs (Manager: company-scoped; Admin: all)

### Manager Endpoints (`role: manager, admin`)

- `GET /api/managers/leave-requests` - All subordinates' requests
- `GET /api/managers/leave-requests/pending` - Pending requests
- `POST /api/managers/leave-requests/{leaveRequest}/approve` - Approve
- `POST /api/managers/leave-requests/{leaveRequest}/reject` - Reject
- `GET /api/managers/users` - Company employees list
- `GET /api/managers/users/statistics` - Statistics for every employee (attendance, hours, lateness)
- `GET /api/managers/users/{user}` - Employee details
- `GET /api/managers/statistics` - Company aggregate statistics
- `GET /api/managers/statistics/export` - Export company statistics per employee to Excel (.xlsx)
- `GET /api/managers/users/{user}/time-entries` - Employee time entries
- `GET /api/managers/users/{user}/time-summary` - Employee time summary
- `GET /api/managers/users/{user}/statistics/export` - Export individual employee statistics to Excel (.xlsx)
- `GET /api/managers/users/{user}/work-schedule` - Employee work schedule
- `PATCH /api/managers/users/{user}/work-schedule` - Update employee work schedule

### Admin Endpoints (`role: admin`)

- `POST /api/admin/companies` - Create company
- `PATCH /api/admin/companies/{company}` - Update company
- `POST /api/admin/companies/{company}/logo` - Upload company logo
- `DELETE /api/admin/companies/{company}` - Delete company
- `POST /api/admin/companies/{company}/assign-manager` - Assign manager
- `POST /api/admin/companies/{company}/add-employee` - Add employee
- `DELETE /api/admin/companies/{company}/remove-employee` - Remove employee
- `GET /api/admin/users` - All users
- `GET /api/admin/users/{user}` - User details
- `GET /api/admin/companies/{companyId}/users` - Users by company
- `PATCH /api/admin/users/{user}` - Update user
- `PATCH /api/admin/users/{user}/role` - Change user role
- `PATCH /api/admin/users/{user}/work-mode` - Change work mode
- `POST /api/admin/users/{user}/reset-password` - Reset password
- `DELETE /api/admin/users/{user}` - Delete user

## Project Structure

```
app/
├── Console/Commands/
│   └── CleanupAuditLogs.php   # audit-logs:cleanup --days=90
├── Channels/
│   └── FcmChannel.php         # Custom Firebase FCM notification channel
├── Enums/
│   ...
├── Events/
│   ...
├── Exports/
│   └── TimeEntryExport.php    # Excel export (maatwebsite/excel)
├── Http/
│   ├── Controllers/Api/
│   │   ...
│   ├── Middleware/
│   │   └── RoleMiddleware.php
│   ├── Requests/              # Form Request validation classes
│   └── Resources/             # API Resource classes
├── Models/
│   ├── Message.php            # Encrypted message field
│   └── User.php
├── Notifications/
│   ├── LeaveRequestStatusNotification.php  # Email + FCM on approve/reject
│   ├── VerificationCodeNotification.php
│   └── WorkScheduleUpdatedNotification.php # Email + FCM on schedule change
├── Providers/
│   └── AppServiceProvider.php
├── Repositories/
│   ...
├── Services/
│   ├── GpsDistanceCalculator.php       # Haversine formula
│   └── QrCodeValidator.php             # Daily rotating SHA-256 token
└── Traits/
    └── Auditable.php                   # Auto-logs model events
```

## Data Models

- **User** - Users with roles (employee/manager/admin) and work mode
- **Company** - Companies with geo-fencing fields (latitude, longitude, radius_meters)
- **TimeEntry** - Work time entries with entry type and lateness data
- **WorkSchedule** - Work schedules
- **DailySchedule** - Daily entries within work schedules
- **LeaveRequest** - Leave requests with type and status
- **Message** - Encrypted chat messages
- **AuditLog** - Automatic change log for auditable models
- **EmailVerificationCode** - 6-digit codes for email verification and password change

## Roles & Permissions

### Employee (default)

- Manage own profile (avatar, PIN, password)
- Clock-in/Clock-out (PIN required for clock-out)
- View own time entries, summary, and work schedule
- Create leave requests
- Chat with other users
- View own audit log

### Manager

- All Employee permissions
- View company employees and their details
- View company statistics and time summaries
- Approve/Reject leave requests
- View company-scoped audit logs
- Update employee work schedules

### Admin

- All Manager permissions
- Full company CRUD with logo upload
- Assign managers to companies
- Change user roles and work modes
- Reset user passwords
- View all audit logs
