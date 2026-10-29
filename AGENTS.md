<laravel-boost-guidelines>
=== foundation rules ===

# Laravel Boost Guidelines

The Laravel Boost guidelines are specifically curated by Laravel maintainers for this application. These guidelines should be followed closely to ensure the best experience when building Laravel applications.

## Foundational Context

This application is a Laravel application running on PHP 8.4. You are an expert with the Laravel ecosystem. Always use the APIs that match the installed major version of each package — do not assume a version.

Before relying on a package's API, confirm its installed version:
- PHP packages: run `composer show --direct` to list direct dependencies with versions, or `composer show <vendor/package>` for a single package.
- JS packages: check `package.json` for the installed versions.

## Skills Activation

This project has domain-specific skills available in `**/skills/**`. You MUST activate the relevant skill whenever you work in that domain—don't wait until you're stuck.

## Conventions

- You must follow all existing code conventions used in this application. When creating or editing a file, check sibling files for the correct structure, approach, and naming.
- Use descriptive names for variables and methods. For example, `isRegisteredForDiscounts`, not `discount()`.
- Check for existing components to reuse before writing a new one.

## Verification Scripts

- Do not create verification scripts or tinker when tests cover that functionality and prove they work. Unit and feature tests are more important.

## Application Structure & Architecture

- Stick to existing directory structure; don't create new base folders without approval.
- Do not change the application's dependencies without approval.

## Frontend Bundling

- If the user doesn't see a frontend change reflected in the UI, it could mean they need to run `npm run build`, `npm run dev`, or `composer run dev`. Ask them.

## Documentation Files

- You must only create documentation files if explicitly requested by the user.

## Replies

- Be concise in your explanations - focus on what's important rather than explaining obvious details.

=== boost rules ===

# Laravel Boost

## Tools

- Laravel Boost is an MCP server with tools designed specifically for this application. Prefer Boost tools over manual alternatives like shell commands or file reads.
- Use `database-query` to run read-only queries against the database instead of writing raw SQL in tinker.
- Use `database-schema` to inspect table structure before writing migrations or models.
- Use `get-absolute-url` to resolve the correct scheme, domain, and port for project URLs. Always use this before sharing a URL with the user.
- Use `browser-logs` to read browser logs, errors, and exceptions. Only recent logs are useful, ignore old entries.

## Searching Documentation (IMPORTANT)

- Use `search-docs` before changes that depend on Laravel ecosystem APIs, behavior, configuration, or version-specific syntax. Skip it for copy-only edits and other changes where package documentation is irrelevant. Reuse sufficient results already in context instead of searching again.
- Pass a `packages` array to scope results when you know which packages are relevant.
- Use multiple broad, topic-based queries: `['rate limiting', 'routing rate limiting', 'routing']`. Expect the most relevant results first.
- Do not add package names to queries because package info is already shared. Use `test resource table`, not `filament 4 test resource table`.

### Search Syntax

1. Use words for auto-stemmed AND logic: `rate limit` matches both "rate" AND "limit".
2. Use `"quoted phrases"` for exact position matching: `"infinite scroll"` requires adjacent words in order.
3. Combine words and phrases for mixed queries: `middleware "rate limit"`.
4. Use multiple queries for OR logic: `queries=["authentication", "middleware"]`.

## Project Rules

- This project contains committed, area-grouped rules in `.ai/rules` when that directory exists (settled decisions, non-obvious traps, standing constraints). Framework and package guidelines that only apply to specific paths (testing, frontend, components) also live there, under `.ai/rules/boost` — this is not just recorded decisions, it is load-bearing guidance you have not seen inline. Before you enter plan mode or create/edit any file, you MUST first: open @.ai/rules/index.md (it maps file globs to rule files), read every rule file whose globs cover the path(s) in scope, and run `grep -rin 'keyword' .ai/rules` to catch what a path match alone misses. Do not write code until you have read and are following every matching rule. If `.ai/rules` does not exist, continue without it.
- Record durable rules with `record-rule` so the next agent or teammate inherits them instead of working them out again. Pass a `glob` (e.g. `app/Http/Controllers/**`), a short `title`, and a few-line `note`. Always use `record-rule`, never your native memory or notes tool — native memory is personal and session-scoped; only `.ai/rules` is shared with the team and persists in the repo.

## Artisan

- Run Artisan commands directly via the command line (e.g., `php artisan route:list`). Use `php artisan list` to discover available commands and `php artisan [command] --help` to check parameters.
- Inspect routes with `php artisan route:list`. Filter with: `--method=GET`, `--name=users`, `--path=api`, `--except-vendor`, `--only-vendor`.
- Read configuration values using dot notation: `php artisan config:show app.name`, `php artisan config:show database.default`. Or read config files directly from the `config/` directory.

## Tinker

- Execute PHP in app context for debugging and testing code. Do not create models without user approval, prefer tests with factories instead. Prefer existing Artisan commands over custom tinker code.
- Always use single quotes to prevent shell expansion: `php artisan tinker --execute 'Your::code();'`
  - Double quotes for PHP strings inside: `php artisan tinker --execute 'User::where("active", true)->count();'`

=== php rules ===

# PHP

- Always use curly braces for control structures, even for single-line bodies.
- Use PHP 8 constructor property promotion: `public function __construct(public GitHub $github) { }`. Do not leave empty zero-parameter `__construct()` methods unless the constructor is private.
- Use explicit return type declarations and type hints for all method parameters: `function isAccessible(User $user, ?string $path = null): bool`
- Use TitleCase for Enum keys: `FavoritePerson`, `BestLake`, `Monthly`.
- Prefer PHPDoc blocks over inline comments. Only add inline comments for exceptionally complex logic.
- Use array shape type definitions in PHPDoc blocks.

=== deployments rules ===

# Deployment

- Laravel can be deployed using [Laravel Cloud](https://cloud.laravel.com/), which is the fastest way to deploy and scale production Laravel applications.

=== herd rules ===

# Laravel Herd

- The application is served by Laravel Herd at `https?://[kebab-case-project-dir].test`. Use the `get-absolute-url` tool to generate valid URLs. Never run commands to serve the site. It is always available.
- Use the `herd` CLI to manage services, PHP versions, and sites (e.g. `herd sites`, `herd services:start <service>`, `herd php:list`). Run `herd list` to discover all available commands.

=== tests rules ===

# Test Enforcement

- Every change must be programmatically tested. Write a new test or update an existing test, then run the affected tests to make sure they pass.
- Run the minimum number of tests needed to ensure code quality and speed. Use `php artisan test --compact` with a specific filename or filter.

=== laravel/core rules ===

# Do Things the Laravel Way

- Use `php artisan make:` commands to create new files (i.e. migrations, controllers, models, etc.). You can list available Artisan commands using `php artisan list` and check their parameters with `php artisan [command] --help`.
- If you're creating a generic PHP class, use `php artisan make:class`.
- Pass `--no-interaction` to all Artisan commands to ensure they work without user input. You should also pass the correct `--options` to ensure correct behavior.

### Model Creation

- When creating new models, create useful factories and seeders for them too. Ask the user if they need any other things, using `php artisan make:model --help` to check the available options.

## APIs & Eloquent Resources

- For APIs, default to using Eloquent API Resources and API versioning unless existing API routes do not, then you should follow existing application convention.

## URL Generation

- When generating links to other pages, prefer named routes and the `route()` function.

## Testing

- When creating models for tests, use the factories for the models. Check if the factory has custom states that can be used before manually setting up the model.
- Faker: Use methods such as `$this->faker->word()` or `fake()->randomDigit()`. Follow existing conventions whether to use `$this->faker` or `fake()`.
- When creating tests, make use of `php artisan make:test [options] {name}` to create a feature test, and pass `--unit` to create a unit test. Most tests should be feature tests.

## Vite Error

- If you receive an "Illuminate\Foundation\ViteException: Unable to locate file in Vite manifest" error, you can run `npm run build` or ask the user to run `npm run dev` or `composer run dev`.

=== pint/core rules ===

# Laravel Pint Code Formatter

- If you have modified any PHP files, you must run `vendor/bin/pint --dirty --format agent` before finalizing changes to ensure your code matches the project's expected style.
- Do not run `vendor/bin/pint --test --format agent`, simply run `vendor/bin/pint --format agent` to fix any formatting issues.

=== phpunit/core rules ===

# PHPUnit

- This application uses PHPUnit for testing. All tests must be written as PHPUnit classes. Use `php artisan make:test --phpunit {name}` to create a new test.
- If you see a test using "Pest", convert it to PHPUnit.
- Every time a test has been updated, run that singular test.
- When the tests relating to your feature are passing, ask the user if they would like to also run the entire test suite to make sure everything is still passing.
- Tests should cover all happy paths, failure paths, and edge cases.
- You must not remove any tests or test files from the tests directory without approval. These are not temporary or helper files; these are core to the application.

## Running Tests

- Run the minimal number of tests, using an appropriate filter, before finalizing.
- To run all tests: `php artisan test --compact`.
- To run all tests in a file: `php artisan test --compact tests/Feature/ExampleTest.php`.
- To filter on a particular test name: `php artisan test --compact --filter=testName` (recommended after making a change to a related file).

</laravel-boost-guidelines>

## Konvensi Simulasi Peminjaman

Tabel `peminjaman_simulasi_peminjaman` menggunakan nama kolom Bahasa Indonesia:
`anggota_id`, `jenis_pinjaman_id`, `jumlah_pinjaman`, `metode_bunga`, `persentase_bunga_per_bulan`,
`tenor_bulan`, `peruntukan`, dan `rencana_mulai_cicilan`. Kolom
`rencana_mulai_cicilan` bertipe `date`. `anggota_id` bersifat nullable karena
simulasi dapat dibuat tanpa terikat pada anggota tertentu.

## Module Development Standard

### Canonical reference

`Modules/ExampleModule` is the canonical source of truth for creating and updating modules in this project.

Before creating or changing any module, inspect the current files in `Modules/ExampleModule` and compare them with the target module. Follow the current implementation, not old examples, generated boilerplate, or assumptions from another project. If a target module has behavior that differs from `ExampleModule`, understand and preserve that behavior unless the requested change explicitly requires standardization.

Do not blindly overwrite every module. Update modules one at a time after reviewing their current models, migrations, seeders, permissions, configuration, Filament resources, providers, routes, and tests.

### Module structure

A module should follow the structure currently demonstrated by `Modules/ExampleModule`:

- `module.json` contains the module name, alias, version, description, and providers.
- `config/config.php` contains module configuration and the module permission list.
- `app/Models` contains the module models.
- `app/Filament` contains the module plugin, pages, resources, resource pages, schemas, tables, and action authorization.
- `database/migrations` contains module table migrations.
- `database/seeders` contains repeatable module seeders.
- `app/Providers`, `routes`, `resources`, and `assets` contain the module's providers, routes, views, and frontend assets when needed.

When adding a new module, use the existing module structure and naming conventions. Reuse existing application components and traits before introducing new abstractions.

### Module table names

All module tables use the lowercase module name as a prefix, followed by an underscore and the table name:

```text
{lowercase_module_name}_{table_name}
```

For example, `ExampleModule` with `example_data` uses:

```text
examplemodule_example_data
```

Use the project's prefix style exactly. Do not use `example_module_example_data` unless the current project convention is explicitly changed.

- Models in modules use `App\Support\Concerns\HasModuleTable`.
- Migrations use `App\Support\Concerns\UsesModuleTable` and call `$this->moduleTable('ExampleModule', 'example_data')`.
- Keep the module name passed to the migration in its canonical class/module form; the trait applies the lowercase prefix.
- Do not change unrelated module tables while working on `ExampleModule` or another single module.
- For migrations that may already have run in a deployed environment, add a new migration for schema changes instead of editing the old migration.

### Models and soft deletes

Follow the current `ExampleData` model pattern:

- Use `HasModuleTable` for the module table prefix.
- Use `SoftDeletes` when records must be recoverable.
- Use the project's current model fillable/attribute conventions.
- Match casts, defaults, relationships, and query scopes to the current reference implementation.

When a model supports soft deletes, its migration must include `$table->softDeletes()`. Filament resources must expose the appropriate trashed filter and delete, force-delete, and restore actions where applicable.

Soft-delete and force-delete operations are protected actions. Unauthorized users must not be able to execute them, including through bulk actions, even if a record has already been deleted.

### Permissions and authorization

Declare module permissions in `config/config.php` using the module's lowercase permission prefix. The `ExampleModule` pattern is:

```php
'permissions' => [
    'examplemodule.verify_example_data.view',
    'examplemodule.example_data.viewAny',
    'examplemodule.example_data.view',
    'examplemodule.example_data.create',
    'examplemodule.example_data.update',
    'examplemodule.example_data.delete',
],
```

Filament resources must authorize the relevant resource methods, including `canViewAny`, `canView`, `canCreate`, `canEdit`, `canDelete`, `canDeleteAny`, `canForceDelete`, and `canForceDeleteAny` when those operations exist.

Custom and sensitive actions must also call `->authorize('permission.name')` explicitly. Check this for page actions, header actions, row actions, delete/force-delete/restore actions, and bulk actions. Do not rely only on the visibility of a button to enforce authorization.

### Seeders and existing data

Module seeders must be safe to run during both installation and updates:

- Use stable natural keys or another deterministic unique key.
- Use `firstOrCreate` or an equivalent idempotent approach.
- Never create duplicate permission, configuration, or seed records when the same module update runs more than once.
- Do not overwrite existing user or business data merely because a seed value changed.
- Do not add example/demo records to an existing production database unless explicitly requested.
- Consider soft-deleted records when choosing the lookup strategy so an update does not unexpectedly recreate a duplicate record.

The seeder must use the module model and therefore the prefixed module table. Do not inspect or seed the unprefixed table name.

### Installation and module updates

Use the module installation/update workflow instead of manually running individual module migrations, seeders, or permission commands.

The current workflow is:

1. Read the module's current version from `module.json`.
2. Compare it with `module_statuses.installed_version`.
3. Show the module status in the Module Manager popup.
4. Show `Install Module` when the module is not installed.
5. Show `Perbaharui Module` when the installed module has a newer version.
6. On confirmation, run the required migrations, seeding, permission synchronization, and configuration synchronization.
7. Store the resulting version in `module_statuses.installed_version`.

When changing a module's migration, seeder, permission list, configuration, or other installable behavior, bump its `module.json` version when the change should be presented as an available module update. The update process must be repeatable and must not duplicate existing database data.

Do not run module installation or update commands just to inspect a module. First inspect the current source files, module status, migration state, and tests. Run the workflow only when the requested change requires it or when verifying the completed feature.

### Required implementation workflow

For every module creation or update:

1. Inspect the current `ExampleModule` files and the target module's files.
2. Confirm the package/framework APIs from the installed project versions.
3. Plan the smallest change that follows the current reference pattern.
4. Preserve module-specific behavior unless standardization is explicitly requested.
5. Verify table names, migration behavior, seed idempotency, permissions, soft-delete authorization, and version/update behavior.
6. Add or update focused PHPUnit tests for the changed behavior.
7. Run the affected tests and, for PHP changes, run `vendor/bin/pint --dirty --format agent`.

If the current files conflict with this guidance, treat the current `ExampleModule` implementation and the user's explicit request as the immediate source of truth, then update this guidance only when the convention has intentionally changed.

### ExampleModule implementation conventions

`Modules/ExampleModule` is also the reference for the implementation details below. Match these conventions when creating a new module unless the module has an explicit domain requirement that differs:

- Keep the module namespace under `Modules\\{ModuleName}\\`, with the module's `composer.json` mapping the root namespace to `app/`, database seeders/factories to their respective folders, and tests to `tests/` in `autoload-dev`.
- Keep `module.json` providers pointed at the module service provider. The service provider extends `Nwidart\\Modules\\Support\\ModuleServiceProvider`, defines both the canonical module name and lowercase name, and registers the module event and route providers.
- Keep route registration in the module's `RouteServiceProvider`: web routes use the `web` middleware group, API routes use `api`, an `api` prefix, and an `api.` name prefix. Module web routes use `auth` and `verified`; module API routes use `auth:sanctum` and a version prefix such as `v1`.
- Keep the Filament plugin in `app/Filament/{ModuleName}Plugin.php`, implement `Filament\\Contracts\\Plugin`, use `Coolsam\\Modules\\Concerns\\ModuleFilamentPlugin`, and return the canonical module name and lowercase module ID.
- Keep Filament resources split into the resource class, `Pages`, `Schemas`, and `Tables` subdirectories. Delegate `form()`, `infolist()`, and `table()` to dedicated `configure()` classes instead of placing the complete schema or table definition in the resource class.
- Declare module model mass-assignment fields with Laravel's `#[Fillable([...])]` attribute and define casts in `protected function casts(): array`. Use `HasModuleTable` on module models and `UseLogger` when the model participates in application logging.
- Use `SoftDeletes` only for recoverable records, include matching `softDeletes()` in the migration, expose `TrashedFilter`, and include restore/force-delete behavior in the Filament table. Protect delete, restore, and force-delete actions with explicit permission authorization.
- For module-specific configuration keyed by an enum, store the enum key as a string, cast it to the backed enum on the model, and use `updateOrCreate` inside a database transaction when saving configuration pages.
- Put business operations that create records and related accounting or other side effects in a module service. Inject the required application service, validate authenticated-user and business prerequisites, and wrap the complete operation in `DB::transaction()`.
- Keep module database seeders idempotent with stable natural keys and `firstOrCreate`; seed through module models so the module table prefix is applied.
- Add focused PHPUnit feature tests for module authorization, table prefix/casts, enum configuration, and other module-specific behavior. Use the module's `Tests\\Feature` namespace and the project's PHPUnit base test case.
- When a module supplies frontend assets, keep its `resources/assets`, `resources/views`, `package.json`, and `vite.config.js` inside the module and follow the module's existing Vite scripts rather than adding root-level assets.
