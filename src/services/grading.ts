export const GRADING_WEIGHTS = {
  midterm: { quiz: 15, lab: 25, recitation: 10, assignment: 10, major_exam: 40 },
  finals: { quiz: 15, lab: 5, recitation: 20, assignment: 20, major_exam: 40 },
} as const

export const FINAL_GRADE_WEIGHTS = { midterm: 40, finals: 60 } as const

export type GradingPeriod = 'midterm' | 'finals'
export type AssessmentHeaders = string[] | Record<GradingPeriod, string[]>
export interface AssessmentCriterion {
  weight: number
  maxScore: number
}
export type GradingCriteria = Record<GradingPeriod, Record<string, AssessmentCriterion>>

const CATEGORY_PREFIXES: Record<string, string> = {
  quiz: 'Q',
  lab: 'L',
  recitation: 'R',
  assignment: 'A',
  major_exam: 'Mid Ex',
}

export function assessmentCategory(label: string): string | undefined {
  return Object.entries(CATEGORY_PREFIXES).find(([, prefix]) => label.startsWith(prefix))?.[0]
}

export function assessmentCriterionKey(label: string): string | undefined {
  const customName = label.match(/^(?:Mid Ex|[QLAR])\d+:\s*(.+)$/i)?.[1]?.trim()
  if (customName) return `custom:${customName.toLocaleLowerCase().replace(/\s+/g, '_')}`
  return assessmentCategory(label)
}

export function assessmentCriterionName(key: string): string {
  const categoryNames: Record<string, string> = {
    quiz: 'Quiz',
    lab: 'Lab',
    recitation: 'Recitation',
    assignment: 'Assignment',
    major_exam: 'Major Exam',
  }
  if (categoryNames[key]) return categoryNames[key]
  return key.startsWith('custom:') ? key.slice('custom:'.length).replace(/_/g, ' ') : key
}

export function createDefaultCriteria(headers: string[] = []): GradingCriteria {
  const criteria = {
    midterm: Object.fromEntries(Object.entries(GRADING_WEIGHTS.midterm).map(([key, weight]) => [key, { weight, maxScore: 100 }])),
    finals: Object.fromEntries(Object.entries(GRADING_WEIGHTS.finals).map(([key, weight]) => [key, { weight, maxScore: 100 }])),
  } as GradingCriteria
  headers.forEach(header => {
    const key = assessmentCriterionKey(header)
    if (key && key.startsWith('custom:')) {
      criteria.midterm[key] ??= { weight: 0, maxScore: 100 }
      criteria.finals[key] ??= { weight: 0, maxScore: 100 }
    }
  })
  return criteria
}

export function normalizePeriodScores(scores: Record<string, unknown> = {}): Record<string, Record<string, string>> {
  if (scores.midterm && typeof scores.midterm === 'object' && scores.finals && typeof scores.finals === 'object') {
    return {
      midterm: { ...(scores.midterm as Record<string, string>) },
      finals: { ...(scores.finals as Record<string, string>) },
    }
  }

  const legacyScores = Object.fromEntries(Object.entries(scores)
    .filter(([, value]) => value !== null && value !== '')
    .map(([label, value]) => [label, String(Math.min(100, Number(value) * 5))]))

  return { midterm: { ...legacyScores }, finals: { ...legacyScores } }
}

export function calculateTermGrade(
  scores: Record<string, Record<string, string>>,
  period: GradingPeriod,
  headers: string[],
  criteria?: GradingCriteria,
): number | null {
  const periodScores = scores[period] || {}
  const periodCriteria = criteria?.[period] || createDefaultCriteria(headers)[period]
  const criterionKeys = [...new Set(headers.map(assessmentCriterionKey).filter((key): key is string => Boolean(key)))]
  const totalWeight = criterionKeys.reduce((total, key) => total + Number(periodCriteria[key]?.weight), 0)
  if (!criterionKeys.length || !Number.isFinite(totalWeight) || Math.abs(totalWeight - 100) > 0.001) return null

  let grade = 0

  for (const key of criterionKeys) {
    const criterion = periodCriteria[key]
    if (!criterion || !Number.isFinite(Number(criterion.maxScore)) || Number(criterion.maxScore) <= 0 || Number(criterion.maxScore) > 100) return null
    const categoryHeaders = headers.filter(header => assessmentCriterionKey(header) === key)
    const values = categoryHeaders.map(header => periodScores[header])
    const maxScore = Number(criterion.maxScore)
    if (!Number.isFinite(maxScore) || maxScore <= 0 || maxScore > 100
      || values.some(value => value === undefined || value === '' || !Number.isFinite(Number(value)) || Number(value) < 0 || Number(value) > maxScore || Number(value) > 100)) {
      return null
    }

    const average = values.reduce((total, value) => total + (Number(value) / maxScore) * 100, 0) / values.length
    grade += average * (criterion.weight / 100)
  }

  return Math.round((grade + Number.EPSILON) * 100) / 100
}

export function calculateRecordGrades(scores: Record<string, Record<string, string>>, headers: AssessmentHeaders, criteria?: GradingCriteria) {
  const headersFor = (period: GradingPeriod) => Array.isArray(headers) ? headers : headers[period]
  const midterm = calculateTermGrade(scores, 'midterm', headersFor('midterm'), criteria)
  const finals = calculateTermGrade(scores, 'finals', headersFor('finals'), criteria)
  if (midterm === null || finals === null) return null

  const finalGrade = Math.round(((midterm * FINAL_GRADE_WEIGHTS.midterm / 100 + finals * FINAL_GRADE_WEIGHTS.finals / 100) + Number.EPSILON) * 100) / 100
  const roundedGrade = Math.floor(finalGrade + 0.5)
  const scale = [
    { minimum: 98, gradePoint: '1.00' },
    { minimum: 96, gradePoint: '1.25' },
    { minimum: 93, gradePoint: '1.50' },
    { minimum: 90, gradePoint: '1.75' },
    { minimum: 87, gradePoint: '2.00' },
    { minimum: 84, gradePoint: '2.25' },
    { minimum: 81, gradePoint: '2.50' },
    { minimum: 78, gradePoint: '2.75' },
    { minimum: 75, gradePoint: '3.00' },
    { minimum: 70, gradePoint: '4.00' },
    { minimum: 0, gradePoint: '5.00' },
  ]
  const gradePoint = scale.find(item => roundedGrade >= item.minimum)?.gradePoint || '5.00'

  return {
    midterm: midterm.toFixed(2),
    finals: finals.toFixed(2),
    finalGrade: finalGrade.toFixed(2),
    gradePoint,
    remarks: roundedGrade >= 75 ? 'PASSED' : 'FAILED',
  }
}
