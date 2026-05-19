# Translation Management Service

Full-stack translation management test project.

- Backend: Laravel API in `backend`
- Frontend: React/Vite console in `frontend`
- API documentation: `backend/docs/openapi.yaml`

Quick start:

```bash
cd backend
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate --seed
php artisan serve --host=127.0.0.1 --port=8000
```

```bash
cd frontend
npm install
npm run dev -- --host 127.0.0.1 --port 5173
```

Default login: `test@example.com` / `password`.

Docker:

```bash
docker compose up --build
```

This starts Laravel/Nginx on `http://localhost:8000`, React on `http://localhost:5173`, and MySQL on `127.0.0.1:3306` with database `translation_management_service`, user `root`, and no password. The Docker stack uses the committed `.env.docker.example` files, leaving local `.env` files untouched.
