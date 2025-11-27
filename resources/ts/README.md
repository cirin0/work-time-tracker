# TypeScript Types for Work Time Tracker API

Цей каталог містить TypeScript типи, які точно відповідають Laravel API Resources.

## 📁 Структура

```
resources/ts/
  ├── enums/
  │   ├── UserRole.ts                  # Ролі користувачів
  │   ├── LeaveRequestStatus.ts        # Статуси запитів на відпустку
  │   └── LeaveRequestType.ts          # Типи запитів на відпустку
  ├── api/
  │   ├── user.ts                      # User & AuthUser типи
  │   ├── company.ts                   # Company типи
  │   ├── timeEntry.ts                 # TimeEntry типи
  │   ├── leaveRequest.ts              # LeaveRequest типи
  │   ├── workSchedule.ts              # WorkSchedule & DailySchedule типи
  │   └── index.ts                     # Загальні типи + re-export всіх
  └── README.md
```

## 🎯 Використання

### Імпорт типів

```typescript
// Імпорт окремих типів
import type { User, Company, TimeEntry } from './resources/ts/api';
import { UserRole, LeaveRequestStatus } from './resources/ts/api';

// Або з конкретних файлів
import type { User } from './resources/ts/api/user';
import { UserRole } from './resources/ts/enums/UserRole';
```

### Приклади використання

```typescript
// User
const user: User = {
  id: 1,
  name: 'John Doe',
  email: 'john@example.com',
  role: UserRole.Employee,
  avatar: 'https://example.com/avatar.jpg',
  company: {
    id: 1,
    name: 'Tech Corp'
  },
  manager: {
    id: 2,
    name: 'Jane Smith'
  },
  work_schedule: {
    id: 1,
    name: 'Standard 9-5'
  },
  created_at: '23-11-2025 14:30:45',
  updated_at: '23-11-2025 14:30:45'
};

// Time Entry
const timeEntry: TimeEntry = {
  id: 1,
  user_id: 1,
  start_time: '2025-11-23 09:00:00',
  stop_time: '2025-11-23 17:00:00',
  duration: 480, // minutes
  comment: 'Regular work day',
  created_at: '2025-11-23 09:00:00',
  updated_at: '2025-11-23 17:00:00'
};

// Leave Request
const leaveRequest: LeaveRequest = {
  id: 1,
  user_id: 1,
  type: LeaveRequestType.Vacation,
  start_date: '2025-12-01',
  end_date: '2025-12-10',
  reason: 'Family vacation',
  status: LeaveRequestStatus.Pending,
  processed_by_manager_id: null,
  manager_comments: null,
  created_at: '2025-11-23T14:30:45.000000Z',
  updated_at: '2025-11-23T14:30:45.000000Z'
};

// API Response with pagination
const response: PaginatedResponse<User> = {
  data: [user],
  current_page: 1,
  last_page: 5,
  per_page: 15,
  total: 73,
  from: 1,
  to: 15,
  links: {
    first: 'https://api.example.com/users?page=1',
    last: 'https://api.example.com/users?page=5',
    prev: null,
    next: 'https://api.example.com/users?page=2'
  }
};
```

## ⚠️ Важливо: Формати дат

Різні Resources використовують **різні формати дат**:

| Resource                    | Date Format           | Example                       |
|-----------------------------|-----------------------|-------------------------------|
| `User`                      | `dd-mm-yyyy HH:mm:ss` | `23-11-2025 14:30:45`         |
| `Company`                   | `dd-mm-yyyy HH:mm:ss` | `23-11-2025 14:30:45`         |
| `AuthUser`                  | `dd-mm-yyyy HH:mm:ss` | `23-11-2025 14:30:45`         |
| `TimeEntry`                 | `YYYY-MM-DD HH:mm:ss` | `2025-11-23 14:30:45`         |
| `TimeEntryStart`            | `YYYY-MM-DD HH:mm:ss` | `2025-11-23 14:30:45`         |
| `TimeEntryStop`             | `YYYY-MM-DD HH:mm:ss` | `2025-11-23 14:30:45`         |
| `DailySchedule` (time only) | `HH:mm`               | `09:00`                       |
| `LeaveRequest` (date only)  | `YYYY-MM-DD`          | `2025-11-23`                  |
| `CompanyStoreResponse`      | ISO timestamp         | `2025-11-23T14:30:45.000000Z` |

## 🔄 Синхронізація з Laravel

Ці типи створені на основі Laravel Resources:

- ✅ `UserResource.php` → `user.ts`
- ✅ `AuthResource.php` → `user.ts` (AuthUser)
- ✅ `CompanyResource.php` → `company.ts`
- ✅ `CompanyStoreResource.php` → `company.ts` (CompanyStoreResponse)
- ✅ `TimeEntryResource.php` → `timeEntry.ts`
- ✅ `TimeEntryStartResource.php` → `timeEntry.ts` (TimeEntryStart)
- ✅ `TimeEntryStopResource.php` → `timeEntry.ts` (TimeEntryStop)
- ✅ `TimeEntrySummaryResource.php` → `timeEntry.ts` (TimeEntrySummary)
- ✅ `LeaveRequestResource.php` → `leaveRequest.ts`
- ✅ `WorkScheduleResource.php` → `workSchedule.ts`
- ✅ `DailyScheduleResource.php` → `workSchedule.ts` (DailySchedule)

## 📝 Enums

### UserRole

```typescript
enum UserRole {
  Admin = 'admin',
  Manager = 'manager',
  Employee = 'employee',
}
```

### LeaveRequestStatus

```typescript
enum LeaveRequestStatus {
  Pending = 'pending',
  Approved = 'approved',
  Rejected = 'rejected',
}
```

### LeaveRequestType

```typescript
enum LeaveRequestType {
  Sick = 'sick',
  Vacation = 'vacation',
  Personal = 'personal',
}
```

## 🔧 Оновлення типів

При зміні Laravel Resources необхідно оновити відповідні TypeScript типи:

1. Змінити Resource в Laravel
2. Оновити відповідний `.ts` файл
3. Переконатися, що формати дат співпадають
4. Перевірити nullable поля (`| null`)

## 📦 Загальні типи

### ApiResponse<T>

Обгортка для стандартних API відповідей:

```typescript
interface ApiResponse<T> {
  data: T;
  message?: string;
  success?: boolean;
}
```

### PaginatedResponse<T>

Пагінована відповідь від Laravel:

```typescript
interface PaginatedResponse<T> {
  data: T[];
  current_page: number;
  last_page: number;
  per_page: number;
  total: number;
  from: number | null;
  to: number | null;
  links: { ... };
}
```

### ApiError

Структура помилок:

```typescript
interface ApiError {
  message: string;
  errors?: Record<string, string[]>;
}
```

### AuthResponse

Відповідь при аутентифікації:

```typescript
interface AuthResponse {
  access_token: string;
  token_type: string;
  expires_in: number;
}
```

---

**Версія:** 1.0.0  
**Оновлено:** 23 листопада 2025 р.
