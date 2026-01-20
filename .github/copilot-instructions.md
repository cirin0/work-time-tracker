<laravel-boost-guidelines>
=== foundation rules ===

# Laravel Boost Guidelines

The Laravel Boost guidelines are specifically curated by Laravel maintainers for this application. These guidelines
should be followed closely to enhance the user's satisfaction building Laravel applications.

## Foundational Context

This application is a **RESTful API backend** built with Laravel 12. There is NO frontend - this is a pure API
application with comprehensive API documentation via Scramble.

### Main Technologies & Versions

- php - ^8.2 (development: 8.4.1)
- laravel/framework (LARAVEL) - ^12.47.0
- postgresql - Primary database
- php-open-source-saver/jwt-auth - ^2.8.3 (JWT authentication)
- laravel/reverb (REVERB) - ^1.7.0 (WebSocket server)
- laravel/sanctum (SANCTUM) - ^4.2.3 (backup auth option)
- dedoc/scramble - ^0.12.36 (OpenAPI documentation)
- laravel/telescope - ^5.16.1 (development monitoring)
- laravel/pint (PINT) - ^1.27.0
- laravel/sail (SAIL) - ^1.52
- pestphp/pest (PEST) - ^3.8.4
- phpunit/phpunit (PHPUNIT) - v11
- yasin_tgh/laravel-postman - ^1.4.5 (Postman collection generation)

### Application Type & Architecture

- **Pure API Backend** - No views, no Blade, no Inertia, no frontend assets
- **Repository-Service Pattern** - Controllers → Services → Repositories → Models
- **JWT Authentication** - Token-based auth (php-open-source-saver/jwt-auth)
- **Role-Based Access Control** - Employee, Manager, Admin roles
- **WebSocket Support** - Real-time messaging via Laravel Reverb
- **Comprehensive Testing** - Pest-based feature and unit tests

## Conventions

- You must follow all existing code conventions used in this application. When creating or editing a file, check sibling
  files for the correct structure, approach, naming.
- Use descriptive names for variables and methods. For example, `isRegisteredForDiscounts`, not `discount()`.
- Check for existing components to reuse before writing a new one.

## Verification Scripts

- Do not create verification scripts or tinker when tests cover that functionality and prove it works. Unit and feature
  tests are more important.

## Application Structure & Architecture

### Design Pattern: Repository-Service Pattern

- **Controllers** (app/Http/Controllers/Api/) - Handle HTTP requests, return JSON responses
- **Services** (app/Services/) - Business logic layer
- **Repositories** (app/Repositories/) - Data access layer, Eloquent queries
- **Resources** (app/Http/Resources/) - Transform models to JSON API responses
- **Form Requests** (app/Http/Requests/) - Validation rules and authorization

### Key Directories

```
app/
├── Classes/              # Helper classes (e.g., ApiResponseClass)
├── Enums/               # UserRole enum
├── Events/              # Laravel events (MessageSent)
├── Http/
│   ├── Controllers/Api/ # All API controllers
│   │   └── Manager/    # Manager-specific controllers
│   ├── Middleware/     # Custom middleware (role-based access)
│   ├── Requests/       # Form Request validation classes
│   └── Resources/      # API Resource transformers
├── Models/             # Eloquent models
├── Repositories/       # Repository pattern implementation
├── Services/          # Service layer with business logic
└── Providers/         # Service Providers (including RepositoryServiceProvider)
```

### Models & Relationships

1. **User** - hasMany TimeEntry, hasOne WorkSchedule, belongsTo Company, role enum
2. **Company** - hasMany Users
3. **TimeEntry** - belongsTo User, tracks clock-in/clock-out
4. **WorkSchedule** - belongsTo User, hasMany DailySchedule
5. **DailySchedule** - belongsTo WorkSchedule
6. **LeaveRequest** - belongsTo User, approval workflow
7. **Message** - polymorphic for real-time chat

### Authentication & Authorization

- **JWT Auth** via php-open-source-saver/jwt-auth (primary)
- **Sanctum** available as backup
- **UserRole Enum**: Employee, Manager, Admin
- **Middleware**: `auth:api`, custom `role:manager`, `role:admin`
- Route groups organized by auth requirements

### API Response Format

- Use `ApiResponseClass` for consistent JSON responses
- Eloquent API Resources for data transformation
- Standard HTTP status codes
- Error handling with proper JSON error responses

### Rules

- Stick to existing directory structure - don't create new base folders without approval
- Do not change the application's dependencies without approval
- Always follow Repository-Service pattern when adding new features
- Controllers should be thin - delegate logic to Services
- Services use Repositories for data access - never query directly from Services

## API-Only Application

- This is a **backend API only** - there are NO frontend assets, views, or UI components.
- Do NOT suggest npm commands, Vite, or frontend-related solutions.
- API documentation is auto-generated at `/docs/api` via Scramble.
- Test API endpoints using Postman collections or the Scramble UI.

## Replies

- Be concise in your explanations - focus on what's important rather than explaining obvious details.

## Documentation Files

- You must only create documentation files if explicitly requested by the user.

=== boost rules ===

## Laravel Boost

- Laravel Boost is an MCP server that comes with powerful tools designed specifically for this application. Use them.

## Artisan

- Use the `list-artisan-commands` tool when you need to call an Artisan command to double check the available
  parameters.

## URLs

- Whenever you share a project URL with the user you should use the `get-absolute-url` tool to ensure you're using the
  correct scheme, domain / IP, and port.

## Tinker / Debugging

- You should use the `tinker` tool when you need to execute PHP to debug code or query Eloquent models directly.
- Use the `database-query` tool when you only need to read from the database.

## Reading Browser Logs With the `browser-logs` Tool

- You can read browser logs, errors, and exceptions using the `browser-logs` tool from Boost.
- Only recent browser logs will be useful - ignore old logs.

## Searching Documentation (Critically Important)

- Boost comes with a powerful `search-docs` tool you should use before any other approaches. This tool automatically
  passes a list of installed packages and their versions to the remote Boost API, so it returns only version-specific
  documentation specific for the user's circumstance. You should pass an array of packages to filter on if you know you
  need docs for particular packages.
- The 'search-docs' tool is perfect for all Laravel related packages, including Laravel, Inertia, Livewire, Filament,
  Tailwind, Pest, Nova, Nightwatch, etc.
- You must use this tool to search for Laravel-ecosystem documentation before falling back to other approaches.
- Search the documentation before making code changes to ensure we are taking the correct approach.
- Use multiple, broad, simple, topic based queries to start. For example:
  `['rate limiting', 'routing rate limiting', 'routing']`.
- Do not add package names to queries - package information is already shared. For example, use `test resource table`,
  not `filament 4 test resource table`.

### Available Search Syntax

- You can and should pass multiple queries at once. The most relevant results will be returned first.

1. Simple Word Searches with auto-stemming - query=authentication - finds 'authenticate' and 'auth'
2. Multiple Words (AND Logic) - query=rate limit - finds knowledge containing both "rate" AND "limit"
3. Quoted Phrases (Exact Position) - query="infinite scroll" - Words must be adjacent and in that order
4. Mixed Queries - query=middleware "rate limit" - "middleware" AND exact phrase "rate limit"
5. Multiple Queries - queries=["authentication", "middleware"] - ANY of these terms

=== php rules ===

## PHP

- Always use curly braces for control structures, even if it has one line.

### Constructors

- Use PHP 8 constructor property promotion in `__construct()`.
    - <code-snippet>public function \_\_construct(public GitHub $github) { }</code-snippet>
- Do not allow empty `__construct()` methods with zero parameters.

### Type Declarations

- Always use explicit return type declarations for methods and functions.
- Use appropriate PHP type hints for method parameters.

<code-snippet name="Explicit Return Types and Method Params" lang="php">
protected function isAccessible(User $user, ?string $path = null): bool
{
    ...
}
</code-snippet>

## Comments

- Prefer PHPDoc blocks over comments. Never use comments within the code itself unless there is something _very_ complex
  going on.

## PHPDoc Blocks

- Add useful array shape type definitions for arrays when appropriate.

## Enums

- Typically, keys in an Enum should be TitleCase. For example: `FavoritePerson`, `BestLake`, `Monthly`.

=== laravel/core rules ===

## Do Things the Laravel Way

- Use `php artisan make:` commands to create new files (i.e. migrations, controllers, models, etc.). You can list
  available Artisan commands using the `list-artisan-commands` tool.
- If you're creating a generic PHP class, use `artisan make:class`.
- Pass `--no-interaction` to all Artisan commands to ensure they work without user input. You should also pass the
  correct `--options` to ensure correct behavior.

### Database

- Always use proper Eloquent relationship methods with return type hints. Prefer relationship methods over raw queries
  or manual joins.
- Use Eloquent models and relationships before suggesting raw database queries
- Avoid `DB::`; prefer `Model::query()`. Generate code that leverages Laravel's ORM capabilities rather than bypassing
  them.
- Generate code that prevents N+1 query problems by using eager loading.
- Use Laravel's query builder for very complex database operations.

### Model Creation

- When creating new models, create useful factories and seeders for them too. Ask the user if they need any other
  things, using `list-artisan-commands` to check the available options to `php artisan make:model`.

### APIs & Eloquent Resources

- For APIs, default to using Eloquent API Resources and API versioning unless existing API routes do not, then you
  should follow existing application convention.

### Controllers & Validation

- Always create Form Request classes for validation rather than inline validation in controllers. Include both
  validation rules and custom error messages.
- Check sibling Form Requests to see if the application uses array or string based validation rules.

### Queues

- Use queued jobs for time-consuming operations with the `ShouldQueue` interface.

### Authentication & Authorization

- Use Laravel's built-in authentication and authorization features (gates, policies, Sanctum, etc.).

### URL Generation

- When generating links to other pages, prefer named routes and the `route()` function.

### Configuration

- Use environment variables only in configuration files - never use the `env()` function directly outside of config
  files. Always use `config('app.name')`, not `env('APP_NAME')`.

### Testing

- When creating models for tests, use the factories for the models. Check if the factory has custom states that can be
  used before manually setting up the model.
- Faker: Use methods such as `$this->faker->word()` or `fake()->randomDigit()`. Follow existing conventions whether to
  use `$this->faker` or `fake()`.
- When creating tests, make use of `php artisan make:test [options] <name>` to create a feature test, and pass `--unit`
  to create a unit test. Most tests should be feature tests.

### No Frontend / Vite

- This application has NO frontend assets or Vite configuration
- Do NOT suggest any npm, Vite, or frontend build commands
- API documentation is handled by Scramble at `/docs/api`

=== laravel/v12 rules ===

## Laravel 12

- Use the `search-docs` tool to get version specific documentation.
- Since Laravel 11, Laravel has a new streamlined file structure which this project uses.

### Laravel 12 Structure

- No middleware files in `app/Http/Middleware/`.
- `bootstrap/app.php` is the file to register middleware, exceptions, and routing files.
- `bootstrap/providers.php` contains application specific service providers.
- **No app\Console\Kernel.php** - use `bootstrap/app.php` or `routes/console.php` for console configuration.
- **Commands auto-register** - files in `app/Console/Commands/` are automatically available and do not require manual
  registration.

### Database

- When modifying a column, the migration must include all of the attributes that were previously defined on the column.
  Otherwise, they will be dropped and lost.
- Laravel 11 allows limiting eagerly loaded records natively, without external packages: `$query->latest()->limit(10);`.

### Models

- Casts can and likely should be set in a `casts()` method on a model rather than the `$casts` property. Follow existing
  conventions from other models.

=== pint/core rules ===

## Laravel Pint Code Formatter

- You must run `vendor/bin/pint --dirty` before finalizing changes to ensure your code matches the project's expected
  style.
- Do not run `vendor/bin/pint --test`, simply run `vendor/bin/pint` to fix any formatting issues.

=== pest/core rules ===

## Pest

### Testing

- If you need to verify a feature is working, write or update a Unit / Feature test.

### Pest Tests

- All tests must be written using Pest. Use `php artisan make:test --pest <name>`.
- You must not remove any tests or test files from the tests directory without approval. These are not temporary or
  helper files - these are core to the application.
- Tests should test all of the happy paths, failure paths, and weird paths.
- Tests live in the `tests/Feature` and `tests/Unit` directories.
- Pest tests look and behave like this:
  <code-snippet name="Basic Pest Test Example" lang="php">
  it('is true', function () {
  expect(true)->toBeTrue();
  });
  </code-snippet>

### Running Tests

- Run the minimal number of tests using an appropriate filter before finalizing code edits.
- To run all tests: `php artisan test`.
- To run all tests in a file: `php artisan test tests/Feature/ExampleTest.php`.
- To filter on a particular test name: `php artisan test --filter=testName` (recommended after making a change to a
  related file).
- When the tests relating to your changes are passing, ask the user if they would like to run the entire test suite to
  ensure everything is still passing.

### Pest Assertions

- When asserting status codes on a response, use the specific method like `assertForbidden` and `assertNotFound` instead
  of using `assertStatus(403)` or similar, e.g.:
  <code-snippet name="Pest Example Asserting postJson Response" lang="php">
  it('returns all', function () {
  $response = $this->postJson('/api/docs', []);

    $response->assertSuccessful();
    });
    </code-snippet>

### Mocking

- Mocking can be very helpful when appropriate.
- When mocking, you can use the `Pest\Laravel\mock` Pest function, but always import it via
  `use function Pest\Laravel\mock;` before using it. Alternatively, you can use `$this->mock()` if existing tests do.
- You can also create partial mocks using the same import or self method.

### Datasets

- Use datasets in Pest to simplify tests which have a lot of duplicated data. This is often the case when testing
  validation rules, so consider going with this solution when writing tests for validation rules.

<code-snippet name="Pest Dataset Example" lang="php">
it('has emails', function (string $email) {
    expect($email)->not->toBeEmpty();
})->with([
    'james' => 'james@laravel.com',
    'taylor' => 'taylor@laravel.com',
]);
</code-snippet>
</laravel-boost-guidelines>
