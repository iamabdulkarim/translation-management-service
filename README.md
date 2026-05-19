# Translation Management Service

API-driven translation management service built for the Laravel Senior Developer code test.

The primary deliverable is the Laravel backend in `backend/`. A React/Vite frontend is included only as an optional API demonstration client.

## Features

- Store translations for multiple locales such as `en`, `fr`, and `es`
- Add new locales without schema changes
- Tag translation keys by context, for example `mobile`, `desktop`, and `web`
- Create, update, view, delete, and search translations
- Search by locale, tag, key, content, and generic query text
- Streamed JSON export endpoint for frontend applications
- First-party bearer-token authentication with token abilities
- Paginated normal API endpoints
- 100k+ record population command for scalability testing
- MySQL setup
- Docker setup
- OpenAPI documentation
- Unit, feature, command, and performance-oriented tests
- Test coverage above 95%

## Project Structure

```text
TranslationManagementService/
|-- backend/              Laravel API backend
|-- frontend/             Optional React/Vite demo console
|-- docker-compose.yml    Optional Docker stack
`-- README.md             Main evaluator guide
```

Backend documentation: `backend/README.md`  
OpenAPI spec: `backend/docs/openapi.yaml`

## Requirements

- PHP 8.3+
- Composer
- MySQL 8 or MariaDB
- Node.js 20+ only if running the optional frontend
- Docker only if using the optional Docker setup

## Backend Setup Without Docker

Run these commands from the project root.

```bash
cd backend
composer install
cp .env.example .env
php artisan key:generate
```

Configure MySQL in `backend/.env`:

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=translation_management_service
DB_USERNAME=root
DB_PASSWORD=
```

Create the database if it does not exist:

```bash
mysql -uroot -e "CREATE DATABASE IF NOT EXISTS translation_management_service CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
```

Run migrations and seed the default user/locales/tags:

```bash
php artisan migrate --seed
```

Start the backend API:

```bash
php artisan serve --host=127.0.0.1 --port=8000
```

Backend URL:

```text
http://127.0.0.1:8000
```

API base URL:

```text
http://127.0.0.1:8000/api/v1
```

Default seeded user:

```text
Email: test@example.com
Password: password
```

## First API Test

Issue an API token:

```bash
curl -X POST http://127.0.0.1:8000/api/v1/auth/login \
  -H "Content-Type: application/json" \
  -d "{\"email\":\"test@example.com\",\"password\":\"password\",\"token_name\":\"local-test\",\"abilities\":[\"*\"]}"
```

Copy the returned `plain_text_token`, then call a protected endpoint:

```bash
curl http://127.0.0.1:8000/api/v1/translations \
  -H "Authorization: Bearer YOUR_TOKEN"
```

Create a translation:

```bash
curl -X POST http://127.0.0.1:8000/api/v1/translations \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -d "{\"key\":\"home.hero.title\",\"locale\":\"en\",\"locale_name\":\"English\",\"value\":\"Welcome back\",\"tags\":[\"web\",\"mobile\"]}"
```

Search translations:

```bash
curl "http://127.0.0.1:8000/api/v1/translations/search?locale=en&tag=web&key=home" \
  -H "Authorization: Bearer YOUR_TOKEN"
```

Export frontend JSON:

```bash
curl http://127.0.0.1:8000/api/v1/translations/export/en \
  -H "Authorization: Bearer YOUR_TOKEN"
```

## Main Backend Endpoints

- `GET /api/v1/health`
- `POST /api/v1/auth/login`
- `POST /api/v1/auth/logout`
- `GET /api/v1/auth/tokens`
- `DELETE /api/v1/auth/tokens/{id}`
- `GET /api/v1/me`
- `GET /api/v1/translations`
- `POST /api/v1/translations`
- `GET /api/v1/translations/search`
- `GET /api/v1/translations/export/{locale}`
- `GET /api/v1/translations/{id}`
- `PATCH /api/v1/translations/{id}`
- `DELETE /api/v1/translations/{id}`

All normal list endpoints are paginated. Use `per_page` up to `100`.

## Testing

From `backend/`:

```bash
php artisan test
vendor/bin/pint --test
```

Coverage:

```bash
php artisan test --coverage
```

Latest local coverage result:

```text
53 tests passed
214 assertions
97.7% line coverage
```

The test suite includes:

- Unit tests
- Feature/API tests
- Authentication and authorization tests
- CRUD tests
- Search tests
- Export tests
- Command tests
- Performance-oriented tests
- Error-handling tests

## 100k+ Scalability Test

From `backend/`:

```bash
php artisan translations:populate 100000 --chunk=1000
```

Optional locales and tags:

```bash
php artisan translations:populate 100000 --locales=en,fr,es --tags=mobile,desktop,web
```

Then test the export endpoint:

```bash
curl http://127.0.0.1:8000/api/v1/translations/export/en \
  -H "Authorization: Bearer YOUR_TOKEN"
```

## Optional Frontend Demo

The frontend is not required for the backend code test, but it can be used to test the API visually.

From the project root:

```bash
cd frontend
npm install
```

Ensure `frontend/.env` contains:

```env
VITE_API_BASE_URL=http://localhost:8000/api
```

Run it:

```bash
npm run dev -- --host 127.0.0.1 --port 5173
```

Frontend URL:

```text
http://127.0.0.1:5173
```

## Optional Docker Setup

From the project root:

```bash
docker compose up --build
```

Docker services:

```text
API:      http://localhost:8000
Frontend: http://localhost:5173
MySQL:    127.0.0.1:3306
Database: translation_management_service
Username: root
Password: empty
```

Run backend tests inside Docker:

```bash
docker compose exec backend php artisan test
docker compose exec backend php artisan test --coverage
```

Populate data inside Docker:

```bash
docker compose exec backend php artisan translations:populate 100000 --chunk=1000
```

## Design Choices

- The schema separates `locales`, `translation_keys`, `translations`, `tags`, and `translation_key_tag`.
- `translation_key_id + locale_id` is unique to prevent duplicate translations for the same key and locale.
- Indexes support lookup by locale, key, publication state, tag pivot, and hash.
- Controllers are thin and delegate validation, persistence, search, and response formatting.
- Form Request classes handle validation.
- Services own business operations, transactions, and logging.
- Repositories own database write details.
- Query/export classes own read-heavy operations.
- Resources format API responses.
- Normal list endpoints use pagination to avoid huge responses.
- The export endpoint streams JSON to reduce memory pressure on large datasets.
- API tokens are stored as SHA-256 hashes and can be ability-scoped and revoked.
- Write operations use `DB::transaction(..., 3)` where consistency matters.
- Structured logs capture success, validation/security rejections, and failures.
- API responses use a consistent `success`, `message`, `data` or `errors` shape.
- Export responses use `Cache-Control: no-store` so frontend applications always receive fresh translations.

## Troubleshooting

If MySQL connection fails, confirm MySQL is running and the database exists:

```bash
mysql -uroot -e "SHOW DATABASES;"
```

If tests are slow under coverage, that is expected because Xdebug instruments execution. Performance timing assertions are active during normal tests and skipped only during coverage mode.

If frontend requests fail, confirm:

- Backend is running on `http://127.0.0.1:8000`
- `frontend/.env` uses `VITE_API_BASE_URL=http://localhost:8000/api`
- You have logged in and are using a valid token
