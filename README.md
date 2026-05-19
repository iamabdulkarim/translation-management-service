# Translation Management Service

Full-stack translation management test project built with a Laravel API backend and an optional React/Vite frontend console.

The **Laravel backend** is the primary deliverable for the test task. The frontend is included only as a lightweight demonstration client for testing the API.

## Project Structure

```text
TranslationManagementService/
├── backend/              # Laravel API backend
├── frontend/             # Optional React/Vite frontend console
├── docker-compose.yml    # Docker stack for API, frontend, and MySQL
└── README.md             # Project overview

Main Deliverable

The backend API is located in:
backend/

Backend documentation:
backend/README.md

OpenAPI documentation:
backend/docs/openapi.yaml

Features
Laravel API for multilingual translation management
Locale support for languages such as en, fr, and es
Context tags such as mobile, desktop, and web
Translation CRUD endpoints
Search by locale, tag, key, and content
Streamed JSON export endpoint for frontend applications
First-party bearer-token authentication
Ability-scoped and revocable API tokens
100k+ record population command
Docker setup
OpenAPI documentation
Test coverage above 95%
Optional React/Vite frontend console

Quick Start Without Docker
Backend
cd backend
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate --seed
php artisan serve --host=127.0.0.1 --port=8000

Default backend URL:
http://127.0.0.1:8000

Default API base URL:
http://127.0.0.1:8000/api/v1

Default seeded user:
Email: test@example.com
Password: password

Frontend
cd frontend
npm install
npm run dev -- --host 127.0.0.1 --port 5173

Frontend URL:
http://127.0.0.1:5173

Docker Quick Start

From the project root:
docker compose up --build

Docker services:
API:      http://localhost:8000
Frontend: http://localhost:5173
MySQL:    127.0.0.1:3306
Database: translation_management_service
Username: root
Password: empty

The Docker stack uses the committed Docker environment examples and does not require local .env files to be changed.

Backend Test Commands

From the backend directory:

php artisan test
php artisan test --coverage
vendor/bin/pint --test

Latest local coverage result:
53 tests passed
214 assertions
97.7% line coverage

100k+ Scalability Test

From the backend directory:
php artisan translations:populate 100000 --chunk=1000

API Documentation

OpenAPI spec:
backend/docs/openapi.yaml