# Translation Management Service

Laravel Senior Developer code-test project for an API-driven translation management service.

The **main deliverable is the Laravel backend** in `backend/`. The React frontend in `frontend/` is optional and is included only as a small visual API client.

## What This Project Includes

- Laravel API backend
- MySQL database support
- Token-based API authentication
- Translation CRUD
- Search by locale, tag, key, content, and query text
- Context tags such as `mobile`, `desktop`, and `web`
- Streamed JSON export endpoint for frontend applications
- 100k+ record population command
- Pagination for normal list endpoints
- Service/repository/query/resource architecture
- OpenAPI documentation
- Docker setup
- Test coverage above 95%
- Optional React/Vite frontend console

## Recommended Review Path

For the code test, review and run the project in this order:

1. Set up the backend with MySQL.
2. Run migrations and seeders.
3. Start the Laravel API.
4. Generate an API token.
5. Test CRUD/search/export endpoints.
6. Run the automated tests.
7. Run the coverage command.
8. Run the 100k scalability command.
9. Optionally run Docker.
10. Optionally run the React frontend.

Detailed commands are below.

## Project Structure

```text
TranslationManagementService/
|-- backend/              Laravel API backend - primary deliverable
|-- frontend/             Optional React/Vite frontend console
|-- docker-compose.yml    Optional Docker stack
`-- README.md             Main setup and evaluation guide
```

Additional backend documentation:

```text
backend/README.md
backend/docs/openapi.yaml
```

## Requirements

Backend:

- PHP 8.3+
- Composer
- MySQL 8 or MariaDB

Optional frontend:

- Node.js 20+
- npm

Optional Docker:

- Docker
- Docker Compose

## 1. Backend Setup With MySQL

From the project root:

```bash
cd backend
composer install
cp .env.example .env
php artisan key:generate
```

Set the database values in `backend/.env`:

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=translation_management_service
DB_USERNAME=root
DB_PASSWORD=
```

Create the database:

```bash
mysql -uroot -e "CREATE DATABASE IF NOT EXISTS translation_management_service CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
```

Run migrations and seeders:

```bash
php artisan migrate --seed
```

This seeds:

- default user
- default locales: `en`, `fr`, `es`
- default tags: `mobile`, `desktop`, `web`

Default login:

```text
Email: test@example.com
Password: password
```

## 2. Run The Backend API

From `backend/`:

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

Health check:

```bash
curl http://127.0.0.1:8000/api/v1/health
```

## 3. Generate An API Token

```bash
curl -X POST http://127.0.0.1:8000/api/v1/auth/login \
  -H "Content-Type: application/json" \
  -d "{\"email\":\"test@example.com\",\"password\":\"password\",\"token_name\":\"review-token\",\"abilities\":[\"*\"]}"
```

Copy the returned `plain_text_token`. Use it as `YOUR_TOKEN` in the next commands.

## 4. Test Core API Endpoints Manually

List translations:

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

Export JSON for frontend apps:

```bash
curl http://127.0.0.1:8000/api/v1/translations/export/en \
  -H "Authorization: Bearer YOUR_TOKEN"
```

Revoke current token:

```bash
curl -X POST http://127.0.0.1:8000/api/v1/auth/logout \
  -H "Authorization: Bearer YOUR_TOKEN"
```

## 5. Main API Endpoints

Auth:

- `POST /api/v1/auth/login`
- `POST /api/v1/auth/logout`
- `GET /api/v1/auth/tokens`
- `DELETE /api/v1/auth/tokens/{id}`
- `GET /api/v1/me`

Translations:

- `GET /api/v1/translations`
- `POST /api/v1/translations`
- `GET /api/v1/translations/search`
- `GET /api/v1/translations/export/{locale}`
- `GET /api/v1/translations/{id}`
- `PATCH /api/v1/translations/{id}`
- `DELETE /api/v1/translations/{id}`

Utility:

- `GET /api/v1/health`

Normal list endpoints are paginated. Use:

```text
?per_page=15
```

Maximum `per_page` is `100`.

OpenAPI documentation:

```text
backend/docs/openapi.yaml
```

## 6. Run Tests

From `backend/`:

```bash
php artisan test
```

Code style:

```bash
vendor/bin/pint --test
```

Coverage:

```bash
php artisan test --coverage
```

Latest local result:

```text
53 tests passed
214 assertions
97.7% line coverage
```

The suite covers:

- unit tests
- feature/API tests
- authentication and token abilities
- translation CRUD
- search
- export
- command behavior
- performance-oriented checks
- error-handling branches
- repository/service behavior

## 7. Run The 100k Scalability Command

From `backend/`:

```bash
php artisan translations:populate 100000 --chunk=1000
```

Optional:

```bash
php artisan translations:populate 100000 --locales=en,fr,es --tags=mobile,desktop,web
```

After the command completes, test export again:

```bash
curl http://127.0.0.1:8000/api/v1/translations/export/en \
  -H "Authorization: Bearer YOUR_TOKEN"
```

## 8. Optional Docker Setup

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

Run backend commands inside Docker:

```bash
docker compose exec backend php artisan test
docker compose exec backend php artisan test --coverage
docker compose exec backend php artisan translations:populate 100000 --chunk=1000
```

## 9. Optional Frontend Demo

The frontend is not required to evaluate the Laravel backend. It is provided only as a small UI for testing the API.

From the project root:

```bash
cd frontend
npm install
```

Ensure `frontend/.env` contains:

```env
VITE_API_BASE_URL=http://localhost:8000/api
```

Run:

```bash
npm run dev -- --host 127.0.0.1 --port 5173
```

Frontend URL:

```text
http://127.0.0.1:5173
```

## Design Choices

- The backend follows a layered structure: controllers, requests, services, repositories, query/export classes, resources, and models.
- Controllers are thin and do not contain business logic.
- Form Request classes validate input.
- Services coordinate business operations, transactions, and logs.
- Repositories own write persistence.
- Query/export classes own read-heavy behavior.
- Normal list endpoints are paginated to avoid huge responses.
- The export endpoint streams JSON to reduce memory usage on large datasets.
- API tokens are hashed with SHA-256 and support abilities such as `translations:read`, `translations:write`, and `translations:export`.
- Write operations use `DB::transaction(..., 3)` where consistency matters.
- API responses use a consistent `success`, `message`, `data`, or `errors` envelope.
- Structured logs record success paths, validation/security rejections, and unexpected failures.
- Export responses use `Cache-Control: no-store` so frontend apps always receive updated translations.
- API and Nginx response headers include `nosniff`, frame protection, and no-store API caching by default.

## Requirement Checklist

- Multiple locales: implemented
- Add future languages: implemented through `locales`
- Context tags: implemented
- Create/update/view/delete translations: implemented
- Search by tags, keys, and content: implemented
- JSON export endpoint: implemented
- Updated export on every request: implemented with fresh DB stream and no-store headers
- Token-based auth: implemented
- Optimized queries and indexes: implemented
- 100k+ population command: implemented
- Docker setup: implemented
- OpenAPI documentation: implemented
- Unit/feature/performance tests: implemented
- Coverage above 95%: implemented
- README setup and design explanation: implemented

## Troubleshooting

If MySQL fails:

```bash
mysql -uroot -e "SHOW DATABASES;"
```

If migrations fail because the database is missing:

```bash
mysql -uroot -e "CREATE DATABASE IF NOT EXISTS translation_management_service CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
php artisan migrate --seed
```

If coverage is slower than normal tests, that is expected because Xdebug instruments execution. Performance timing assertions run during normal tests and are skipped only during coverage mode.

If frontend requests fail, confirm:

- backend is running on `http://127.0.0.1:8000`
- `frontend/.env` has `VITE_API_BASE_URL=http://localhost:8000/api`
- you are logged in with a valid token
