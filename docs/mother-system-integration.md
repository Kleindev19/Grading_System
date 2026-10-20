# Mother System Integration Contract

The grading app currently reads and writes its canonical Laravel API. It is not directly connected to the Mother System because the external API schema and authentication protocol have not been supplied. Add an adapter at the integration boundary; do not bind Vue components directly to Mother System field names.

## Connection

Set `VITE_API_URL` to either the adapter origin (for example, `https://adapter.example.edu`) or its API base URL (for example, `https://adapter.example.edu/api`). The frontend appends `/api` only when it is not already present. Leave it blank for local development, where Vite proxies `/api` to Laravel.

All protected endpoints must accept `Authorization: Bearer <app token>` and JSON responses. The adapter may validate the Mother System credential server-side and map its identity to the app's authenticated user; never expose a Mother System secret in Vite environment variables.

## Canonical API

| Endpoint | Expected response or request |
| --- | --- |
| `POST /api/login` | Request `{ username, password }`; response `{ user, token }`, where `user` has `id`, `name`, `username`, `role`, and student users have `student_id`. |
| `GET /api/me` | Response `{ user }` using the same user shape. |
| `GET /api/students` | Response `{ students: [{ student_id, name, institute, course, year_level, section, status }] }`. |
| `GET /api/professors` | Response `{ professors: [{ id, name, email, username, status }] }`; registrar-only. |
| `GET /api/grade-sheets` | Response `{ grade_sheets: [...] }`; registrar receives all, professor receives their sheets. Each sheet includes `id`, `code`, `subject`, `section`, `semester`, `units`, `status`, `professor_id`, and `student_records`. |
| `POST /api/grade-sheets` | Professor submission with `code`, `subject`, `section`, `semester`, `units`, `students`, `period` (`midterm`, `finals`, or `both`), and records `{ id, name, scores: { midterm?: { assessment: percentage }, finals?: { assessment: percentage } } }`. `id` must match an enrolled student identifier. |
| `PATCH /api/grade-sheets/{id}/status` | Registrar status change to `Approved`, `Published`, or return to `Submitted` with `rejection_reason`. Approval/publication requires both term grades and final grade for every record. |
| `GET /api/grade-schedules` | Response `{ schedules: [{ id, schoolYear, semester, yearLevel, courses, releasedDate, status, midtermOpens, midtermDeadline, finalsOpens, finalsDeadline }] }`. Student responses must include only their enrolled course offerings; course entries may be `sheet:<id>` or a course code. |
| `GET /api/published-grades` | Student-only response `{ grade_sheets: [...] }`, each containing only that student's record with `id`, `midterm`, `finals`, `finalGrade`, `gradePoint`, and `remarks`. |
| `GET/POST /api/messages` | Message records use `studentId`, `professorId`, `senderId`, `body`, `attachmentUrl`, and optional `gradeSheet`. Image uploads are limited to 1 MB. |

## Required Mapping

1. Map Mother System student identifiers consistently. Grade-sheet records may use either the app user's `student_id` or `username`; schedule enrollment and published-grade results must use the same identity mapping.
2. Preserve course offering identity, not only a display name. A course offering must be distinguishable by code, section, semester, and school year. The grading app stores semester/year in `semester` (for example, `1st Semester 2025-2026`) and schedule records separately store `schoolYear` and `semester`.
3. Map units and enrollment status for every enrolled subject. The student dashboard uses registrar schedules to determine expected subjects and only shows `Completed` when every expected subject has complete passing grades.
4. Keep grading authoritative in the grading API. The app calculates term percentages, final grade, grade point, and remarks from assessment scores; imported or submitted summary values should not override that calculation.
5. Keep registrar release/status semantics: `Submitted` -> `Approved` -> `Published`; students see published grades only.

Before switching a real account over, validate login/identity mapping, one enrolled course, a Midterm-only submission, a Finals-only submission, registrar approval and release, and that only the correct student sees the published record.