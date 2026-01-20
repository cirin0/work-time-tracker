<p align="center"><a href="https://laravel.com" target="_blank"><img src="https://raw.githubusercontent.com/laravel/art/master/logo-lockup/5%20SVG/2%20CMYK/1%20Full%20Color/laravel-logolockup-cmyk-red.svg" width="400" alt="Laravel Logo"></a></p>

# Work Time Tracker

RESTful API for work time tracking built with Laravel 12, JWT authentication, and WebSocket support.

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
- Role-based system: Employee, Manager, Admin
- Middleware for role-based access control

### Company Management
- CRUD operations for companies
- Employee assignment to companies
- Employee management (managers only)

### Time Tracking
- Clock-in/Clock-out functionality
- Time entry history
- Work time reports and statistics
- Work schedule integration

### Work Schedules
- Create and manage schedules
- Daily schedules (DailySchedule)
- Assign schedules to users

### Leave Requests
- Create leave requests
- Approve/Reject functionality for managers
- Request history

### Real-time Messaging
- WebSocket chat via Laravel Reverb
- Private messages between users
- Rate limiting (60 messages/minute)

### Documentation & Monitoring
- Automatic OpenAPI documentation (Scramble)
- Laravel Telescope for debugging (development only)
- Postman collection (laravel-postman)

## Tech Stack

### Core
- **PHP**: ^8.2
- **Laravel Framework**: ^12.47.0
- **PostgreSQL**: Primary database

### Packages
- **JWT Auth**: ^2.8.3 php-open-source-saver/jwt-auth 
- **Reverb**: ^1.7.0 (WebSocket server)
- **Scramble**: ^0.12.36 (API documentation)
- **Telescope**: ^5.16.1 (development)
- **Pest**: ^3.8.4 (testing)

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
   php artisan serve
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
   php artisan test
   ```

## Useful Links

- **API Documentation**: http://localhost:8000/docs/api
- **Telescope (dev)**: http://localhost:8000/telescope

## API Endpoints

### Authentication
- `POST /api/auth/register` - User registration
- `POST /api/auth/login` - Login
- `POST /api/auth/logout` - Logout
- `POST /api/auth/refresh` - Refresh token
- `GET /api/me` - Current user

### Users
- `GET /api/users` - List users
- `GET /api/users/{user}` - User details
- `PUT /api/users/{user}` - Update profile
- `POST /api/users/{user}/avatar` - Upload avatar
- `POST /api/users/{user}/role` - Change role (Admin)
- `DELETE /api/users/{user}` - Delete user

### Companies
- `GET /api/companies/{company}` - Company details
- `GET /api/companies/name/{company}` - Search by name
- `POST /api/companies` - Create company
- `PUT /api/companies/{company}` - Update
- `DELETE /api/companies/{company}` - Delete

### Time Tracking
- `POST /api/clock-in` - Clock in
- `POST /api/clock-out` - Clock out
- `GET /api/time-entries` - Entry history
- `GET /api/me/time-summary` - Time summary

### Leave Requests
- `GET /api/leave-requests` - My requests
- `POST /api/leave-requests` - Create request
- `GET /api/manager/leave-requests` - Subordinates' requests (Manager)
- `POST /api/manager/leave-requests/{id}/approve` - Approve (Manager)
- `POST /api/manager/leave-requests/{id}/reject` - Reject (Manager)

### Work Schedules
- `GET /api/work-schedules` - List schedules
- `POST /api/work-schedules` - Create schedule
- `GET /api/work-schedules/{id}` - Details
- `PUT /api/work-schedules/{id}` - Update
- `DELETE /api/work-schedules/{id}` - Delete
- `GET /api/users/{user}/work-schedule` - User schedule
- `PUT /api/users/{user}/work-schedule` - Set schedule

### Messages
- `GET /api/messages/{receiverId}` - Chat history
- `POST /api/messages` - Send message (rate limit: 60/min)

### Manager Endpoints
- `POST /api/manager/companies/{company}/add-employee` - Add employee
- `POST /api/manager/companies/{company}/remove-employee` - Remove employee

## Project Structure

```
app/
├── Classes/           # Helper classes
├── Enums/            # UserRole enum
├── Events/           # MessageSent event
├── Http/
│   ├── Controllers/
│   │   └── Api/     # API controllers
│   │       ├── Manager/  # Manager controllers
│   │       ├── AuthController
│   │       ├── CompanyController
│   │       ├── LeaveRequestController
│   │       ├── MessageController
│   │       ├── TimeEntryController
│   │       ├── UserController
│   │       └── WorkScheduleController
│   ├── Middleware/   # Custom middleware
│   ├── Requests/     # Form Request validation
│   └── Resources/    # API Resources
├── Models/           # Eloquent models
├── Repositories/     # Data access layer
├── Services/         # Business logic layer
└── Providers/        # Service Providers

tests/
├── Feature/          # Feature tests
│   ├── AuthTest.php
│   ├── ChatTest.php
│   ├── CompanyTest.php
│   ├── LeaveRequestTest.php
│   ├── TimeEntryTest.php
│   ├── UserManagementTest.php
│   ├── UserTest.php
│   └── WorkScheduleTest.php
└── Unit/             # Unit tests
```

## Data Models

- **User** - Users with roles (employee/manager/admin)
- **Company** - Companies
- **TimeEntry** - Work time entries
- **WorkSchedule** - Work schedules
- **DailySchedule** - Daily schedules within work schedules
- **LeaveRequest** - Leave requests
- **Message** - Chat messages

## Roles & Permissions

### Employee (default)
- Manage own profile
- Clock-in/Clock-out
- View own time and schedules
- Create leave requests
- Chat with other users

### Manager
- All Employee permissions
- Manage company employees
- Approve/Reject leave requests
- View subordinates' time

### Admin
- All Manager permissions
- Change user roles
- Full access to all data

