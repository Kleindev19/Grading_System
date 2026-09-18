# Grading System

Vue 3 and Laravel grading system with registrar, professor, and student workflows.

## Stack

- Frontend: Vue 3, TypeScript, Vite
- Backend: Laravel 13, PHP 8.3+
- Database: SQLite for tests; MySQL or MariaDB for local/deployed data

## Local Setup

1. Install frontend dependencies from the repository root:

   ```powershell
   npm install
   ```

2. Configure the Laravel backend:

   ```powershell
   cd backend
   composer install
   Copy-Item .env.example .env
   php artisan key:generate
   php artisan migrate
   ```

3. Configure database values in `backend/.env`. For local MySQL, set `DB_CONNECTION=mysql`, `DB_DATABASE`, `DB_USERNAME`, and `DB_PASSWORD`.

4. Optionally load demo data for testing:

   ```powershell
   php artisan db:seed
   ```

5. Start the application from two terminals:

   ```powershell
   cd backend
   php artisan serve --host=127.0.0.1 --port=8000
   ```

   ```powershell
   npm run dev -- --host 127.0.0.1 --port 5173
   ```

   Open `http://127.0.0.1:5173/`.

## Validation

```powershell
npm run build
cd backend
php artisan test
```

The frontend uses the Vite `/api` proxy locally. For a deployed frontend, set `VITE_API_URL` to the public Laravel backend URL using the root `.env.example` as a reference.

Do not commit `.env`, database credentials, mail credentials, or uploaded storage files.
