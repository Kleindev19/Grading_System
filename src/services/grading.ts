export const GRADING_WEIGHTS = {
  midterm: { quiz: 15, lab: 25, recitation: 10, assignment: 10, major_exam: 40 },
  finals: { quiz: 15, lab: 5, recitation: 20, assignment: 20, major_exam: 40 },
} as const

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
  period: 'midterm' | 'finals',
  headers: string[],
): number | null {
  const periodScores = scores[period] || {}
  let grade = 0

  for (const [category, weight] of Object.entries(GRADING_WEIGHTS[period])) {
    const categoryHeaders = headers.filter(header => assessmentCategory(header) === category)
    if (!categoryHeaders.length) return null

    const values = categoryHeaders.map(header => periodScores[header])
    if (values.some(value => value === undefined || value === '' || !Number.isFinite(Number(value)) || Number(value) < 0 || Number(value) > 100)) {
      return null
    }

    const average = values.reduce((total, value) => total + Number(value), 0) / values.length
    grade += average * (weight / 100)
  }

  return Math.round((grade + Number.EPSILON) * 100) / 100
}

export function calculateRecordGrades(scores: Record<string, Record<string, string>>, headers: string[]) {
  const midterm = calculateTermGrade(scores, 'midterm', headers)
  const finals = calculateTermGrade(scores, 'finals', headers)
  if (midterm === null || finals === null) return null

  const finalGrade = Math.round(((midterm * 0.4 + finals * 0.6) + Number.EPSILON) * 100) / 100
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
    remarks: roundedGrade >= 75 ? 'PASSED' : roundedGrade >= 70 ? 'INC' : 'FAILED',
  }
}
