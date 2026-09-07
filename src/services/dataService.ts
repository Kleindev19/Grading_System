import { reactive } from 'vue'

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
  code: string
  subject: string
  section: string
  professor: string
  semester: string
  students: number
  submitted: string
  status: 'Submitted' | 'Approved' | 'Published'
}

// Centralized data provider functions — replace inline mock data with function-based sources
export function getSubjects(): Subject[] {
  return [
    { code: 'ITRSRC1',  status: 'APPROVED', name: 'Capstone Project 1',   section: 'BSIT-3A', students: 60, dept: 'Institute of Computing Science' },
    { code: 'ITQUANM',  status: 'PENDING',  name: 'Quantitative Methods', section: 'BSIT-3B', students: 55, dept: 'Institute of Computing Science' },
    { code: 'ITELEC4',  status: 'APPROVED', name: 'IT Elective 4', subtitle: '(Platform Technologies)', section: 'BSIT-3A', students: 60, dept: 'Institute of Computing Science' },
    { code: 'ITELEC4B', status: 'APPROVED', name: 'IT Elective 4', subtitle: '(Platform Technologies)', section: 'BSIT-3B', students: 58, dept: 'Institute of Computing Science' },
  ]
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

export function getPublishedGrades() {
  // Return an empty list by default — replace with API call when available
  return []
}

export function getGradeSheets() {
  return gradeSheets
}

const gradeSheets = reactive<GradeSheet[]>([])

export function submitGradeSheet(sheet: GradeSheet): void {
  const existingIndex = gradeSheets.findIndex(item => item.code === sheet.code)
  if (existingIndex === -1) gradeSheets.push(sheet)
  else gradeSheets[existingIndex] = sheet
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
    {
      id: 'ibe',
      short: 'IBE',
      name: 'INSTITUTE OF BUSINESS AND ENTREPRENEURSHIP',
      color: '#fbff78',
    },
    {
      id: 'ics',
      short: 'ICS',
      name: 'INSTITUTE OF COMPUTING SCIENCE',
      color: '#ffdd88',
    },
    {
      id: 'ioe',
      short: 'IOE',
      name: 'INSTITUTE OF EDUCATION',
      color: '#5ec7ee',
    },
  ]
}
