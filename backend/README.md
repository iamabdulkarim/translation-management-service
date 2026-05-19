# Translation Management Service

Laravel API for managing multilingual translations with context tags, bearer-token security, search, and streamed JSON export for frontend applications.

## Stack

- Laravel 13 / PHP 8.3
- SQLite by default, compatible with MySQL/PostgreSQL schema patterns
- React/Vite frontend in `../frontend`
- No external CRUD or translation-service packages

## Local MySQL Setup

```bash
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate --seed
php artisan serve --host=127.0.0.1 --port=8000
```

The default database configuration uses MySQL:

```text
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=translation_management_service
DB_USERNAME=root
DB_PASSWORD=
```

Default seeded user:

```text
test@example.com / password
```

## Testing and Quality

```bash
php artisan test
vendor/bin/pint --test
```

The suite includes unit, feature, command, and performance-oriented tests for auth, CRUD, search, export, and bulk population.

## Performance Data

Create 100k+ records for scalability testing:

```bash
php artisan translations:populate 100000 --chunk=1000
```

Optional locales/tags:

```bash
php artisan translations:populate 100000 --locales=en,fr,es --tags=mobile,desktop,web
```

The command uses chunked inserts and database transactions instead of per-row Eloquent writes.

## Docker

From the project root:

```bash
docker compose up --build
```

The Compose stack reads `backend/.env.docker.example` and `frontend/.env.docker.example` directly, so your local `.env` files can stay configured for Laragon/MySQL development.

Services:

- API: `http://localhost:8000`
- Frontend: `http://localhost:5173`
- MySQL: `127.0.0.1:3306`
- Database: `translation_management_service`
- Username: `root`
- Password: empty

Run backend commands inside Docker:

```bash
docker compose exec backend php artisan test
docker compose exec backend php artisan translations:populate 100000 --chunk=1000
```

## API Authentication

Issue a token:

```bash
curl -X POST http://127.0.0.1:8000/api/v1/auth/login \
  -H "Content-Type: application/json" \
  -d '{"email":"test@example.com","password":"password","token_name":"local","abilities":["*"]}'
```

Use the returned `plain_text_token`:

```bash
curl http://127.0.0.1:8000/api/v1/translations \
  -H "Authorization: Bearer YOUR_TOKEN"
```

## Main Endpoints

- `POST /api/v1/auth/login`
- `POST /api/v1/auth/logout`
- `GET /api/v1/auth/tokens`
- `DELETE /api/v1/auth/tokens/{id}`
- `GET /api/v1/translations`
- `POST /api/v1/translations`
- `GET /api/v1/translations/search`
- `GET /api/v1/translations/export/{locale}`
- `GET /api/v1/translations/{id}`
- `PATCH /api/v1/translations/{id}`
- `DELETE /api/v1/translations/{id}`

OpenAPI spec: [docs/openapi.yaml](docs/openapi.yaml)

## Design Choices

- `locales`, `translation_keys`, `translations`, `tags`, and `translation_key_tag` separate language, key, value, and context concerns.
- Unique `translation_key_id + locale_id` prevents duplicate locale values for the same key.
- Indexed locale, key, publication, tag pivot, and hash columns support fast lookup/search/export.
- Write operations live in services and use DB transactions with retry attempts.
- Repositories own database write details; query/export classes own read-heavy operations.
- Controllers stay thin and delegate validation to Form Requests and response formatting to Resources.
- API responses use a consistent `success`, `message`, `data` or `errors` envelope.
- Bearer tokens are stored as SHA-256 hashes and can be revoked or ability-scoped.
- Export uses streamed JSON and `Cache-Control: no-store` so clients always receive fresh translations.
- Structured logs record success, validation/security rejections, and persistence failures.
- API and Nginx response headers include `nosniff`, `DENY` frame policy, and no-store API caching by default.
- CDN-aware config is available through `TMS_CDN_ENABLED`, `TMS_CDN_ASSET_URL`, and cache-control env variables.

## Frontend

```bash
cd ../frontend
npm install
npm run dev -- --host 127.0.0.1 --port 5173
```

Configure `frontend/.env`:

```text
VITE_API_BASE_URL=http://localhost:8000/api
```
