# Grading System

A Vue 3 and Laravel application for the complete **Professor → Registrar → Student** grading workflow. Shared access includes registration, email verification, sign-in, and sign-out. The current development and demo setup uses mock accounts and records through the app's Laravel API. Mother System connection and data mapping are a future integration-team task.

## System at a glance

1. Registrar configures a school year, semester, and Submit of Grade deadline.
2. Professor opens an assigned course, sets its assessment criteria, and enters student scores. Draft scores and assessment columns are saved so work can be resumed.
3. Professor checks the calculated grades and submits the grade sheet for Registrar review.
4. Registrar reviews submissions, approves complete sheets, or returns a sheet with a reason. Approved sheets are scheduled for release.
5. When the scheduled release date arrives, complete approved grades are published. Students can view their own published grades and academic summary.

Dates and deadlines use Asia/Manila time. A grade period must be configured and unlocked before professors can save or submit grades. Locking a period preserves its submitted grade sheets; a new period starts with a blank grade sheet and the students' roster, without deleting historical grades. A future release remains scheduled; the Registrar may release it once its date arrives. Due schedules are also processed when grade schedules or published grades are requested, so this workflow does not depend on a separate cron job.

## Features and functions

### Professor

Class roster - Browse course grade sheets grouped by institute and year level; search by student name or ID and filter by section or year.

Course grade sheet - Open a course and see its enrolled students, student IDs, section, and semester.

Midterm and Finals entry - Switch between grading periods, enter individual assessment scores, and see the calculated period grade.

Assessment management - Add Quiz, Lab, Recitation, Assignment, or Major Exam columns; create custom assessments; remove or restore columns. Custom columns can be assigned a category or configured as a separate category.

Per-course grading criteria - View or edit each period's category weights and maximum scores. Criteria are saved for that course and semester, and each period must total 100%.

Automatic draft saving - Score edits and assessment-column changes are saved as a course draft. Reopening the course restores the draft; successful grade-sheet submission clears it.

Grade calculation - See Midterm, Finals, final grade, and equivalent grade calculated from score entries and criteria.

Period availability - See the selected school year and semester and whether the Registrar has locked grade submission. Inputs and submissions are blocked when submission is locked or the required grade period/deadline is unavailable.

Excel export - Select **Midterm Grades**, **Finals Grades**, or **Final Grade** from Export to Excel. Each choice downloads a real `.xlsx` file for the selected course with student IDs, names, and the selected calculated grade data.

Submit to Registrar - Review and confirm the final-grade sheet. The backend recalculates authoritative grade results and marks the sheet Submitted. The button then changes to Submitted and is disabled; a sheet returned by the Registrar can be corrected and resubmitted.

### Registrar

Institute grade management - Choose IBE, ICS, or ITE and manage that institute's grade sheets and professor listings.

Approval dashboard - View submission status and count summaries and a calendar showing scheduled release dates.

Search and filter submissions - Search by class, professor, or section; filter by Submitted, Approved, or Published; browse by academic year.

Grade review - Inspect a sheet's student scores, computed grades, and remarks.

Approve or return - Approve only complete sheets. Return a submission to the professor with a required reason when changes are needed. A published sheet cannot be returned.

Release scheduling - See approved sections awaiting release. School year and semester come from the approved grade sheet, and year level comes from the section; set the release date, review schedule status, or manually release a due schedule.

Grade period setup - Create one grade period per school-year semester and set its Submit of Grade deadline. The Registrar can edit or remove a period while it is still editable.

Period controls - Lock grade submission after its deadline. Locked periods remain viewable and historical professor grade sheets are retained; completed periods cannot be edited or deleted.

Academic-year safeguards - Enforce semester and year ordering; require prior academic periods to be completed before moving forward; prevent editing or deleting completed past periods after their deadline.

Editable grading scale - In Registrar Release & Schedule, the Grading Scale panel appears below Grade Period Setting. Add blank rows manually; choose a grade point from the dropdown (1.00–3.00, 4.00, or 5.00) and choose PASSED or FAILED for remarks. Upload one `.xlsx` workbook or a PNG/JPG/JPEG/JPE/WEBP image to preview detected rows before applying them to the table. Imports automatically locate columns from the headers and work without visible table borders; the four-column layout reads Minimum Grade and Maximum Grade separately, followed by Grade Range and Remarks. The three-column Range, Grade, and Remarks layout is also supported. Imported ranges must cover 0 through 100 without overlaps. Image OCR runs in the browser; the selected image is not sent to the Laravel API. Review imported values before selecting Save Rows. The saved scale is used by Professor previews and backend grade calculations; existing saved/published grades are not silently recalculated.

Student repository - Uses the authenticated `/api/students` roster and `/api/students/{studentId}/grades` published-grade lookup. Search by name or ID; filter by institute, course, year level, section, and last-name group; inspect a student's profile, status, and published grades by semester. Loading/API errors are shown in the repository instead of being presented as an empty grade result. The authenticated API also supports adding student records. The current local/demo setup displays seeded mock roster and grade data.

Professor repository - Uses the authenticated `/api/professors` response; search and view professor records and status. Pending accounts remain marked Pending. In the current mock setup, when optional institute/department values are absent, the screen uses the selected institute and the professor's grade-sheet course where available; it does not treat Pending as Active.

Repository data source - Until the Mother System adapter is provided, both repositories display the app's local/demo API data. Demo data is for development and testing only, not real student records.

Responsive dashboard - Registrar, Professor, and Student views adapt to mobile and desktop screens.

### Student

Semester selector - The dropdown shows only 1st and 2nd Semester for the current school year. It advances to the next school year when the calendar year changes; a semester without a Registrar grade period is marked unavailable.

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

Professors may customize weights per course and semester and create custom categories. Each period's weights must total 100%; configured maximum scores must be greater than 0 and no more than 100; entered scores must be from 0 through the configured maximum (up to 100). A score of 0 is valid, but any zero assessment score immediately marks the student FAILED with a 5.00 grade point. Scores within a category are averaged as percentages, then weighted to calculate that period's grade.

Final grade = Midterm × 40% + Finals × 60%. The grading service derives the final grade, grade point, and pass/fail remark; grade summaries sent by a client are not the authoritative calculation.

Normal review flow: Submitted → Approved → Published. A Registrar may return a submission to Submitted with a reason when allowed by the completeness and status rules. Approval and publication require Midterm, Finals, final grade, and grade point for every student in the sheet. Scheduled releases publish only complete, approved sheets selected by that schedule.

## Mother System integration handoff

### Current state

The application currently uses its Laravel API with local/demo accounts and mock records. It does **not** connect to the Mother System, read its database, or contain the Mother System's credentials. The mock data is for development and workflow testing only; it is not real student data.

### Integration team scope

The Mother System team owns the future connection, authentication/data mapping, and deployment of an adapter/API. The Vue application should continue calling the API contract in [the integration document](docs/mother-system-integration.md); do not expose Mother System credentials in the browser or connect the frontend directly to its database.

At minimum, the adapter must provide the following real data using the existing endpoint response shapes:

| Repository data | Endpoint | Required behavior |
| --- | --- | --- |
| Student roster | `GET /api/students` | Return `{ "students": [...] }` with stable `student_id`, `name`, `institute`, `course`, `year_level`, `section`, and `status` values from Mother System records. |
| A student's grades | `GET /api/students/{studentId}/grades` | Return `{ "grades": [...] }` for that student, containing published grades only. Each item includes `id`, `code`, `subject`, `semester`, `professor`, `units`, `midterm`, `finals`, `finalGrade`, `gradePoint`, and `remarks`. |
| Professor roster | `GET /api/professors` | Return `{ "professors": [...] }` with `id`, `name`, `email`, `username`, and `status`. Include professors even if they have not submitted a grade sheet. Optional `institute` and `department` fields should reflect their actual Mother System affiliations. |

The adapter must also preserve the authenticated user's identity and role, enforce the existing role access rules, and keep the grade calculation and release workflow described below. Keep student IDs consistent between the roster, enrollment, grade-sheet records, and published grades. See [the full Mother System integration contract](docs/mother-system-integration.md) for all endpoints, fields, rules, and acceptance checks.

### API connection

For local development, leave `VITE_API_URL` blank; Vite proxies `/api` to `http://127.0.0.1:8000`. When the Mother System adapter is ready, its deployment team can set `VITE_API_URL` to the adapter's public API origin. The frontend appends `/api` when needed. Protected endpoints require the app's documented bearer-token authorization; external-system secrets must remain server-side.

### API areas used by the workflows

| API area | What it connects |
| --- | --- |
| /api/grade-sheets | Professor submissions and Registrar grade-sheet queue and review |
| /api/grade-criteria | Per-professor, course, and semester assessment criteria |
| /api/grade-drafts | Saved score entries and assessment headers |
| /api/grading-scale | Registrar-managed final-grade ranges, grade points, and remarks |
| /api/grade-schedules | Registrar release schedules and due publication |
| /api/grade-periods | Semester deadline setup, submission lock, and removal |
| /api/students, /api/students/{studentId}/grades | Registrar student roster and per-student published grades |
| /api/professors | Registrar professor repository and statuses |
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

The focused demo/repository workflow check is:

```powershell
cd backend
php artisan test --filter=DemoAccountsSeederTest
```

Never commit .env files, database credentials, or uploaded storage files.

## Demo deployment: Vercel frontend with Render API

This is the current public **demo deployment only**, not a Mother System integration. Vercel hosts the Vue frontend; the Laravel mock-data API runs separately on Render. The Vite build uses the demo URL from the tracked `.env.production` file. If the demo Render service changes, update that file and redeploy Vercel.

The Render backend currently uses SQLite in the container. This is suitable only for a disposable demo: Render's free service filesystem is ephemeral, so accounts and grades can be lost when the service restarts or redeploys. Do not connect real Mother System accounts or store real student records in this demo service. The integration team should use its approved persistent infrastructure for the real connection.

### 1. Configure the Render web service

The Render web service must use the repository's `backend/Dockerfile` (the `backend` directory as its root). Configure these environment variables in the Render service:

```text
APP_ENV=production
APP_DEBUG=false
CORS_ALLOWED_ORIGINS=https://gradingsystem05.vercel.app,https://grading-system-gamma-six.vercel.app,https://grading-system-cdm-kleins-projects-246b3510.vercel.app
```

Set `APP_KEY` directly in the Render environment settings using a securely generated Laravel key. Never paste the real key into this README, source code, or a committed environment file.

On each container start, the Dockerfile runs migrations and seeds the verified demo accounts. Seeding is limited to this demo Docker service; the seeder is disabled by default when run manually in production. The seeder preserves an existing complete demo workflow rather than resetting its statuses on each restart.

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
