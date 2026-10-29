# Mother System Integration Contract

## Status and boundary

The grading application currently uses its Laravel JSON API. It does **not** call the Mother System or synchronize with its database. The Mother System API schema, authentication protocol, and deployment URL have not been provided, so the external connection is an integration-team task.

Build or configure a server-side adapter at the boundary. Keep Mother System field names and credentials behind that adapter; the Vue app must continue using the API shapes below. The adapter must preserve role authorization and the grading API's calculated grades and release rules.

## Connection

- Local development: leave `VITE_API_URL` blank; Vite proxies `/api` to `http://127.0.0.1:8000`.
- Deployment: set `VITE_API_URL` to the adapter/backend origin or API base URL, with or without a trailing `/api`. The frontend appends `/api` when needed.
- All API responses are JSON. Protected requests must carry an app-issued access token in the HTTP authorization header using the Bearer authentication scheme. Never expose the Mother System credential in client-side configuration.
- Authenticated users have `id`, `name`, `username`, and `role` (`student`, `professor`, or `registrar`). Student users also need `student_id`. Preserve the authenticated identity and role through every protected request.
- Errors use normal HTTP error status codes and a JSON `message`; validation errors may also include an `errors` object.

## Workflow

1. Registrar creates one grade period per school-year semester and sets the Submit of Grade deadline. Professors can submit while the period is unlocked.
2. Professors save course criteria and grade drafts, enter assessment scores, and submit Midterm, Finals, or both periods. Grade sheets are kept separately by academic period.
3. The API calculates term and final grades from the scores. Registrar approves complete sheets or returns them to the professor with a reason.
4. Registrar schedules approved sheets using the school year, semester, and section year already on the grade sheet; only the release date needs to be selected in the dashboard. Due releases are processed when schedules or student published grades are requested (no separate scheduler is required by the current app).
5. Students can view only their own records from published grade sheets.

## API endpoints

All paths below are relative to `/api`. Field names and casing are significant. Unless noted otherwise, protected endpoints require a bearer token.

### Authentication

These are the app's local auth endpoints, separate from the grading workflow. If Mother System authentication is used instead, the adapter must still meet the protected API's authenticated-user and role contract.

| Method and path | Access | Request and response |
| --- | --- | --- |
| `POST /register` | Public | Request account fields: `name`, `username`, `email`, `password`, `password_confirmation`, `role`; student accounts also require `student_id`, `institute`, `course`, `year_level`, `section`. Returns a verification-required message. |
| `POST /verify-email` | Public | `{ email, code }`; returns `{ message, user, token }` on success. |
| `POST /login` | Public | `{ username, password }`; returns `{ user, token }`. User has `id`, `name`, `username`, `role`, and `student_id` for students. |
| `GET /me` | Any signed-in role | Returns `{ user }` with the same identity shape. |
| `POST /logout` | Any signed-in role | Invalidates the current app token; returns `{ message }`. |

### Grade sheets and review

| Method and path | Access | Request and response |
| --- | --- | --- |
| `GET /grade-sheets` | Professor, Registrar | Returns `{ grade_sheets: [...] }`. Professors receive their sheets; Registrar receives all. |
| `POST /grade-sheets` | Professor | Submit `{ code, subject, section, semester, students, units, period, student_records }`. `period` is `midterm`, `finals`, or `both` (default `both`). Each record is `{ id, name, scores: { midterm?: { assessment: score }, finals?: { assessment: score } } }`. `students` must equal the record count; records need stable enrolled student IDs. Returns `{ grade_sheet }`. The API merges the selected period with saved grades, calculates grades, sets status to `Submitted`, and deletes the saved draft after successful submission. |
| `PATCH /grade-sheets/{id}/status` | Registrar | `{ status: "Approved" }`, `{ status: "Published" }`, or `{ status: "Submitted", rejection_reason }`. A reason is required when returning a sheet. Approving/publishing requires complete grades for every student. Returns `{ grade_sheet }`. |
| `GET /published-grades` | Student | Returns `{ grade_sheets: [...] }` for that student only and only for `Published` sheets. Each sheet has course metadata and `student_records` containing the student's `id`, `name`, `midterm`, `finals`, `finalGrade`, `gradePoint`, `remarks`, and `scores`. |

Grade-sheet metadata includes `id`, `code`, `subject`, `section`, `semester`, `students`, `units`, `submitted`, `status`, `professor`, `professor_id`, `reviewed_by`, `reviewed_at`, and `rejection_reason`.

### Professor criteria and drafts

`code`, `section`, and `semester` identify the professor's course configuration. Send them as query parameters for GET/DELETE and JSON fields for PUT.

| Method and path | Access | Request and response |
| --- | --- | --- |
| `GET /grade-criteria?code=...&section=...&semester=...` | Professor | Returns `{ criteria, assessment_headers }`; criteria is `null` when not yet saved. |
| `PUT /grade-criteria` | Professor | `{ code, section, semester, criteria, assessment_headers }`. Criteria has `midterm` and `finals` maps of `{ weight, maxScore }`; weights for each period must total 100 and `maxScore` must be greater than 0 and at most 100. Headers can be one shared string list or `{ midterm: [...], finals: [...] }`. Returns the saved criteria and headers. |
| `GET /grade-drafts?code=...&section=...&semester=...` | Professor | Returns `{ draft }`, or `null` when no draft exists. |
| `PUT /grade-drafts` | Professor | `{ code, section, semester, subject, units, assessment_headers, student_records }`. Draft records are `{ id, name, scores: { midterm: { assessment: score }, finals: { assessment: score } } }`. Returns `{ message }`. |
| `DELETE /grade-drafts?code=...&section=...&semester=...` | Professor | Deletes the current professor's matching draft; returns `{ message }`. |

### Students and professors

| Method and path | Access | Request and response |
| --- | --- | --- |
| `GET /students` | Professor, Registrar | Returns `{ students: [...] }`, with `student_id`, `name`, `institute`, `course`, `year_level`, `section`, and `status`. |
| `POST /students` | Registrar | Creates a student using `student_id`, `name`, optional `institute`, `course`, `year_level`, `section`, and `status`. Returns `{ student }`. |
| `GET /professors` | Registrar | Returns `{ professors: [...] }`, with `id`, `name`, `email`, `username`, and `status`. |

### Release schedules and grading periods

| Method and path | Access | Request and response |
| --- | --- | --- |
| `GET /grade-schedules` | Student, Professor, Registrar | Returns `{ schedules: [...] }`. Student responses include only course schedules applicable to the student's enrolled courses, plus grade-period schedules. |
| `POST /grade-schedules` | Registrar | Creates `{ schoolYear, semester, yearLevel, courses, releasedDate }`. In the dashboard these academic values are derived from the approved sheet (`semester` includes school year; `yearLevel` is derived from its section), while the Registrar enters the release date. `courses` uses `sheet:<gradeSheetId>` for exact targeting. Returns `{ schedule }`. |
| `PATCH /grade-schedules/{id}/release` | Registrar | Releases a due schedule; future-dated schedules are rejected. Returns `{ schedule }`. |
| `POST /grade-periods` | Registrar | Creates `{ schoolYear, semester, submitGradeDeadline }`. `schoolYear` is `YYYY-YYYY`; `semester` is `1st Semester` or `2nd Semester`. Academic-year progression is validated by the API. Returns `{ schedule }`. |
| `PATCH /grade-periods/{schedule}` | Registrar | Updates `{ schoolYear, semester, submitGradeDeadline }`, subject to completion and deadline rules. Returns `{ schedule }`. |
| `PATCH /grade-periods/{schedule}/lock-submission` | Registrar | Locks submissions after the configured deadline. The lock is permanent for that period; it does not delete grade sheets or student records. Returns `{ schedule }`. |
| `DELETE /grade-periods/{schedule}` | Registrar | Deletes an editable grade period; locked or completed past periods cannot be deleted. Returns `{ message }`. |

Schedule responses use camelCase: `id`, `schoolYear`, `semester`, `yearLevel`, `courses`, `releasedDate`, `status`, `submitGradeDeadline`, and `submitGradeLocked`. A grade-period schedule has `yearLevel: "Grade Period"`.

## Required data mapping and rules

1. **Student identity:** use one stable identifier consistently in account `student_id`, `/students`, grade-sheet record `id`, enrollment, and schedules. The current app can match a student's account `student_id` or `username`; using `student_id` consistently is preferred.
2. **Course identity:** distinguish an offering by course code, section, semester, and school year. Use `sheet:<id>` in a release schedule when a course code alone could select multiple offerings. Semester values on grade sheets include the school year (for example, `1st Semester 2026-2027`); grade-period schedules store `semester` and `schoolYear` separately.
3. **Enrollment:** return institute, course, year level, section, units, and status consistently. Student schedules must include only the student's enrolled offerings. A student's academic summary is based on expected enrolled courses and complete passing published records.
4. **Grades:** send assessment scores, not calculated summaries to be trusted. The grading API computes term, final, grade point, and remarks from scores and saved criteria. Scores range from 0 through the configured maximum (up to 100); a score of 0 is accepted but immediately results in `FAILED` with grade point `5.00`. Each configured period's weights total 100%.
5. **Availability and release:** professors cannot save or submit to a locked grade period. Locking a period preserves its grade sheets and student records. Opening a new period creates a blank course grade sheet with the student roster; it does not overwrite prior-period results. Students see published sheets only. A release affects only complete, approved sheets that match the selected schedule.
6. **Time:** grade dates and deadlines are evaluated using the Asia/Manila timezone. The current app processes due release schedules when `GET /grade-schedules` or `GET /published-grades` is called; do not assume an external cron is required for this behavior.

## Integration acceptance check

Before connecting real accounts, verify: authentication-to-role mapping; a student identity linked to an enrolled course; professor criteria/draft save, score entry, and submission; Registrar approval or return, semester lock, and scheduled release; that a new period keeps the roster but starts blank without changing historical grades; and that only the correct student can see the published record. The app integration does not include Mother System-specific API credentials, database synchronization, or an external deployment URL until the integration team supplies them.
