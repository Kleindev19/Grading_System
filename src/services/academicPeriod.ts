export interface AcademicPeriod {
  semester: string
  schoolYear: string
}

function normalizeSchoolYear(value: string): string {
  const match = value.match(/(\d{4})\s*[-/]\s*(\d{4}|\d{2})/)
  if (!match) return value.replace(/[^\da-z]/gi, '').toLowerCase()
  const endYear = match[2].length === 2 ? `${match[1].slice(0, 2)}${match[2]}` : match[2]
  return `${match[1]}-${endYear}`
}

export function parseAcademicPeriod(value: string, schoolYear = ''): AcademicPeriod {
  const semester = value.match(/(1st|2nd)\s+semester/i)?.[0]?.toLowerCase() || ''
  return {
    semester,
    schoolYear: normalizeSchoolYear(schoolYear || value),
  }
}

export function sameAcademicPeriod(left: string, leftYear = '', right: string, rightYear = ''): boolean {
  const leftPeriod = parseAcademicPeriod(left, leftYear)
  const rightPeriod = parseAcademicPeriod(right, rightYear)
  return Boolean(leftPeriod.semester && leftPeriod.schoolYear)
    && leftPeriod.semester === rightPeriod.semester
    && leftPeriod.schoolYear === rightPeriod.schoolYear
}
