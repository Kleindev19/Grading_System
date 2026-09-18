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
  student_records?: StudentRecord[]
}

export interface StudentRecord {
  id: string
  name: string
  midterm?: string
  finals?: string
  finalGrade?: string
  gradePoint?: string
  remarks?: string
  scores?: Record<string, string>
}

export interface GradeSchedule {
  id?: number
  schoolYear: string
  semester: string
  yearLevel: string
  courses: string[]
  releasedDate?: string
  status: 'Scheduled' | 'Released'
  midtermOpens?: string
  midtermDeadline?: string
  finalsOpens?: string
  finalsDeadline?: string
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
  const baseUrl = (import.meta.env.VITE_API_URL || '').replace(/\/$/, '')
  return `${baseUrl}/api${path}`
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

export async function submitGradeSheet(sheet: GradeSheet, studentRecords: StudentRecord[] = []): Promise<GradeSheet> {
  const payload = await apiRequest<{ grade_sheet: GradeSheet }>('/grade-sheets', {
    method: 'POST',
    body: JSON.stringify({ ...sheet, student_records: studentRecords }),
  })
  return payload.grade_sheet
}

export async function updateGradeSheetStatus(id: number, status: GradeSheet['status']): Promise<GradeSheet> {
  const payload = await apiRequest<{ grade_sheet: GradeSheet }>(`/grade-sheets/${id}/status`, {
    method: 'PATCH',
    body: JSON.stringify({ status }),
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

export async function createGradePeriod(period: { schoolYear: string; semester: string; midtermOpens?: string; midtermDeadline?: string; finalsOpens?: string; finalsDeadline?: string }): Promise<GradeSchedule> {
  const payload = await apiRequest<{ schedule: GradeSchedule }>('/grade-periods', {
    method: 'POST',
    body: JSON.stringify(period),
  })
  return payload.schedule
}

export async function updateGradePeriod(id: number, period: { schoolYear: string; semester: string; midtermOpens?: string; midtermDeadline?: string; finalsOpens?: string; finalsDeadline?: string }): Promise<GradeSchedule> {
  const payload = await apiRequest<{ schedule: GradeSchedule }>(`/grade-periods/${id}`, {
    method: 'PATCH',
    body: JSON.stringify(period),
  })
  return payload.schedule
}

export async function deleteGradePeriod(id: number): Promise<void> {
  await apiRequest<{ message: string }>(`/grade-periods/${id}`, { method: 'DELETE' })
}

export interface MessageRecord {
  id: number
  studentId: number
  studentName?: string
  studentNumber?: string
  professorId: number
  professorName?: string
  senderId: number
  body?: string
  attachmentUrl?: string
  gradeSheet?: { id: number; code: string; subject: string; section: string } | null
  createdAt?: string
  readAt?: string | null
}

export interface MessageThread {
  key: string
  studentId: number
  studentName?: string
  studentNumber?: string
  gradeSheetId?: number
  course: { name: string; section: string }
  messages: MessageRecord[]
  unreadCount: number
}

export async function getMessages(): Promise<MessageRecord[]> {
  const payload = await apiRequest<{ messages: MessageRecord[] }>('/messages')
  return payload.messages
}

export async function sendMessage(message: { professorId?: number; studentId?: number; gradeSheetId?: number; body?: string; attachment?: File }): Promise<MessageRecord> {
  const body = new FormData()
  if (message.professorId) body.append('professor_id', String(message.professorId))
  if (message.studentId) body.append('student_id', String(message.studentId))
  if (message.gradeSheetId) body.append('grade_sheet_id', String(message.gradeSheetId))
  if (message.body) body.append('body', message.body)
  if (message.attachment) body.append('attachment', message.attachment)
  const response = await fetch(apiUrl('/messages'), { method: 'POST', headers: authHeaders(), body })
  const payload = await parseApiResponse<{ message: MessageRecord; message_error?: string }>(response)
  if (!response.ok) throw new Error(payload.message_error || 'Unable to send message.')
  return payload.message
}

export async function deleteMessage(messageId: number): Promise<void> {
  const response = await fetch(apiUrl(`/messages/${messageId}`), {
    method: 'DELETE',
    headers: authHeaders(),
  })
  const payload = await parseApiResponse<{ message?: string }>(response)
  if (!response.ok) throw new Error(payload.message || 'Unable to unsend message.')
}

export function groupMessages(messages: MessageRecord[]): MessageThread[] {
  const groups = new Map<string, MessageThread>()

  messages.forEach(message => {
    const key = `${message.studentId}:${message.professorId || 'general'}`
    const existing = groups.get(key)

    if (existing) {
      existing.messages.push(message)
      if (!existing.gradeSheetId && message.gradeSheet?.id) {
        existing.gradeSheetId = message.gradeSheet.id
      }
      if (!existing.course.name || existing.course.name === 'General Message') {
        existing.course = { name: message.gradeSheet?.subject || 'General Message', section: message.gradeSheet?.section || '' }
      }
      if (!message.readAt && message.senderId !== message.professorId) existing.unreadCount += 1
      return
    }

    groups.set(key, {
      key,
      studentId: message.studentId,
      studentName: message.studentName,
      studentNumber: message.studentNumber,
      gradeSheetId: message.gradeSheet?.id,
      course: { name: message.gradeSheet?.subject || 'General Message', section: message.gradeSheet?.section || '' },
      messages: [message],
      unreadCount: !message.readAt && message.senderId !== message.professorId ? 1 : 0,
    })
  })

  return [...groups.values()].map(thread => ({
    ...thread,
    messages: [...thread.messages].sort((left, right) => (left.createdAt || '').localeCompare(right.createdAt || '')),
  }))
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
    { id: 'ioe', short: 'IOE', name: 'INSTITUTE OF EDUCATION', color: '#5ec7ee' },
  ]
}
