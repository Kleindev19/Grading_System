export const SYSTEM_TIME_ZONE = 'Asia/Manila'

export interface SystemDateParts {
  year: number
  month: number
  day: number
}

export function getSystemDateParts(timestamp: number = Date.now()): SystemDateParts {
  const parts = new Intl.DateTimeFormat('en-US', {
    timeZone: SYSTEM_TIME_ZONE,
    year: 'numeric',
    month: '2-digit',
    day: '2-digit',
  }).formatToParts(timestamp)
  const values = Object.fromEntries(parts.map(part => [part.type, part.value]))

  return {
    year: Number(values.year),
    month: Number(values.month),
    day: Number(values.day),
  }
}

export function getSystemDateString(timestamp: number = Date.now()): string {
  const { year, month, day } = getSystemDateParts(timestamp)
  return `${year}-${String(month).padStart(2, '0')}-${String(day).padStart(2, '0')}`
}

export function parseSystemDate(dateValue: string | null | undefined): Date | null {
  if (!dateValue || !/^\d{4}-\d{2}-\d{2}$/.test(dateValue)) return null

  const [year, month, day] = dateValue.split('-').map(Number)
  const date = new Date(Date.UTC(year, month - 1, day))
  if (date.getUTCFullYear() !== year || date.getUTCMonth() !== month - 1 || date.getUTCDate() !== day) return null
  return date
}

export function getSystemDayStart(timestamp: number = Date.now()): number {
  const { year, month, day } = getSystemDateParts(timestamp)
  return Date.UTC(year, month - 1, day)
}

export function formatSystemDate(
  date: Date,
  options: Intl.DateTimeFormatOptions,
): string {
  return new Intl.DateTimeFormat('en-US', { ...options, timeZone: 'UTC' }).format(date)
}
