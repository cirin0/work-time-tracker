# Work Time Tracker - AI Coding Instructions

## Project Overview

Work Time Tracker is a **Laravel 12 REST API** for employee time tracking with JWT authentication, WebSocket chat (Laravel Reverb), and role-based access control (Employee/Manager/Admin). Uses PostgreSQL and follows Repository-Service pattern.

## Key Dependencies

- **php**: ^8.2
- **laravel/framework**: ^12.48.1
- **JWT Auth**: php-open-source-saver/jwt-auth ^2.8.3
- **Reverb**: ^1.7.0 (WebSocket server)
- **Spatie Laravel Data**: ^4.19 (DTOs with TypeScript generation)
- **Scramble**: ^0.12.36 (OpenAPI docs)
- **Pest**: ^3.8.4 (testing framework)
- **Telescope**: ^5.16.1 (dev debugging)

## Architecture Pattern: Repository-Service-Controller

**CRITICAL**: Always follow this layered architecture for all features:

1. **Controllers** (`app/Http/Controllers/Api/`) - Handle HTTP, return resources
2. **Services** (`app/Services/`) - Business logic, return arrays with results
3. **Repositories** (`app/Repositories/`) - Data access only, no business logic
4. **Form Requests** (`app/Http/Requests/`) - Validation with custom messages
5. **Resources** (`app/Http/Resources/`) - Format API responses
6. **Data** (`app/Data/`) - DTOs using Spatie Laravel Data with `#[TypeScript]` attribute

### Example Flow (see [app/Http/Controllers/Api/TimeEntryController.php](app/Http/Controllers/Api/TimeEntryController.php)):

```php
// Controller injects service
public function __construct(protected TimeEntryService $timeEntryService) {}

// Service uses repository and returns array
public function startTimeEntry(User $user, array $data): array {
    $activeEntry = $this->timeEntryRepository->getActiveEntryForUser($user);
    return $activeEntry ? ['error' => true, 'message' => '...'] : ['time_entry' => $created];
}

// Repository only queries, no logic
public function getActiveEntryForUser(User $user): ?TimeEntry {
    return TimeEntry::query()->where('user_id', $user->id)->whereNull('stop_time')->first();
}
```

## Conventions

- Use descriptive names for variables and methods. For example, `isRegisteredForDiscounts`, not `discount()`.
- Check for existing components to reuse before writing a new one.

## Application Structure

- Stick to existing directory structure; don't create new base folders without approval.
- Do not change the application's dependencies without approval.

## Documentation Files

- **NEVER** create or update `.md` files (README.md, docs/, or any markdown files) unless explicitly requested
- Do NOT generate documentation, summaries, or change logs in markdown format
- After completing tasks, provide brief confirmations - do not create markdown files to document changes

## Replies

- Be concise in your explanations - focus on what's important rather than explaining obvious details.

## Authentication & Authorization

- **JWT Authentication**: Uses `php-open-source-saver/jwt-auth` (NOT Laravel Sanctum)
    - Auth guard: `auth:api` in [routes/api.php](routes/api.php)
    - User model implements `JWTSubject` with `getJWTIdentifier()` and `getJWTCustomClaims()`
- **Role-Based Access**: Custom `RoleMiddleware` in [app/Http/Middleware/RoleMiddleware.php](app/Http/Middleware/RoleMiddleware.php)
    - Usage: `->middleware('role:admin,manager')`
    - Checks `$user->role->value` against allowed roles (enum values)
- **Authorization Handling**: Services return `['error' => true, 'message' => '...']` for access denied
    - Controllers check for `isset($data['error'])` and return 403

## Validation Pattern

**ALL validation via Form Request classes** with both `rules()` and `messages()` methods:

```php
// app/Http/Requests/StoreTimeEntryRequest.php
public function rules(): array {
    return ['start_comment' => 'nullable|string|max:255'];
}
public function messages(): array {
    return ['start_comment.max' => 'The comment must not exceed 255 characters.'];
}
```

Check sibling Form Requests to determine if project uses array-based or string-based rule syntax.

## Data Transfer Objects (DTOs)

- Use **Spatie Laravel Data** for all DTOs in `app/Data/`
- Add `#[TypeScript('Name')]` attribute for frontend type generation
- Mark computed properties with `#[Computed]` attribute
- See [app/Data/TimeEntryData.php](app/Data/TimeEntryData.php) for example

## Models & Relationships

- Use `protected $fillable` (NOT `$guarded`)
- Always type-hint relationship return types: `public function company(): BelongsTo`
- Prefer `Model::query()` over `DB::` for queries
- Use proper Eloquent eager loading to avoid N+1 queries
- Enums for status fields (e.g., `UserRole`, `LeaveRequestStatus`)

## Testing with Pest

- Tests use **PHPUnit-style Pest** (class-based, NOT describe/it syntax)
- **Feature tests** for endpoints, **unit tests** for services/repositories
- Standard test setup pattern:
    ```php
    protected function setUp(): void {
        parent::setUp();
        $this->company = Company::factory()->create();
        $this->user = User::factory()->create(['company_id' => $this->company->id]);
        $this->actingAs($this->user, 'api'); // JWT auth guard
    }
    ```
- Always use factories for model creation in tests
- Test files: [tests/Feature/](tests/Feature/) organized by domain

## API Response Format

Controllers return JSON with consistent structure:

```php
return response()->json([
    'message' => 'Success message',
    'data' => new ResourceClass($result)
], 200);
```

For collections: `return ResourceClass::collection($items);`

## WebSocket / Real-time (Reverb)

- Private chat channels in [routes/channels.php](routes/channels.php): `chat.{userId}`
- Guard specification: `['guards' => ['api']]`
- Rate limiting on message endpoint: `->middleware('throttle:60,1')`

## Development Workflows

### Running the Application

```bash
# Start server + Reverb WebSocket (defined in composer.json)
composer run dev

# Or separately:
php artisan serve --host=0.0.0.0 --port=8000
php artisan reverb:start
```

### Testing

```bash
# Run all tests (defined in composer.json)
composer test

# Run specific test file
php artisan test --compact tests/Feature/TimeEntryTest.php

# Run with filter
php artisan test --compact --filter=test_name
```

### Code Quality

```bash
# Auto-fix code style (Laravel Pint)
vendor/bin/pint --dirty

# Static analysis (PHPStan) - Note: Currently has errors (see TODO.txt)
php -d memory_limit=512M vendor\bin\phpstan analyse
```

### Documentation

- **API Docs**: Auto-generated at `/docs/api` via Scramble
- **Postman Collection**: Auto-generated via `yasin_tgh/laravel-postman`

## Known Issues & TODOs

From [TODO.txt](TODO.txt):

- 7 tests currently failing - needs fixing
- PHPStan has errors to resolve
- Migrations need cleanup and consolidation
- TypeScript types in `resources/ts/api/generated.d.ts` may need Data class updates

## Configuration Notes

- **Middleware**: Registered in [bootstrap/app.php](bootstrap/app.php) via `->withMiddleware()`
- **Custom exceptions**: JWT token exceptions handled in `bootstrap/app.php`
- **Telescope**: Auto-discovery disabled (see `composer.json` extra section)

---

<laravel-boost-guidelines>
=== foundation rules ===

# Laravel Boost Guidelines

The Laravel Boost guidelines are specifically curated by Laravel maintainers for this application. These guidelines should be followed closely to ensure the best experience when building Laravel applications.

## Foundational Context

This application is a Laravel application and its main Laravel ecosystems package & versions are below. You are an expert with them all. Ensure you abide by these specific packages & versions.

- php - 8.4.7
- laravel/framework (LARAVEL) - v12
- laravel/prompts (PROMPTS) - v0
- laravel/reverb (REVERB) - v1
- laravel/sanctum (SANCTUM) - v4
- larastan/larastan (LARASTAN) - v3
- laravel/mcp (MCP) - v0
- laravel/pint (PINT) - v1
- laravel/sail (SAIL) - v1
- laravel/telescope (TELESCOPE) - v5
- pestphp/pest (PEST) - v3
- phpunit/phpunit (PHPUNIT) - v11

## Skills Activation

This project has domain-specific skills available. You MUST activate the relevant skill whenever you work in that domain—don't wait until you're stuck.

- `pest-testing` — Tests applications using the Pest 3 PHP framework. Activates when writing tests, creating unit or feature tests, adding assertions, testing Livewire components, architecture testing, debugging test failures, working with datasets or mocking; or when the user mentions test, spec, TDD, expects, assertion, coverage, or needs to verify functionality works.

## Conventions

- You must follow all existing code conventions used in this application. When creating or editing a file, check sibling files for the correct structure, approach, and naming.
- Use descriptive names for variables and methods. For example, `isRegisteredForDiscounts`, not `discount()`.
- Check for existing components to reuse before writing a new one.

## Verification Scripts

- Do not create verification scripts or tinker when tests cover that functionality and prove they work. Unit and feature tests are more important.

## Frontend Bundling

- If the user doesn't see a frontend change reflected in the UI, it could mean they need to run `npm run build`, `npm run dev`, or `composer run dev`. Ask them.

=== boost rules ===

# Laravel Boost

- Laravel Boost is an MCP server that comes with powerful tools designed specifically for this application. Use them.

## Artisan

- Use the `list-artisan-commands` tool when you need to call an Artisan command to double-check the available parameters.

## URLs

- Whenever you share a project URL with the user, you should use the `get-absolute-url` tool to ensure you're using the correct scheme, domain/IP, and port.

## Tinker / Debugging

- You should use the `tinker` tool when you need to execute PHP to debug code or query Eloquent models directly.
- Use the `database-query` tool when you only need to read from the database.

## Reading Browser Logs With the `browser-logs` Tool

- You can read browser logs, errors, and exceptions using the `browser-logs` tool from Boost.
- Only recent browser logs will be useful - ignore old logs.

## Searching Documentation (Critically Important)

- Boost comes with a powerful `search-docs` tool you should use before trying other approaches when working with Laravel or Laravel ecosystem packages. This tool automatically passes a list of installed packages and their versions to the remote Boost API, so it returns only version-specific documentation for the user's circumstance. You should pass an array of packages to filter on if you know you need docs for particular packages.
- Search the documentation before making code changes to ensure we are taking the correct approach.
- Use multiple, broad, simple, topic-based queries at once. For example: `['rate limiting', 'routing rate limiting', 'routing']`. The most relevant results will be returned first.
- Do not add package names to queries; package information is already shared. For example, use `test resource table`, not `filament 4 test resource table`.

### Available Search Syntax

1. Simple Word Searches with auto-stemming - query=authentication - finds 'authenticate' and 'auth'.
2. Multiple Words (AND Logic) - query=rate limit - finds knowledge containing both "rate" AND "limit".
3. Quoted Phrases (Exact Position) - query="infinite scroll" - words must be adjacent and in that order.
4. Mixed Queries - query=middleware "rate limit" - "middleware" AND exact phrase "rate limit".
5. Multiple Queries - queries=["authentication", "middleware"] - ANY of these terms.

=== php rules ===

# PHP

- Always use curly braces for control structures, even for single-line bodies.

## Constructors

- Use PHP 8 constructor property promotion in `__construct()`.
    - <code-snippet>public function \_\_construct(public GitHub $github) { }</code-snippet>
- Do not allow empty `__construct()` methods with zero parameters unless the constructor is private.

## Type Declarations

- Always use explicit return type declarations for methods and functions.
- Use appropriate PHP type hints for method parameters.

<code-snippet name="Explicit Return Types and Method Params" lang="php">
protected function isAccessible(User $user, ?string $path = null): bool
{
    ...
}
</code-snippet>

## Enums

- Typically, keys in an Enum should be TitleCase. For example: `FavoritePerson`, `BestLake`, `Monthly`.

## Comments

- Prefer PHPDoc blocks over inline comments. Never use comments within the code itself unless the logic is exceptionally complex.

## PHPDoc Blocks

- Add useful array shape type definitions when appropriate.

=== tests rules ===

# Test Enforcement

- Every change must be programmatically tested. Write a new test or update an existing test, then run the affected tests to make sure they pass.
- Run the minimum number of tests needed to ensure code quality and speed. Use `php artisan test --compact` with a specific filename or filter.

=== laravel/core rules ===

# Do Things the Laravel Way

- Use `php artisan make:` commands to create new files (i.e. migrations, controllers, models, etc.). You can list available Artisan commands using the `list-artisan-commands` tool.
- If you're creating a generic PHP class, use `php artisan make:class`.
- Pass `--no-interaction` to all Artisan commands to ensure they work without user input. You should also pass the correct `--options` to ensure correct behavior.

## Database

- Always use proper Eloquent relationship methods with return type hints. Prefer relationship methods over raw queries or manual joins.
- Use Eloquent models and relationships before suggesting raw database queries.
- Avoid `DB::`; prefer `Model::query()`. Generate code that leverages Laravel's ORM capabilities rather than bypassing them.
- Generate code that prevents N+1 query problems by using eager loading.
- Use Laravel's query builder for very complex database operations.

### Model Creation

- When creating new models, create useful factories and seeders for them too. Ask the user if they need any other things, using `list-artisan-commands` to check the available options to `php artisan make:model`.

### APIs & Eloquent Resources

- For APIs, default to using Eloquent API Resources and API versioning unless existing API routes do not, then you should follow existing application convention.

## Controllers & Validation

- Always create Form Request classes for validation rather than inline validation in controllers. Include both validation rules and custom error messages.
- Check sibling Form Requests to see if the application uses array or string based validation rules.

## Authentication & Authorization

- Use Laravel's built-in authentication and authorization features (gates, policies, Sanctum, etc.).

## URL Generation

- When generating links to other pages, prefer named routes and the `route()` function.

## Queues

- Use queued jobs for time-consuming operations with the `ShouldQueue` interface.

## Configuration

- Use environment variables only in configuration files - never use the `env()` function directly outside of config files. Always use `config('app.name')`, not `env('APP_NAME')`.

## Testing

- When creating models for tests, use the factories for the models. Check if the factory has custom states that can be used before manually setting up the model.
- Faker: Use methods such as `$this->faker->word()` or `fake()->randomDigit()`. Follow existing conventions whether to use `$this->faker` or `fake()`.
- When creating tests, make use of `php artisan make:test [options] {name}` to create a feature test, and pass `--unit` to create a unit test. Most tests should be feature tests.

## Vite Error

- If you receive an "Illuminate\Foundation\ViteException: Unable to locate file in Vite manifest" error, you can run `npm run build` or ask the user to run `npm run dev` or `composer run dev`.

=== laravel/v12 rules ===

# Laravel 12

- CRITICAL: ALWAYS use `search-docs` tool for version-specific Laravel documentation and updated code examples.
- Since Laravel 11, Laravel has a new streamlined file structure which this project uses.

## Laravel 12 Structure

- In Laravel 12, middleware are no longer registered in `app/Http/Kernel.php`.
- Middleware are configured declaratively in `bootstrap/app.php` using `Application::configure()->withMiddleware()`.
- `bootstrap/app.php` is the file to register middleware, exceptions, and routing files.
- `bootstrap/providers.php` contains application specific service providers.
- The `app\Console\Kernel.php` file no longer exists; use `bootstrap/app.php` or `routes/console.php` for console configuration.
- Console commands in `app/Console/Commands/` are automatically available and do not require manual registration.

## Database

- When modifying a column, the migration must include all of the attributes that were previously defined on the column. Otherwise, they will be dropped and lost.
- Laravel 12 allows limiting eagerly loaded records natively, without external packages: `$query->latest()->limit(10);`.

### Models

- Casts can and likely should be set in a `casts()` method on a model rather than the `$casts` property. Follow existing conventions from other models.

=== pint/core rules ===

# Laravel Pint Code Formatter

- You must run `vendor/bin/pint --dirty` before finalizing changes to ensure your code matches the project's expected style.
- Do not run `vendor/bin/pint --test`, simply run `vendor/bin/pint` to fix any formatting issues.

=== pest/core rules ===

## Pest

- This project uses Pest for testing. Create tests: `php artisan make:test --pest {name}`.
- Run tests: `php artisan test --compact` or filter: `php artisan test --compact --filter=testName`.
- Do NOT delete tests without approval.
- CRITICAL: ALWAYS use `search-docs` tool for version-specific Pest documentation and updated code examples.
- IMPORTANT: Activate `pest-testing` every time you're working with a Pest or testing-related task.
  </laravel-boost-guidelines>
