# Repository Guidelines

## Project Structure & Module Organization
This repository is a Laravel 5.8 API focused on DIAN electronic documents.

- `app/`: core application code (controllers, requests, traits, models).
- `routes/api.php`: API surface (`/api/ubl2.1/*`).
- `resources/templates/xml/`: XML templates for invoice, payroll, support document, and health-sector extensions.
- `database/migrations`, `database/seeds`: schema and catalog/bootstrap data.
- `tests/Feature`, `tests/Unit`: PHPUnit suites.
- `public/csv/`: seed input catalogs (document types, taxes, currencies, etc.).

Keep business behavior changes close to module boundaries (e.g., invoice logic in `Api/InvoiceController` + request + XML template).

## Build, Test, and Development Commands
- `composer install`: install PHP dependencies.
- `php artisan key:generate`: create app key for local setup.
- `php artisan migrate --seed`: create schema and load catalog data.
- `php artisan serve`: run local API server.
- `./vendor/bin/phpunit` (or `php artisan test`): run test suite.
- `npm install`: install frontend build toolchain.
- `npm run dev`: compile assets for development.
- `npm run prod`: production asset build.

## Coding Style & Naming Conventions
- Follow Laravel/PHP conventions with 4-space indentation and PSR-style formatting.
- Class names: `PascalCase` (`InvoiceController`, `PayrollRequest`).
- Methods/variables: `camelCase`.
- Request payload keys and DIAN fields may remain existing `snake_case` (e.g., `healt_sector`, `type_document_id`) to preserve API compatibility.
- Prefer small, explicit methods; avoid embedding heavy business rules directly in route files.

## Testing Guidelines
- Framework: PHPUnit (`tests/Unit`, `tests/Feature`).
- Name tests by behavior: `it_generates_invoice_xml_with_health_extension`.
- Add/update tests for every change to:
  - endpoint contracts,
  - DIAN XML generation,
  - signing/sending error handling.
- Run full suite before opening PR.

## Commit & Pull Request Guidelines
Git history favors imperative subjects: `Add ...`, `Refactor ...`, `Fix ...`, `Remove ...`.

For each PR include:
- concise problem/solution summary,
- affected endpoints/files,
- sample request/response (or XML diff) for functional changes,
- test evidence (`phpunit` output),
- linked issue/ticket when applicable.

## Security & Configuration Tips
- Never commit real certificates, secrets, or production `.env` values.
- Validate file and path inputs defensively (especially XML retrieval/export flows).
- Use environment-specific DIAN endpoints and credentials per company/software configuration.
