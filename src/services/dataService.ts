import type { GradingCriteria } from './grading'

export interface Subject {
  code: string
  status: 'APPROVED' | 'PENDING'
  name: string
  subtitle?: string
  section: string
  students: number
  dept: string
}

export interface Assessment {
  key: string
  label: string
  max: number
}

export interface GradeSheet {
  id?: number
  code: string
  subject: string
  section: string
  professor: string
  professor_id?: number
  semester: string
  students: number
  units?: number
  submitted: string
  status: 'Submitted' | 'Approved' | 'Published'
  rejection_reason?: string | null
  student_records?: StudentRecord[]
}

export interface GradeCriteriaCourse {
  code: string
  section: string
  semester: string
}

export type AssessmentHeaders = string[] | { midterm: string[]; finals: string[] }
export interface SavedGradeCriteria {
  criteria: GradingCriteria | null
  assessmentHeaders: AssessmentHeaders
}

export interface GradeDraft {
  subject: string
  units: number
  assessmentHeaders: AssessmentHeaders
  studentRecords: StudentRecord[]
}

export interface StudentRecord {
  id: string
  name: string
  midterm?: string
  finals?: string
  finalGrade?: string
  gradePoint?: string
  remarks?: string
  scores?: Record<string, string | number | null> | {
    midterm: Record<string, string | number | null>
    finals: Record<string, string | number | null>
  }
}

export interface GradeSchedule {
  id?: number
  schoolYear: string
  semester: string
  yearLevel: string
  courses: string[]
  releasedDate?: string
  status: 'Scheduled' | 'Released'
  submitGradeDeadline?: string
  submitGradeLocked: boolean
}

export interface Student {
  id?: number
  student_id: string
  name: string
  institute?: string
  course?: string
  year_level?: string
  section?: string
  status?: string
}

export interface Professor {
  id: number
  name: string
  email: string
  username: string
  status: 'Active' | 'Pending'
}

// Centralized data provider functions — replace inline mock data with function-based sources
export function getSubjects(): Subject[] {
  return []
}

export function getMidtermTable(): Array<{ item: string; percentage: string }> {
  return [
    { item: 'Quiz',       percentage: '15%' },
    { item: 'Activity',   percentage: '35%' },
    { item: 'Recitation', percentage: '10%' },
    { item: 'Major Exam', percentage: '40%' },
  ]
}

export function getDefaultAssessments(): Assessment[] {
  return [
    { key: 'Q1',   label: 'Q1',   max: 20 },
    { key: 'Q2',   label: 'Q2',   max: 20 },
    { key: 'Q2b',  label: 'Q2',   max: 20 },
    { key: 'Act1', label: 'Act1', max: 50 },
    { key: 'Act2', label: 'Act2', max: 50 },
  ]
}

// Create empty students map keyed by subject code (no inline mock student rows)
export function createEmptyStudentsMap(subjects: Subject[]): Record<string, unknown[]> {
  return Object.fromEntries(subjects.map(subject => [subject.code, []]))
}

export function createAssessmentsMap(subjects: Subject[]): Record<string, Assessment[]> {
  const def = getDefaultAssessments()
  return Object.fromEntries(subjects.map(subject => [subject.code, def.map(assessment => ({ ...assessment }))]))
}

function authHeaders(): HeadersInit {
  const token = localStorage.getItem('auth_token')
  return token ? { Authorization: `Bearer ${token}`, Accept: 'application/json' } : { Accept: 'application/json' }
}

export function apiUrl(path: string): string {
  const productionApi = import.meta.env.PROD ? 'https://grading-system-urtn.onrender.com' : ''
  const configuredBase = (import.meta.env.VITE_API_URL || productionApi).replace(/\/+$/, '')
  const apiBase = configuredBase.endsWith('/api') ? configuredBase : `${configuredBase}/api`
  const apiPath = path.startsWith('/') ? path : `/${path}`
  return `${apiBase}${apiPath}`
}

export async function parseApiResponse<T>(response: Response): Promise<T> {
  const body = await response.text()

  if (!body.trim()) {
    if (!response.ok) throw new Error(`Request failed (${response.status} ${response.statusText}).`)
    return {} as T
  }

  try {
    return JSON.parse(body) as T
  } catch {
    if (!response.ok) throw new Error(`Request failed (${response.status} ${response.statusText}).`)
    throw new Error('The server returned an invalid response.')
  }
}

async function apiRequest<T>(path: string, options: RequestInit = {}): Promise<T> {
  const response = await fetch(apiUrl(path), {
    ...options,
    headers: { 'Content-Type': 'application/json', ...authHeaders(), ...(options.headers || {}) },
  })
  const payload = await parseApiResponse<T & { message?: string }>(response)
  if (!response.ok) throw new Error(payload.message || 'Request failed.')
  return payload as T
}

export async function getGradeSheets(): Promise<GradeSheet[]> {
  const payload = await apiRequest<{ grade_sheets: GradeSheet[] }>('/grade-sheets')
  return payload.grade_sheets
}

export async function getGradeCriteria(course: GradeCriteriaCourse): Promise<SavedGradeCriteria> {
  const query = new URLSearchParams({
    code: course.code,
    section: course.section,
    semester: course.semester,
  }).toString()
  const payload = await apiRequest<{ criteria: GradingCriteria | null; assessment_headers: AssessmentHeaders }>(`/grade-criteria?${query}`)
  return { criteria: payload.criteria, assessmentHeaders: payload.assessment_headers }
}

export async function saveGradeCriteria(course: GradeCriteriaCourse, criteria: GradingCriteria, assessmentHeaders: AssessmentHeaders): Promise<SavedGradeCriteria> {
  const payload = await apiRequest<{ criteria: GradingCriteria; assessment_headers: AssessmentHeaders }>('/grade-criteria', {
    method: 'PUT',
    body: JSON.stringify({ ...course, criteria, assessment_headers: assessmentHeaders }),
  })
  return { criteria: payload.criteria, assessmentHeaders: payload.assessment_headers }
}

export async function getGradeDraft(course: GradeCriteriaCourse): Promise<GradeDraft | null> {
  const query = new URLSearchParams({
    code: course.code,
    section: course.section,
    semester: course.semester,
  }).toString()
  const payload = await apiRequest<{ draft: { subject: string; units: number; assessment_headers: AssessmentHeaders; student_records: StudentRecord[] } | null }>(`/grade-drafts?${query}`)
  if (!payload.draft) return null
  return {
    subject: payload.draft.subject,
    units: payload.draft.units,
    assessmentHeaders: payload.draft.assessment_headers,
    studentRecords: payload.draft.student_records,
  }
}

export async function saveGradeDraft(course: GradeCriteriaCourse, draft: GradeDraft): Promise<void> {
  await apiRequest<{ message: string }>('/grade-drafts', {
    method: 'PUT',
    body: JSON.stringify({
      ...course,
      subject: draft.subject,
      units: draft.units,
      assessment_headers: draft.assessmentHeaders,
      student_records: draft.studentRecords,
    }),
  })
}

export async function deleteGradeDraft(course: GradeCriteriaCourse): Promise<void> {
  const query = new URLSearchParams({
    code: course.code,
    section: course.section,
    semester: course.semester,
  }).toString()
  await apiRequest<{ message: string }>(`/grade-drafts?${query}`, { method: 'DELETE' })
}

export async function submitGradeSheet(sheet: GradeSheet, studentRecords: StudentRecord[] = [], period: 'midterm' | 'finals' | 'both' = 'both'): Promise<GradeSheet> {
  const payload = await apiRequest<{ grade_sheet: GradeSheet }>('/grade-sheets', {
    method: 'POST',
    body: JSON.stringify({ ...sheet, period, student_records: studentRecords }),
  })
  return payload.grade_sheet
}

export async function updateGradeSheetStatus(id: number, status: GradeSheet['status'], rejectionReason?: string): Promise<GradeSheet> {
  const payload = await apiRequest<{ grade_sheet: GradeSheet }>(`/grade-sheets/${id}/status`, {
    method: 'PATCH',
    body: JSON.stringify({ status, rejection_reason: rejectionReason }),
  })
  return payload.grade_sheet
}

export async function getPublishedGrades(): Promise<GradeSheet[]> {
  const payload = await apiRequest<{ grade_sheets: GradeSheet[] }>('/published-grades')
  return payload.grade_sheets
}

export async function getGradeSchedules(): Promise<GradeSchedule[]> {
  const payload = await apiRequest<{ schedules: GradeSchedule[] }>('/grade-schedules')
  return payload.schedules
}

export async function createGradeSchedule(schedule: Omit<GradeSchedule, 'id' | 'status'>): Promise<GradeSchedule> {
  const payload = await apiRequest<{ schedule: GradeSchedule }>('/grade-schedules', {
    method: 'POST',
    body: JSON.stringify(schedule),
  })
  return payload.schedule
}

export async function releaseGradeSchedule(id: number): Promise<GradeSchedule> {
  const payload = await apiRequest<{ schedule: GradeSchedule }>(`/grade-schedules/${id}/release`, { method: 'PATCH' })
  return payload.schedule
}

export async function createGradePeriod(period: { schoolYear: string; semester: string; submitGradeDeadline: string }): Promise<GradeSchedule> {
  const payload = await apiRequest<{ schedule: GradeSchedule }>('/grade-periods', {
    method: 'POST',
    body: JSON.stringify(period),
  })
  return payload.schedule
}

export async function updateGradePeriod(id: number, period: { schoolYear: string; semester: string; submitGradeDeadline: string }): Promise<GradeSchedule> {
  const payload = await apiRequest<{ schedule: GradeSchedule }>(`/grade-periods/${id}`, {
    method: 'PATCH',
    body: JSON.stringify(period),
  })
  return payload.schedule
}

export async function lockGradeSubmission(id: number): Promise<GradeSchedule> {
  const payload = await apiRequest<{ schedule: GradeSchedule }>(`/grade-periods/${id}/lock-submission`, {
    method: 'PATCH',
  })
  return payload.schedule
}

export async function unlockGradeSubmission(id: number): Promise<GradeSchedule> {
  const payload = await apiRequest<{ schedule: GradeSchedule }>(`/grade-periods/${id}/unlock-submission`, {
    method: 'PATCH',
  })
  return payload.schedule
}

export async function deleteGradePeriod(id: number): Promise<void> {
  await apiRequest<{ message: string }>(`/grade-periods/${id}`, { method: 'DELETE' })
}

export async function getStudents(): Promise<Student[]> {
  const payload = await apiRequest<{ students: Student[] }>('/students')
  return payload.students
}

export async function getProfessors(): Promise<Professor[]> {
  const payload = await apiRequest<{ professors: Professor[] }>('/professors')
  return payload.professors
}

export async function addStudent(student: Omit<Student, 'id'>): Promise<Student> {
  const payload = await apiRequest<{ student: Student }>('/students', {
    method: 'POST',
    body: JSON.stringify(student),
  })
  return payload.student
}

export function getReviewStudents() {
  return []
}

export function getRoles() {
  return [
    { id: 'student', title: 'Student', desc: 'Access grades, schedule, docs' },
    { id: 'professor', title: 'Professor', desc: 'Manage grades & classes' },
    { id: 'registrar', title: 'Registrar', desc: 'Manage grade approvals & publishing' },
  ]
}

export function getInstitutes() {
  return [
    { id: 'ibe', short: 'IBE', name: 'INSTITUTE OF BUSINESS AND ENTREPRENEURSHIP', color: '#fbff78' },
    { id: 'ics', short: 'ICS', name: 'INSTITUTE OF COMPUTING SCIENCE', color: '#ffdd88' },
    { id: 'ite', short: 'ITE', name: 'INSTITUTE OF TEACHERS EDUCATION', color: '#5ec7ee' },
  ]
}
