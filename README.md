# Grading System

A Vue 3 and Laravel application for the complete Professor → Registrar → Student grade workflow. This guide covers the grading features and data flow. Account screens are outside this workflow.

## System at a glance

1. Registrar configures Midterm and Finals dates and the final-grade submission deadline for each semester.
2. Professor opens an assigned course, sets its assessment criteria, and enters student scores. Draft scores and assessment columns are saved so work can be resumed.
3. Professor checks the calculated grades and submits the grade sheet for Registrar review.
4. Registrar reviews submissions, approves complete sheets, or returns a sheet with a reason. Approved sheets are linked to a release schedule.
5. When the scheduled release date arrives, complete approved grades are published. Students can view their own published grades and academic summary.

Dates and deadlines use Asia/Manila time. A grade period must be configured and open before professors can enter or submit that period. A future release remains scheduled; the Registrar may release it once its date arrives. Due schedules are also processed when grade schedules or published grades are requested, so this workflow does not depend on a separate cron job.

## Features and functions

### Professor

Class roster - Browse assigned courses grouped by institute and year level; search by student name or ID and filter by section or year.

Course grade sheet - Open a course and see its enrolled students, student IDs, section, and semester.

Midterm and Finals entry - Switch between grading periods, enter individual assessment scores, and see the calculated period grade.

Assessment management - Add Quiz, Lab, Recitation, Assignment, or Major Exam columns; create custom assessments; remove or restore columns. Custom columns can be assigned a category or configured as a separate category.

Per-course grading criteria - View or edit each period's category weights and maximum scores. Criteria are saved for that course and semester, and each period must total 100%.

Automatic draft saving - Score edits and assessment-column changes are saved as a course draft. Reopening the course restores the draft; successful grade-sheet submission clears it.

Grade calculation - See Midterm, Finals, final grade, and equivalent grade calculated from score entries and criteria.

Period availability - See opening dates and lock reasons. Inputs and submissions are blocked while a period is locked, before its opening date, or after the applicable submission deadline.

Final Grade export - Select Export to Excel to download a real .xlsx file for the selected course. It contains student IDs and names, Midterm, Finals, final grade, equivalent grade, and a filterable header row. Export is an explicit download action, not an automatic Excel file saved on every score edit.

Submit to Registrar - Review the final-grade sheet and confirm submission. The backend recalculates authoritative grade results and marks the sheet Submitted.

### Registrar

Institute grade management - Choose IBE, ICS, or ITE and manage that institute's grade sheets and professor listings.

Approval dashboard - View submission status and count summaries and a calendar showing scheduled release dates.

Search and filter submissions - Search by class, professor, or section; filter by Submitted, Approved, or Published; browse by academic year.

Grade review - Inspect a sheet's student scores, computed grades, and remarks.

Approve or return - Approve only complete sheets. Return a submission to the professor with a required reason when changes are needed. A published sheet cannot be returned.

Release scheduling - See approved sections awaiting release; choose school year, semester, year level, and release date; review existing schedules and status; manually release a due schedule.

Grade period setup - Create one schedule per semester and configure Midterm opening, Finals opening, and final-grade submission deadline in stages.

Period controls - Mark Midterm or Finals complete to lock that stage and unlock the next setup step; reopen a completed stage when correction or resubmission is needed. Relevant date fields lock after completion.

Academic-year safeguards - Enforce semester and year ordering; require prior academic periods to be completed before moving forward; prevent editing or deleting completed past periods after their deadline.

Student repository - Search students by name or ID; filter by institute, course, year level, section, and last-name group; inspect student profile, status, and published grades by semester. The authenticated API also supports adding student records.

Professor repository - Search and view professor records and their institute or department association.

### Student

Semester selector - Choose an available semester and academic year. A term without an opening date or whose opening date is in the future is marked locked and its grades are unavailable.

Academic summary - View General Weighted Average, enrolled-subject count, student status, and total units for the selected semester.

Published-grade table - View subject code and name, professor, Midterm (40%), Finals (60%), final average, grade point, and remarks.

My Subjects shortcut - Jump to the published subject list.

Privacy boundary - The API returns only the signed-in student's records and only from grade sheets with Published status.

Automatic refresh - The dashboard refreshes grade and schedule data periodically so newly released grades can appear without manually reloading the page.

## Grade rules and status flow

### Default category weights

| Assessment category | Midterm | Finals |
| --- | ---: | ---: |
| Quiz | 15% | 15% |
| Lab | 25% | 5% |
| Recitation | 10% | 20% |
| Assignment | 10% | 20% |
| Major Exam | 40% | 40% |
| Total | 100% | 100% |

Professors may customize weights per course and semester and create custom categories. Each period's weights must total 100%; configured maximum scores must be greater than 0 and no more than 100; entered scores must be within the configured maximum and 100. Scores within a category are averaged as percentages, then weighted to calculate that period's grade.

Final grade = Midterm × 40% + Finals × 60%. The grading service derives the final grade, grade point, and pass/fail remark; grade summaries sent by a client are not the authoritative calculation.

Normal review flow: Submitted → Approved → Published. A Registrar may return a submission to Submitted with a reason when allowed by the completeness and status rules. Approval and publication require Midterm, Finals, final grade, and grade point for every student in the sheet. Scheduled releases publish only complete, approved sheets selected by that schedule.

## Connection and data flow

The frontend calls the Laravel JSON API. Laravel validates roles and data, stores course criteria, drafts, grade sheets, and schedules, calculates grades, and filters published records by student identity. The Mother System is not directly connected, and its external API and authentication details have not been supplied. For handoff, the integration team must provide an adapter that maps its authenticated user, roles, student IDs, course offerings, enrollment, and semester data to this app's API contract. There is no direct database sharing.

Local development - Keep VITE_API_URL blank; Vite proxies /api to http://127.0.0.1:8000.

Integrated deployment - Set VITE_API_URL to the adapter or API origin, with or without a trailing /api.

Credentials - Keep external-system secrets server-side. Do not put them in frontend environment variables.

Data mapping - Use stable student identifiers consistently for enrollment, grade-sheet records, schedules, and published results. Identify a course offering by code, section, semester, and school year; use sheet:<id> for exact release-schedule targeting when needed.

API contract - Full endpoint list, field names, response shapes, role access, and mapping requirements are in [the Mother System integration contract](docs/mother-system-integration.md).

### API areas used by the workflows

| API area | What it connects |
| --- | --- |
| /api/grade-sheets | Professor submissions and Registrar grade-sheet queue and review |
| /api/grade-criteria | Per-professor, course, and semester assessment criteria |
| /api/grade-drafts | Saved score entries and assessment headers |
| /api/grade-schedules | Registrar release schedules and due publication |
| /api/grade-periods | Semester opening dates, deadline, completion, and reopening |
| /api/students, /api/professors | Registrar repositories and professor roster data |
| /api/published-grades | Student-scoped published grade results |

## Technology

Frontend - Vue 3, TypeScript, and Vite; ExcelJS generates .xlsx exports.

Backend - Laravel 13, PHP 8.3+, and a JSON REST API.

Database - Configure via Laravel environment settings. SQLite is used for automated tests; MySQL or MariaDB may be used for local or deployed data.

Dates - Asia/Manila system timezone.

## Run locally

Requirements - Node.js, PHP, Composer, and a Laravel-supported database.

From the repository root:

```powershell
npm install
```

In a backend terminal:

```powershell
cd backend
composer install
Copy-Item .env.example .env
php artisan key:generate
php artisan migrate
php artisan serve --host=127.0.0.1 --port=8000
```

In a second terminal at the repository root:

```powershell
npm run dev -- --host 127.0.0.1 --port 5173
```

Open http://127.0.0.1:5173/. Set database and mail configuration in backend/.env. To seed the local SQLite database with the demo users, roster, grade sheets in Submitted/Approved/Published states, an active grade period, and release schedules, run this from `backend`:

```powershell
php artisan db:seed --class=DemoAccountsSeeder
```

This seeds only the database configured by the local `backend/.env`; it does not change Vercel or Render configuration. Demo logins for local testing: student `23-00001`, professor `mock.professor`, and registrar `mock.registrar`. All use `Password123!`.

## Verify changes

From the repository root:

```powershell
npm run build
```

From backend:

```powershell
php artisan test
```

Never commit .env files, database credentials, or uploaded storage files.

## Deploy Vercel frontend with Render API

Vercel hosts the Vue frontend; the Laravel API runs separately on Render. The production Vite build uses `https://grading-system-urtn.onrender.com` from the tracked `.env.production` file. If the Render service changes, update that file and redeploy Vercel.

The Render backend currently uses SQLite in the container. This is suitable only for a disposable demo: Render's free service filesystem is ephemeral, so accounts and grades can be lost when the service restarts or redeploys. Do not store real student records in this demo service.

### 1. Configure the Render web service

The Render web service must use the repository's `backend/Dockerfile` (the `backend` directory as its root). Configure these environment variables in the Render service:

```text
APP_ENV=production
APP_DEBUG=false
APP_KEY=<a generated Laravel application key>
CORS_ALLOWED_ORIGINS=https://gradingsystem05.vercel.app,https://grading-system-cdm-kleins-projects-246b3510.vercel.app
SEED_DEMO_ACCOUNTS=true
```

Keep `SEED_DEMO_ACCOUNTS=true` for this disposable demo service. On each container start, the Dockerfile runs migrations and seeds the verified demo accounts. The seeder is disabled by default in production and will fail safely if explicitly run without the flag.

Confirm that `https://grading-system-urtn.onrender.com/up` reports **Application up**. The first request may take longer while a free Render service wakes.

### 2. Deploy the Vercel frontend

The `.env.production` file sets:

```text
VITE_API_URL=https://grading-system-urtn.onrender.com
```

Push the change to GitHub and wait for Vercel to finish a new Production deployment. Vite embeds this public API base URL at build time; changing a Vercel runtime variable alone does not modify an already-built frontend. The API automatically adds `/api` to this base URL. CORS on Render must allow the exact Vercel origin.

### 3. Demo logins

The seeder creates a student profile as well as the three verified accounts, so email verification is not required:

| Role | Username | Password |
| --- | --- | --- |
| Student | `23-00001` | `Password123!` |
| Professor | `mock.professor` | `Password123!` |
| Registrar | `mock.registrar` | `Password123!` |

These public demo credentials must never be reused for real accounts or real student records. Demo Access uses the usernames above.

### 4. Verify login

Open the Vercel site after its latest deployment and try each Demo Access role. A Render `422` with **Invalid username or password** means the demo seeder did not run; check `SEED_DEMO_ACCOUNTS=true` and the Render deployment logs. A browser **Failed to fetch** message means CORS or Render availability; check `CORS_ALLOWED_ORIGINS` and the `/up` health page. A `404` at the Vercel site's `/api/login` means the browser is still running an old frontend deployment.
