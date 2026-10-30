import Tesseract, { createWorker } from 'tesseract.js'
import type { GradingScaleRow } from './grading'

const MAX_FILE_SIZE = 10 * 1024 * 1024
const MAX_IMAGE_PIXELS = 20_000_000
const IMAGE_EXTENSIONS = new Set(['.png', '.jpg', '.jpeg', '.jpe', '.webp'])
const VALID_GRADE_POINTS = new Set(['1.00', '1.25', '1.50', '1.75', '2.00', '2.25', '2.50', '2.75', '3.00', '4.00', '5.00'])

type ImportProgress = (message: string) => void

interface OcrLine {
  text: string
  x0: number
  x1: number
  y: number
  words?: Array<{ text: string; x0: number; x1: number; y: number }>
}

type ImageColumnField = 'minimum' | 'maximum' | 'range' | 'gradePoint' | 'remarks'

interface ImageColumnRegion {
  field: ImageColumnField
  left: number
  right: number
  top: number
}

interface OcrScaleRow extends GradingScaleRow {
  x0: number
  x1: number
  y: number
}

interface OcrRangeCandidate {
  minimum: number | null
  maximum: number | null
  observedValue?: number
  y: number
}

interface OcrPartialScaleRow extends Omit<OcrScaleRow, 'minimum' | 'maximum'> {
  minimum: number | null
  maximum: number | null
  observedRangeValue?: number
}

function getExtension(fileName: string): string {
  return fileName.slice(fileName.lastIndexOf('.')).toLowerCase()
}

function cellText(value: unknown): string {
  if (value == null) return ''
  if (typeof value === 'object' && 'text' in value) return String(value.text ?? '').trim()
  if (typeof value === 'object' && 'richText' in value && Array.isArray(value.richText)) {
    return value.richText.map(part => String(part.text ?? '')).join('').trim()
  }
  return String(value).trim()
}

function parseGradeRange(value: unknown): [number, number] | null {
  const match = cellText(value).match(/^\s*(\d{1,3})\s*(?:-|–|—|to)\s*(\d{1,3})\s*$/i)
  if (!match) return null
  return [Number(match[1]), Number(match[2])]
}

function formatGradePoint(value: unknown): string {
  const numeric = Number(cellText(value).replace(',', '.'))
  return Number.isFinite(numeric) ? numeric.toFixed(2) : ''
}

function normalizeHeader(value: unknown): string {
  return cellText(value).toLowerCase().replace(/[^a-z%]+/g, ' ').trim()
}

function columnIndexes(values: unknown[]) {
  const headers = values.map(normalizeHeader)
  const find = (predicate: (header: string) => boolean) => headers.findIndex(predicate)
  const minimum = find(header => /\bmin(?:imum)?\b/.test(header))
  const maximum = find(header => /\bmax(?:imum)?\b/.test(header))
  const range = find(header => /\brange\b/.test(header))
  const gradePoint = find(header =>
    /\bgrade\b/.test(header)
      && !/\b(?:min|minimum|max|maximum)\b/.test(header)
      && (!/\brange\b/.test(header) || (minimum >= 0 && maximum >= 0)),
  )
  const remarks = find(header => /\bremarks?\b/.test(header))

  if (gradePoint < 0 || remarks < 0 || (range < 0 && (minimum < 0 || maximum < 0))) return null
  return { minimum, maximum, range, gradePoint, remarks }
}

type ImageScaleLayout = 'four-column' | 'three-column'

function detectImageScaleLayout(lines: OcrLine[]): ImageScaleLayout | null {
  const text = lines.map(line => line.text).join(' ').toLowerCase().replace(/[^a-z%]+/g, ' ')
  const hasRemarks = /\bremarks?\b/.test(text)
  const hasRange = /\brange\b/.test(text)
  const hasMinimum = /\bmin(?:imum)?\b/.test(text)
  const hasMaximum = /\bmax(?:imum)?\b/.test(text)

  if (hasMinimum && hasMaximum && hasRange && hasRemarks) return 'four-column'
  if (!hasMinimum && !hasMaximum && hasRange && /\bgrade\b/.test(text) && hasRemarks) return 'three-column'
  return null
}

function validateRows(rows: GradingScaleRow[]): string | null {
  if (rows.length === 0) return 'No grading-scale rows were found.'
  const invalidRowIndex = rows.findIndex(row =>
    !Number.isInteger(row.minimum)
      || !Number.isInteger(row.maximum)
      || row.minimum < 0
      || row.maximum > 100
      || row.minimum > row.maximum
      || !VALID_GRADE_POINTS.has(formatGradePoint(row.gradePoint))
      || !/^(PASSED|FAILED)$/i.test(row.remarks.trim()),
  )
  if (invalidRowIndex >= 0) {
    const row = rows[invalidRowIndex]
    return `Row ${invalidRowIndex + 1} could not be read correctly (minimum: ${row.minimum}, maximum: ${row.maximum}, grade: ${row.gradePoint || 'missing'}, remarks: ${row.remarks || 'missing'}). Use a clearer image or correct this row manually after import.`
  }

  const ascending = [...rows].sort((first, second) => first.minimum - second.minimum)
  let nextMinimum = 0
  for (const row of ascending) {
    if (row.minimum !== nextMinimum) return 'The detected ranges overlap or do not cover every grade from 0 to 100.'
    nextMinimum = row.maximum + 1
  }
  return nextMinimum === 101 ? null : 'The detected ranges must cover every grade from 0 to 100.'
}

async function extractWorkbookRows(file: File): Promise<GradingScaleRow[]> {
  const { default: ExcelJS } = await import('exceljs')
  const workbook = new ExcelJS.Workbook()
  await workbook.xlsx.load(await file.arrayBuffer())
  if (workbook.worksheets.length > 10 || workbook.worksheets.some(worksheet => worksheet.rowCount > 200)) {
    throw new Error('The workbook contains too many sheets or rows. Upload a workbook with at most 10 sheets and 200 rows per sheet.')
  }
  const validScales: GradingScaleRow[][] = []

  workbook.eachSheet((worksheet) => {
    const headerRowNumber = Math.min(worksheet.rowCount, 25)
    for (let rowNumber = 1; rowNumber <= headerRowNumber; rowNumber += 1) {
      const header = columnIndexes(worksheet.getRow(rowNumber).values as unknown[])
      if (!header) continue

      const rows: GradingScaleRow[] = []
      let carriedRemark = ''
      for (let dataRowNumber = rowNumber + 1; dataRowNumber <= worksheet.rowCount; dataRowNumber += 1) {
        const dataRow = worksheet.getRow(dataRowNumber)
        const minimumValue = header.minimum >= 0 ? dataRow.getCell(header.minimum).value : null
        const maximumValue = header.maximum >= 0 ? dataRow.getCell(header.maximum).value : null
        const range = header.range >= 0
          ? parseGradeRange(dataRow.getCell(header.range).value)
          : null
        const minimum = minimumValue == null ? range?.[0] : Number(cellText(minimumValue).replace(/,/g, ''))
        const maximum = maximumValue == null ? range?.[1] : Number(cellText(maximumValue).replace(/,/g, ''))
        const gradePoint = formatGradePoint(dataRow.getCell(header.gradePoint).value)
        const currentRemark = cellText(dataRow.getCell(header.remarks).value)
        if (currentRemark) carriedRemark = currentRemark
        if (minimum == null && maximum == null && !gradePoint) continue
        if (minimum == null || maximum == null || !gradePoint) continue
        rows.push({ minimum, maximum, gradePoint, remarks: currentRemark || carriedRemark })
      }

      if (!validateRows(rows)) validScales.push(rows)
      break
    }
  })

  if (validScales.length !== 1) {
    throw new Error(validScales.length > 1
      ? 'More than one grading scale was found. Keep only one valid scale in the workbook before importing.'
      : 'No valid grading-scale table was found. Use columns for Minimum/Maximum Grade or Range, Grade, and Remarks, with ranges covering 0 to 100.')
  }
  return validScales[0]
}

function readOcrLines(data: { blocks: Array<{ paragraphs: Array<{ lines: Array<{ text: string; bbox: { x0: number; x1: number; y0: number; y1: number }; words?: Array<{ text: string; bbox: { x0: number; x1: number; y0: number; y1: number } }> }> }> }> | null; text: string }): OcrLine[] {
  const lines = data.blocks?.flatMap(block => block.paragraphs.flatMap(paragraph => paragraph.lines)) || []
  if (lines.length > 0) {
    return lines.map(line => ({
      text: line.text.trim(),
      x0: line.bbox.x0,
      x1: line.bbox.x1,
      y: (line.bbox.y0 + line.bbox.y1) / 2,
      words: line.words?.map(word => ({
        text: word.text.trim(),
        x0: word.bbox.x0,
        x1: word.bbox.x1,
        y: (word.bbox.y0 + word.bbox.y1) / 2,
      })),
    }))
  }
  return data.text.split(/\r?\n/).map((text, index) => ({ text: text.trim(), x0: 0, x1: 0, y: index }))
}

function findImageHeaderAnchor(lines: OcrLine[], field: ImageColumnField): { center: number; y: number } | null {
  const allWords = lines.flatMap(line => (line.words ?? []).map(word => ({ ...word, line })))
  const pattern = field === 'minimum'
    ? /^min(?:imum)?$/i
    : field === 'maximum'
      ? /^max(?:imum)?$/i
      : field === 'range'
        ? /^range$/i
        : field === 'gradePoint'
          ? /^grade$/i
          : /^remarks?$/i
  const word = allWords.find(candidate =>
    pattern.test(candidate.text.trim())
      && (field !== 'gradePoint' || !/\bmin(?:imum)?\b|\bmax(?:imum)?\b/i.test(candidate.line.text)),
  )

  if (word) {
    let x0 = word.x0
    let x1 = word.x1
    if (field === 'minimum' || field === 'maximum' || (field === 'gradePoint' && word.line.text.toLowerCase().includes('range'))) {
      const relatedGrade = allWords
        .filter(candidate => candidate.line === word.line && /^grade$/i.test(candidate.text.trim()))
        .sort((first, second) => Math.abs(first.y - word.y) - Math.abs(second.y - word.y))[0]
      if (relatedGrade) {
        x0 = Math.min(x0, relatedGrade.x0)
        x1 = Math.max(x1, relatedGrade.x1)
      }
    }
    return { center: (x0 + x1) / 2, y: word.y }
  }

  const line = lines.find(candidate => {
    const text = candidate.text.toLowerCase().replace(/[^a-z]+/g, ' ')
    if (field === 'minimum') return /\bmin(?:imum)?\b/.test(text) && !/\bmax(?:imum)?\b/.test(text)
    if (field === 'maximum') return /\bmax(?:imum)?\b/.test(text) && !/\bmin(?:imum)?\b/.test(text)
    if (field === 'range') return /\brange\b/.test(text)
    if (field === 'gradePoint') return /\bgrade\b/.test(text) && !/\bmin(?:imum)?\b|\bmax(?:imum)?\b/.test(text)
    return /\bremarks?\b/.test(text)
  })
  return line ? { center: (line.x0 + line.x1) / 2, y: line.y } : null
}

function getImageColumnRegions(lines: OcrLine[], layout: ImageScaleLayout, width: number, height: number): ImageColumnRegion[] {
  const fields: ImageColumnField[] = layout === 'four-column'
    ? ['minimum', 'maximum', 'gradePoint', 'remarks']
    : ['range', 'gradePoint', 'remarks']
  const anchors = fields.map(field => findImageHeaderAnchor(lines, field))
  const orderedAnchors = anchors.map((anchor, index) => ({ anchor, index })).sort((first, second) =>
    (first.anchor?.center ?? Number.POSITIVE_INFINITY) - (second.anchor?.center ?? Number.POSITIVE_INFINITY),
  )
  const geometryIsUsable = orderedAnchors.every(({ anchor }) => anchor != null)
    && orderedAnchors.every(({ anchor }, index) =>
      index === 0 || anchor!.center - orderedAnchors[index - 1].anchor!.center > width * 0.04,
    )
  if (!geometryIsUsable) {
    const defaults = layout === 'four-column'
      ? [
          { field: 'minimum' as const, left: 0.05, right: 0.29 },
          { field: 'maximum' as const, left: 0.29, right: 0.51 },
          { field: 'gradePoint' as const, left: 0.51, right: 0.73 },
          { field: 'remarks' as const, left: 0.73, right: 0.97 },
        ]
      : [
          { field: 'range' as const, left: 0.02, right: 0.47 },
          { field: 'gradePoint' as const, left: 0.4, right: 0.68 },
          { field: 'remarks' as const, left: 0.65, right: 0.99 },
        ]
    return defaults.map(region => ({ ...region, top: 0.12 }))
  }

  const centers = orderedAnchors.map(({ anchor }) => anchor!.center / width)
  const top = Math.min(0.9, Math.max(...orderedAnchors.map(({ anchor }) => anchor!.y / height)) + 0.025)
  return orderedAnchors.map(({ index }, position) => {
    const center = centers[position]
    const left = position === 0 ? center - (centers[position + 1] - center) / 2 : (centers[position - 1] + center) / 2
    const right = position === centers.length - 1 ? center + (center - centers[position - 1]) / 2 : (center + centers[position + 1]) / 2
    return {
      field: fields[index],
      left: Math.max(0, left),
      right: Math.min(1, right),
      top,
    }
  })
}

function parseOcrScaleRow(line: OcrLine): OcrScaleRow | null {
  const normalized = line.text.replace(/[–—]/g, '-')
  const range = normalized.match(/([0-9O]{1,3})\s*-\s*([0-9O]{1,3})/i)
  if (!range) return null

  const minimum = Number(range[1].replace(/O/gi, '0'))
  const maximum = Number(range[2].replace(/O/gi, '0'))
  const afterRange = normalized.slice((range.index || 0) + range[0].length)
  const point = afterRange.match(/([1-5])\s*[.,]\s*([0-9O]{1,2})/)
  if (!point) return null
  const gradePoint = `${point[1]}.${point[2].replace(/O/gi, '0')}`.padEnd(4, '0')
  const remark = afterRange.slice((point.index || 0) + point[0].length).replace(/^[\s:;,.-]+/, '').trim()

  return {
    minimum,
    maximum,
    gradePoint,
    remarks: /^[a-z][a-z ]{0,31}$/i.test(remark) ? remark.toUpperCase() : '',
    x0: line.x0,
    x1: line.x1,
    y: line.y,
  }
}

function isRemarkLine(line: OcrLine, rows: OcrScaleRow[], medianRowWidth: number): boolean {
  const text = line.text.toUpperCase().replace(/[^A-Z ]/g, ' ').replace(/\s+/g, ' ').trim()
  if (!/^[A-Z][A-Z ]{0,23}$/.test(text)) return false
  if (/\b(GRADING|SYSTEM|RANGE|GRADE|REMARKS|MINIMUM|MAXIMUM|MIDTERM|FINALS|TOTAL|PERCENT|BELOW|ABOVE)\b/.test(text)) return false
  const rowTop = Math.min(...rows.map(row => row.y))
  const rowBottom = Math.max(...rows.map(row => row.y))
  const rowWidthStart = Math.min(...rows.map(row => row.x0))
  return line.x1 > 0
    && line.x0 > rowWidthStart + medianRowWidth * 0.45
    && line.y >= rowTop - medianRowWidth * 0.2
    && line.y <= rowBottom + medianRowWidth * 0.2
}

function assignMergedRemarks<T extends { y: number; remarks: string }>(rows: T[], labels: OcrLine[]) {
  if (labels.length === 0 || labels.length > rows.length) return
  const sortedRows = [...rows].sort((first, second) => first.y - second.y)
  const sortedLabels = [...labels].sort((first, second) => first.y - second.y)
  const groupCount = sortedLabels.length
  const rowCount = sortedRows.length
  const rowYPrefix = [0]
  sortedRows.forEach(row => rowYPrefix.push(rowYPrefix[rowYPrefix.length - 1] + row.y))
  const cost = Array.from({ length: groupCount + 1 }, () => Array(rowCount + 1).fill(Number.POSITIVE_INFINITY))
  const previous = Array.from({ length: groupCount + 1 }, () => Array(rowCount + 1).fill(-1))
  cost[0][0] = 0

  for (let group = 1; group <= groupCount; group += 1) {
    for (let end = group; end <= rowCount - (groupCount - group); end += 1) {
      for (let start = group - 1; start < end; start += 1) {
        const center = (rowYPrefix[end] - rowYPrefix[start]) / (end - start)
        const distance = center - sortedLabels[group - 1].y
        const candidate = cost[group - 1][start] + distance * distance
        if (candidate < cost[group][end]) {
          cost[group][end] = candidate
          previous[group][end] = start
        }
      }
    }
  }

  let end = rowCount
  for (let group = groupCount; group > 0; group -= 1) {
    const start = previous[group][end]
    if (start < 0) return
    for (let index = start; index < end; index += 1) {
      if (!sortedRows[index].remarks) sortedRows[index].remarks = sortedLabels[group - 1].text.toUpperCase()
    }
    end = start
  }
}

function prepareImageCanvas(image: ImageBitmap, crop: boolean): HTMLCanvasElement {
  const canvas = document.createElement('canvas')
  const cropX = crop ? image.width * 0.1 : 0
  const cropY = crop ? image.height * 0.12 : 0
  const cropWidth = crop ? image.width * 0.8 : image.width
  const cropHeight = crop ? image.height * 0.7 : image.height
  const scale = Math.min(3, 2400 / Math.max(cropWidth, cropHeight))
  canvas.width = Math.round(cropWidth * scale)
  canvas.height = Math.round(cropHeight * scale)
  const context = canvas.getContext('2d')
  if (!context) throw new Error('Unable to prepare the image for local text recognition.')
  context.filter = 'grayscale(1) contrast(1.7) brightness(1.08)'
  context.drawImage(image, cropX, cropY, cropWidth, cropHeight, 0, 0, canvas.width, canvas.height)
  if (crop) cleanTableGrid(canvas)
  return canvas
}

function cleanTableGrid(canvas: HTMLCanvasElement) {
  const context = canvas.getContext('2d')
  if (!context) return
  const image = context.getImageData(0, 0, canvas.width, canvas.height)
  const { data, width, height } = image
  const darkRows = new Uint8Array(height)
  const darkColumns = new Uint32Array(width)

  for (let y = 0; y < height; y += 1) {
    let darkCount = 0
    for (let x = 0; x < width; x += 1) {
      const offset = (y * width + x) * 4
      const luminance = data[offset] * 0.299 + data[offset + 1] * 0.587 + data[offset + 2] * 0.114
      const value = luminance < 145 ? 0 : 255
      data[offset] = value
      data[offset + 1] = value
      data[offset + 2] = value
      data[offset + 3] = 255
      if (value === 0) {
        darkCount += 1
        darkColumns[x] += 1
      }
    }
    if (darkCount > width * 0.24) darkRows[y] = 1
  }

  const removeRows = new Uint8Array(height)
  const removeColumns = new Uint8Array(width)
  for (let y = 0; y < height; y += 1) {
    if (darkRows[y]) {
      for (let offset = -1; offset <= 1; offset += 1) {
        if (y + offset >= 0 && y + offset < height) removeRows[y + offset] = 1
      }
    }
  }
  for (let x = 0; x < width; x += 1) {
    if (darkColumns[x] > height * 0.42) {
      for (let offset = -1; offset <= 1; offset += 1) {
        if (x + offset >= 0 && x + offset < width) removeColumns[x + offset] = 1
      }
    }
  }

  for (let y = 0; y < height; y += 1) {
    for (let x = 0; x < width; x += 1) {
      if (!removeRows[y] && !removeColumns[x]) continue
      const offset = (y * width + x) * 4
      data[offset] = 255
      data[offset + 1] = 255
      data[offset + 2] = 255
    }
  }
  context.putImageData(image, 0, 0)
}

function prepareColumnCanvas(image: ImageBitmap, left: number, top: number, right: number, bottom: number, cleanGrid = false) {
  const cropX = image.width * left
  const cropY = image.height * top
  const cropWidth = image.width * (right - left)
  const cropHeight = image.height * (bottom - top)
  const scale = Math.min(4, 2400 / Math.max(cropWidth, cropHeight))
  const canvas = document.createElement('canvas')
  canvas.width = Math.round(cropWidth * scale)
  canvas.height = Math.round(cropHeight * scale)
  const context = canvas.getContext('2d')
  if (!context) throw new Error('Unable to prepare image columns for text recognition.')
  context.filter = 'grayscale(1) contrast(1.7) brightness(1.08)'
  context.drawImage(image, cropX, cropY, cropWidth, cropHeight, 0, 0, canvas.width, canvas.height)
  if (cleanGrid) cleanTableGrid(canvas)
  return {
    canvas,
    mapY: (y: number) => y / scale + cropY,
  }
}

function prepareRemarkCanvas(image: ImageBitmap, left: number, top: number, right: number, bottom: number, threshold = true) {
  const cropX = image.width * left
  const cropY = image.height * top
  const cropWidth = image.width * (right - left)
  const cropHeight = image.height * (bottom - top)
  const scale = Math.min(4, 2400 / Math.max(cropWidth, cropHeight))
  const canvas = document.createElement('canvas')
  canvas.width = Math.round(cropWidth * scale)
  canvas.height = Math.round(cropHeight * scale)
  const context = canvas.getContext('2d')
  if (!context) throw new Error('Unable to prepare grading remarks for text recognition.')
  context.filter = threshold ? 'grayscale(1)' : 'grayscale(1) contrast(1.7) brightness(1.08)'
  context.drawImage(image, cropX, cropY, cropWidth, cropHeight, 0, 0, canvas.width, canvas.height)
  if (threshold) {
    const pixels = context.getImageData(0, 0, canvas.width, canvas.height)
    for (let offset = 0; offset < pixels.data.length; offset += 4) {
      const value = pixels.data[offset] < 75 ? 0 : 255
      pixels.data[offset] = value
      pixels.data[offset + 1] = value
      pixels.data[offset + 2] = value
    }
    context.putImageData(pixels, 0, 0)
  }
  return {
    canvas,
    mapY: (y: number) => y / scale + cropY,
  }
}

function parseRangeColumn(lines: OcrLine[], rowDistance: number): OcrRangeCandidate[] {
  const ranges: OcrRangeCandidate[] = lines.flatMap(line => {
    const match = line.text.replace(/[–—]/g, '-').match(/([0-9O]{1,3})\s*(?:-|to)\s*([0-9O]{1,3})/i)
    if (!match) return []
    return [{
      minimum: Number(match[1].replace(/O/gi, '0')),
      maximum: Number(match[2].replace(/O/gi, '0')),
      y: line.y,
    }]
  })
  const singleValues = lines.flatMap(line => {
    if (/[0-9O]{1,3}\s*(?:[-–—]|to)\s*[0-9O]{1,3}/i.test(line.text)) return []
    const match = line.text.match(/\b([0-9O]{1,3})\b/i)
    if (!match) return []
    return [{ value: Number(match[1].replace(/O/gi, '0')), x: line.x0, y: line.y }]
  }).sort((first, second) => first.y - second.y)
  const groups: typeof singleValues[] = []
  for (const value of singleValues) {
    const group = groups[groups.length - 1]
    if (!group || value.y - group.reduce((sum, item) => sum + item.y, 0) / group.length > rowDistance) {
      groups.push([value])
    } else {
      group.push(value)
    }
  }
  for (const group of groups) {
    if (group.length === 2) {
      const [left, right] = [...group].sort((first, second) => first.x - second.x)
      ranges.push({ minimum: left.value, maximum: right.value, y: (left.y + right.y) / 2 })
    } else if (group.length === 1) {
      ranges.push({ minimum: null, maximum: null, observedValue: group[0].value, y: group[0].y })
    }
  }
  return ranges
    .filter(range => range.observedValue == null || !ranges.some(other =>
      other !== range
        && (other.minimum === range.observedValue || other.maximum === range.observedValue),
    ))
    .sort((first, second) => first.y - second.y)
}

function completePartialRanges(rows: OcrPartialScaleRow[]): GradingScaleRow[] {
  for (let index = 0; index < rows.length; index += 1) {
    const row = rows[index]
    if (row.minimum != null && row.maximum != null) continue
    const observed = row.observedRangeValue
    if (observed == null) continue
    const previousMinimum = index === 0 ? 101 : rows[index - 1].minimum
    const nextMaximum = index === rows.length - 1 ? -1 : rows[index + 1].maximum
    const inferredMaximum = previousMinimum == null ? null : previousMinimum - 1
    const inferredMinimum = nextMaximum == null ? null : nextMaximum + 1

    if (index === 0 && inferredMaximum === 100) {
      row.maximum = 100
      if (observed !== 100) row.minimum = observed
      else if (inferredMinimum != null) row.minimum = inferredMinimum
    } else if (index === rows.length - 1 && inferredMinimum === 0) {
      row.minimum = 0
      if (observed !== 0) row.maximum = observed
      else if (inferredMaximum != null) row.maximum = inferredMaximum
    } else if (observed === inferredMaximum) {
      row.maximum = inferredMaximum
      if (inferredMinimum != null) row.minimum = inferredMinimum
    } else if (observed === inferredMinimum) {
      row.minimum = inferredMinimum
      if (inferredMaximum != null) row.maximum = inferredMaximum
    }
  }

  return rows.map(({ minimum, maximum, gradePoint, remarks }) => ({
    minimum: minimum ?? Number.NaN,
    maximum: maximum ?? Number.NaN,
    gradePoint,
    remarks,
  }))
}

function takeNearest<T extends { y: number }>(values: T[], targetY: number): T | null {
  if (values.length === 0) return null
  let nearestIndex = 0
  for (let index = 1; index < values.length; index += 1) {
    if (Math.abs(values[index].y - targetY) < Math.abs(values[nearestIndex].y - targetY)) nearestIndex = index
  }
  return values.splice(nearestIndex, 1)[0]
}

function parseOcrGradePoint(text: string): string | null {
  const match = text.match(/\b([1-5])\s*[.,]?\s*([0-9O]{2,3})\b/)
  if (!match) return null
  const decimals = match[2].replace(/O/gi, '0')
  const candidates = new Set<string>()
  if (decimals.length === 2) candidates.add(`${match[1]}.${decimals}`)
  if (decimals.length === 3) {
    for (let index = 0; index < decimals.length; index += 1) {
      candidates.add(`${match[1]}.${decimals.slice(0, index)}${decimals.slice(index + 1)}`)
    }
  }
  const validCandidates = [...candidates].filter(candidate => VALID_GRADE_POINTS.has(candidate))
  return validCandidates.length === 1 ? validCandidates[0] : null
}

function parseOcrGradePoints(lines: OcrLine[]): Array<{ gradePoint: string; y: number }> {
  const points = lines.flatMap(line => [{
    gradePoint: parseOcrGradePoint(line.text),
    y: line.y,
  }]).sort((first, second) => first.y - second.y)
  const orderedOptions = [...VALID_GRADE_POINTS].sort((first, second) => Number(first) - Number(second))

  points.forEach((point, index) => {
    if (point.gradePoint) return
    const previous = [...points.slice(0, index)].reverse().find(candidate => candidate.gradePoint)?.gradePoint
    const next = points.slice(index + 1).find(candidate => candidate.gradePoint)?.gradePoint
    const possible = orderedOptions.filter(option =>
      (previous == null || Number(option) > Number(previous))
        && (next == null || Number(option) < Number(next)),
    )
    if (possible.length === 1) point.gradePoint = possible[0]
  })

  return points.filter((point): point is { gradePoint: string; y: number } => point.gradePoint != null)
}

function parseOcrRemark(text: string): 'PASSED' | 'FAILED' | null {
  const normalized = text.toUpperCase().replace(/[^A-Z]/g, '')
  if (/^PASS(?:ED|EO|E|)?$/.test(normalized)) return 'PASSED'
  if (/^FAIL(?:ED|EO|E|)?$/.test(normalized)) return 'FAILED'
  return null
}

function correctRangeBoundaries(ranges: OcrRangeCandidate[]) {
  const invalidIndexes = ranges.flatMap((range, index) =>
    range.minimum == null
      || range.maximum == null
      || range.minimum < 0
      || range.maximum > 100
      || range.minimum > range.maximum
      ? [index]
      : [],
  )
  if (invalidIndexes.length !== 1) return

  const index = invalidIndexes[0]
  const range = index == null ? null : ranges[index]
  if (!range || index == null) return
  const previousMinimum = ranges[index - 1]?.minimum
  const nextMaximum = ranges[index + 1]?.maximum
  const inferredMaximum = index === 0 ? 100 : previousMinimum == null ? null : previousMinimum - 1
  const inferredMinimum = index === ranges.length - 1 ? 0 : nextMaximum == null ? null : nextMaximum + 1
  if (
    inferredMinimum != null
    && inferredMaximum != null
    && inferredMinimum <= inferredMaximum
    && (
      range.minimum == null
      || range.maximum == null
      || range.minimum < 0
      || range.maximum > 100
      || range.minimum > range.maximum
    )
  ) {
    range.minimum = inferredMinimum
    range.maximum = inferredMaximum
  }
}

function extractSpatialRows(
  lines: OcrLine[],
  layout: ImageScaleLayout,
  width: number,
  height: number,
): GradingScaleRow[] {
  const regions = getImageColumnRegions(lines, layout, width, height)
  const anchors = regions.map(region => findImageHeaderAnchor(lines, region.field))
  if (anchors.some(anchor => anchor == null)) {
    throw new Error('Could not locate all grading-scale headers in the image.')
  }
  const dataTop = Math.max(...anchors.map(anchor => anchor!.y)) + height * 0.005
  const linesFor = (field: ImageColumnField) => lines.filter(line => {
    const region = regions.find(candidate => candidate.field === field)
    if (!region || line.y <= dataTop) return false
    const center = (line.x0 + line.x1) / 2
    return center >= region.left * width && center <= region.right * width
  })

  const ranges: OcrRangeCandidate[] = layout === 'three-column'
    ? linesFor('range').flatMap(line => {
        const match = line.text.replace(/[–—]/g, '-').match(/([0-9O]{1,3})\s*(?:-|to)\s*([0-9O]{1,3})/i)
        if (!match) return []
        return [{
          minimum: Number(match[1].replace(/O/gi, '0')),
          maximum: Number(match[2].replace(/O/gi, '0')),
          y: line.y,
        }]
      }).sort((first, second) => first.y - second.y)
    : []
  const minimumBounds = layout === 'four-column'
    ? linesFor('minimum').flatMap(line => {
        const match = line.text.match(/\b([0-9O]{1,3})\b/i)
        if (!match) return []
        return [{ value: Number(match[1].replace(/O/gi, '0')), raw: match[1], y: line.y }]
      }).sort((first, second) => first.y - second.y)
    : []

  if ((layout === 'three-column' ? ranges.length : minimumBounds.length) < 2) {
    throw new Error('Could not read the grade ranges from the image.')
  }
  const bounds = layout === 'four-column' ? linesFor('maximum').flatMap(line => {
    const match = line.text.replace(/[1-5]\s*[.,]\s*[0-9O]{2,3}/g, ' ').match(/\b([0-9O]{1,3})\b/i)
    if (!match) return []
    return [{ value: Number(match[1].replace(/O/gi, '0')), raw: match[1], y: line.y }]
  }).sort((first, second) => first.y - second.y) : []
  if (layout === 'four-column') correctDescendingMinimums(minimumBounds)
  else correctRangeBoundaries(ranges)

  const pointLines = linesFor('gradePoint')
  const points = parseOcrGradePoints(pointLines)
  const labels = linesFor('remarks').flatMap(line => {
    const text = parseOcrRemark(line.text)
    return text ? [{ text, x0: line.x0, x1: line.x1, y: line.y }] : []
  }).sort((first, second) => first.y - second.y)

  const rowSeeds: Array<{ minimum: number | null; maximum: number | null; y: number }> = layout === 'four-column'
    ? minimumBounds.map(bound => ({ minimum: bound.value, maximum: null, y: bound.y }))
    : ranges.map(range => ({ minimum: range.minimum, maximum: range.maximum, y: range.y }))
  const rows: OcrPartialScaleRow[] = []
  const availablePoints = [...points]
  const availableBounds = [...bounds]
  rowSeeds.forEach(seed => {
    const targetY = seed.y
    const point = takeNearest(availablePoints, targetY)
    const maximum = layout === 'four-column' ? takeNearest(availableBounds, targetY)?.value ?? null : seed.maximum
    const minimum = seed.minimum
    if (point && minimum != null && maximum != null) {
      rows.push({
        minimum,
        maximum,
        gradePoint: point.gradePoint,
        remarks: '',
        x0: 0,
        x1: 1,
        y: targetY,
      })
    }
  })
  if (rows.length !== rowSeeds.length || rows.length < 2) {
    throw new Error(`Could not align the image columns (${ranges.length} ranges, ${points.length} grades, ${labels.length} remarks).`)
  }
  assignMergedRemarks(rows, labels)
  const imported = rows.map(({ minimum, maximum, gradePoint, remarks }) => ({ minimum: minimum ?? Number.NaN, maximum: maximum ?? Number.NaN, gradePoint, remarks }))
  const validationError = validateRows(imported)
  if (validationError) throw new Error(`The grading table was detected, but the extracted rows need correction: ${validationError}`)
  return imported
}

function correctDescendingMinimums(bounds: Array<{ value: number; raw: string; y: number }>) {
  for (let index = 0; index < bounds.length - 1; index += 1) {
    const bound = bounds[index]
    const previousValue = index > 0 ? bounds[index - 1].value : Number.POSITIVE_INFINITY
    const nextValue = bounds[index + 1].value
    if (bound.value < previousValue && bound.value > nextValue) continue

    const digits = bound.raw.replace(/O/gi, '0')
    if (!/^\d{2,3}$/.test(digits)) continue
    const candidates = new Set<number>()
    for (let digit = 0; digit < digits.length; digit += 1) {
      for (let replacement = 0; replacement <= 9; replacement += 1) {
        const candidate = Number(`${digits.slice(0, digit)}${replacement}${digits.slice(digit + 1)}`)
        if (candidate <= 100 && candidate < previousValue && candidate > nextValue) candidates.add(candidate)
      }
    }
    if (candidates.size === 1) bound.value = [...candidates][0]
  }
}

async function extractRecognizedRows(worker: Awaited<ReturnType<typeof createWorker>>, image: HTMLCanvasElement, pageSegmentation: Tesseract.PSM): Promise<GradingScaleRow[]> {
  await worker.setParameters({
    tessedit_pageseg_mode: pageSegmentation,
    user_defined_dpi: '300',
  })
  const result = await worker.recognize(image, {}, { text: true, blocks: true })
  const lines = readOcrLines(result.data)
  if (!detectImageScaleLayout(lines)) {
    throw new Error('The image must show either Minimum Grade, Maximum Grade, Grade Range, and Remarks headers, or Range, Grade, and Remarks headers.')
  }

  const extracted = lines.map(parseOcrScaleRow).filter((row): row is OcrScaleRow => row !== null)
  if (extracted.length < 2) throw new Error('Could not read enough grade ranges. Use a clearer, straight-on image of the grading table.')
  const rowWidths = extracted.map(row => row.x1 - row.x0).sort((first, second) => first - second)
  const rowWidth = rowWidths[Math.floor(rowWidths.length / 2)]
  const labelLines = lines.filter(line => isRemarkLine(line, extracted, rowWidth))
  assignMergedRemarks(extracted, labelLines)

  const rows = extracted.map(({ minimum, maximum, gradePoint, remarks }) => ({ minimum, maximum, gradePoint, remarks }))
  const validationError = validateRows(rows)
  if (validationError) {
    throw new Error(`The grading table was detected, but the extracted rows need correction: ${validationError}`)
  }
  return rows
}

async function extractColumnRows(
  worker: Awaited<ReturnType<typeof createWorker>>,
  image: ImageBitmap,
  layout: ImageScaleLayout,
  headerLines: OcrLine[],
  headerWidth: number,
  headerHeight: number,
): Promise<GradingScaleRow[]> {
  const regions = getImageColumnRegions(headerLines, layout, headerWidth, headerHeight)
  const recognized: OcrLine[][] = []
  for (const region of regions) {
    const prepared = region.field === 'remarks'
      ? prepareRemarkCanvas(image, region.left, region.top, region.right, 0.99)
      : prepareColumnCanvas(image, region.left, region.top, region.right, 0.99)
    await worker.setParameters({
      tessedit_pageseg_mode: Tesseract.PSM.SPARSE_TEXT,
      user_defined_dpi: '300',
      tessedit_char_whitelist: region.field === 'remarks' ? 'ABCDEFGHIJKLMNOPQRSTUVWXYZ' : '0123456789O.,-–— ',
    })
    const result = await worker.recognize(prepared.canvas, {}, { text: true, blocks: true })
    let lines = readOcrLines(result.data).map(line => ({ ...line, y: prepared.mapY(line.y) }))
    if (region.field === 'remarks' && !lines.some(line => parseOcrRemark(line.text))) {
      const enhanced = prepareRemarkCanvas(image, region.left, region.top, region.right, 0.99, false)
      const retry = await worker.recognize(enhanced.canvas, {}, { text: true, blocks: true })
      lines = readOcrLines(retry.data).map(line => ({ ...line, y: enhanced.mapY(line.y) }))
    }
    recognized.push(lines)
  }

  const findLines = (field: ImageColumnField) => recognized[regions.findIndex(region => region.field === field)]
  const parseBounds = (field: 'minimum' | 'maximum') => findLines(field).flatMap(line =>
    [...line.text.replace(/[1-5]\s*[.,]\s*[0-9O]{2,3}/g, ' ').matchAll(/\b([0-9O]{1,3})\b/gi)].flatMap(match => {
      const value = Number(match[1].replace(/O/gi, '0'))
      return Number.isInteger(value) && value >= 0 && value <= 100
        ? [{ value, raw: match[1], y: line.y }]
        : []
    }),
  ).sort((first, second) => first.y - second.y)

  let minimums = layout === 'four-column' ? parseBounds('minimum') : []
  if (layout === 'four-column') correctDescendingMinimums(minimums)
  const maximums = layout === 'four-column' ? parseBounds('maximum') : []
  if (layout === 'four-column') {
    maximums.forEach((maximum, index) => {
      const expected = minimums[index - 1]?.value
      if (expected == null || maximum.value === expected - 1) return
      if (maximum.raw.length === 1 && String(expected - 1).endsWith(String(maximum.value))) {
        maximum.value = expected - 1
      }
    })
    if (
      maximums.length === minimums.length + 1
      && minimums.at(-1)?.value != null
      && maximums.at(-1)?.value === (minimums.at(-1)?.value ?? 0) - 1
    ) {
      const lastMaximum = maximums.at(-1)
      if (lastMaximum) minimums = [...minimums, { value: 0, raw: '0', y: lastMaximum.y }]
    }
  }
  let ranges = layout === 'three-column'
    ? parseRangeColumn(findLines('range'), image.height * 0.015)
    : []
  const rawPoints = parseOcrGradePoints(findLines('gradePoint'))
  if (layout === 'three-column' && ranges.length < rawPoints.length) {
    const rangeRegion = regions.find(region => region.field === 'range')
    if (rangeRegion) {
      const retryCanvas = prepareRemarkCanvas(image, rangeRegion.left, rangeRegion.top, rangeRegion.right, 0.99, false)
      await worker.setParameters({
        tessedit_pageseg_mode: Tesseract.PSM.SINGLE_BLOCK,
        user_defined_dpi: '300',
        tessedit_char_whitelist: '0123456789O.,-–— to',
      })
      const retryResult = await worker.recognize(retryCanvas.canvas, {}, { text: true, blocks: true })
      const retryLines = readOcrLines(retryResult.data).map(line => ({ ...line, y: retryCanvas.mapY(line.y) }))
      const retryRanges = parseRangeColumn(retryLines, image.height * 0.015)
      const combinedRanges = [...ranges]
      for (const candidate of retryRanges) {
        const duplicateIndex = combinedRanges.findIndex(existing =>
          Math.abs(existing.y - candidate.y) <= image.height * 0.015,
        )
        if (duplicateIndex < 0) combinedRanges.push(candidate)
        else {
          const existing = combinedRanges[duplicateIndex]
          if (existing && existing.observedValue != null && candidate.observedValue == null) {
            combinedRanges[duplicateIndex] = candidate
          }
        }
      }
      if (layout === 'three-column') correctRangeBoundaries(ranges)
      if (combinedRanges.length > ranges.length) {
        ranges = combinedRanges.sort((first, second) => first.y - second.y)
      }
    }
  }
  const rowPositions = layout === 'four-column'
    ? [...minimums, ...maximums].sort((first, second) => first.y - second.y)
    : ranges
  const recognizedPoints = rawPoints
  const points = rowPositions.length > 0
    ? recognizedPoints.filter(point =>
        point.y >= rowPositions[0].y - image.height * 0.03
          && point.y <= rowPositions[rowPositions.length - 1].y + image.height * 0.03,
      )
    : []
  let labels = findLines('remarks').flatMap(line => {
    const text = parseOcrRemark(line.text)
    if (!text) return []
    return [{ text, x0: line.x0, x1: line.x1, y: line.y }]
  }).sort((first, second) => first.y - second.y)
    .filter((label, index, values) => index === 0
      || label.text !== values[index - 1].text
      || Math.abs(label.y - values[index - 1].y) > image.height * 0.02)
  if (labels.length === 0 && rowPositions.length > 0) {
    const remarksRegion = regions.find(region => region.field === 'remarks')
    if (remarksRegion) {
      labels = headerLines.flatMap(line => {
        const text = parseOcrRemark(line.text)
        const insideRemarkColumn = line.x0 >= remarksRegion.left * headerWidth - headerWidth * 0.02
          && line.x0 <= remarksRegion.right * headerWidth
        const insideDataRows = line.y >= rowPositions[0].y - image.height * 0.03
          && line.y <= rowPositions[rowPositions.length - 1].y + image.height * 0.03
        if (!text || !insideRemarkColumn || !insideDataRows) return []
        return [{ text, x0: line.x0, x1: line.x1, y: line.y }]
      }).sort((first, second) => first.y - second.y)
        .filter((label, index, values) => index === 0
          || label.text !== values[index - 1].text
          || Math.abs(label.y - values[index - 1].y) > image.height * 0.02)
    }
  }
  const rangeCount = layout === 'four-column' ? minimums.length : ranges.length
  if (
    rangeCount < 2
    || rangeCount !== points.length
    || (layout === 'four-column' && rangeCount !== maximums.length)
    || labels.length === 0
  ) {
    const boundCount = layout === 'four-column' ? `, ${maximums.length} maximums` : ''
    throw new Error(`Could not match the detected ${layout} grading-scale columns (${rangeCount} ranges${boundCount}, ${points.length} grades, ${labels.length} remarks). Use a clear image or upload an .xlsx file instead.`)
  }

  const rows: OcrPartialScaleRow[] = Array.from({ length: rangeCount }, (_, index) => {
    const range = layout === 'three-column' ? ranges[index] : null
    const targetY = range?.y ?? minimums[index]?.y
    const point = targetY == null ? null : takeNearest(points, targetY)
    const maximum = layout === 'four-column' && targetY != null
      ? takeNearest(maximums, targetY)?.value ?? null
      : range?.maximum ?? null
    const minimum = layout === 'four-column' ? minimums[index]?.value ?? null : range?.minimum ?? null
    if (minimum == null || maximum == null || !point) {
      throw new Error(`Could not align the ${layout} grading-scale columns. Use a clear image or upload an .xlsx file instead.`)
    }
    return {
      minimum,
      maximum,
      gradePoint: point.gradePoint,
      remarks: '',
      x0: 0,
      x1: 1,
      y: point.y,
      observedRangeValue: range?.observedValue,
    }
  })

  assignMergedRemarks(rows, labels)
  const importedRows = layout === 'three-column'
    ? completePartialRanges(rows)
    : rows.map(({ minimum, maximum, gradePoint, remarks }) => ({
        minimum: minimum ?? Number.NaN,
        maximum: maximum ?? Number.NaN,
        gradePoint,
        remarks,
      }))
  const error = validateRows(importedRows)
  if (error) throw new Error(`The grading table was detected, but the extracted rows need correction: ${error}`)
  return importedRows
}

async function extractImageRows(file: File, onProgress: ImportProgress): Promise<GradingScaleRow[]> {
  const image = await createImageBitmap(file)
  const pixels = image.width * image.height
  if (pixels > MAX_IMAGE_PIXELS) {
    image.close()
    throw new Error('Image is too large to process. Use an image under 20 megapixels.')
  }

  let worker: Awaited<ReturnType<typeof createWorker>> | null = null
  try {
    worker = await createWorker('eng', 1, {
      logger: (event) => {
        if (event.status === 'loading tesseract core') onProgress('Preparing image reader...')
        else if (event.status === 'loading language traineddata') onProgress('Loading English recognition data...')
        else if (event.status === 'recognizing text') onProgress(`Reading grading table... ${Math.round(event.progress * 100)}%`)
      },
    })
    onProgress('Enhancing image for local text recognition...')
    const fullImage = prepareImageCanvas(image, false)
    const tableCrop = prepareImageCanvas(image, true)
    await worker.setParameters({
      tessedit_pageseg_mode: Tesseract.PSM.SPARSE_TEXT,
      user_defined_dpi: '300',
    })
    const fullHeaderLines = readOcrLines((await worker.recognize(fullImage, {}, { text: true, blocks: true })).data)
    let headerLines = fullHeaderLines
    let layout = detectImageScaleLayout(fullHeaderLines)
    if (!layout) {
      await worker.setParameters({
        tessedit_pageseg_mode: Tesseract.PSM.SINGLE_BLOCK,
        user_defined_dpi: '300',
      })
      const cropHeaderLines = readOcrLines((await worker.recognize(tableCrop, {}, { text: true, blocks: true })).data)
      layout = detectImageScaleLayout(cropHeaderLines)
      if (layout) {
        headerLines = cropHeaderLines.map(line => ({
          ...line,
          x0: (line.x0 / tableCrop.width * 0.8 + 0.1) * fullImage.width,
          x1: (line.x1 / tableCrop.width * 0.8 + 0.1) * fullImage.width,
          y: (line.y / tableCrop.height * 0.7 + 0.12) * fullImage.height,
          words: line.words?.map(word => ({
            ...word,
            x0: (word.x0 / tableCrop.width * 0.8 + 0.1) * fullImage.width,
            x1: (word.x1 / tableCrop.width * 0.8 + 0.1) * fullImage.width,
            y: (word.y / tableCrop.height * 0.7 + 0.12) * fullImage.height,
          })),
        }))
      }
    }
    if (!layout) {
      throw new Error('The image must show either Minimum Grade, Maximum Grade, Grade Range, and Remarks headers, or Range, Grade, and Remarks headers.')
    }

    let firstError: unknown
    try {
      return extractSpatialRows(fullHeaderLines, layout, fullImage.width, fullImage.height)
    } catch (error) {
      firstError = error
    }

    try {
      return await extractRecognizedRows(worker, fullImage, Tesseract.PSM.SPARSE_TEXT)
    } catch (error) {
      firstError ??= error
    }

    onProgress('Checking the table crop...')
    try {
      return await extractRecognizedRows(worker, tableCrop, Tesseract.PSM.SINGLE_BLOCK)
    } catch (error) {
      firstError = error
    }

    onProgress('Reading table columns separately...')
    try {
      return await extractColumnRows(worker, image, layout, headerLines, fullImage.width, fullImage.height)
    } catch (columnError) {
      if (columnError instanceof Error && columnError.message.startsWith('The grading table was detected')) throw columnError
      if (firstError instanceof Error && firstError.message.startsWith('The grading table was detected')) throw firstError
      throw columnError
    }
  } finally {
    image.close()
    if (worker) await worker.terminate()
  }
}

export async function importGradingScaleFile(file: File, onProgress: ImportProgress): Promise<GradingScaleRow[]> {
  if (file.size === 0) throw new Error('The selected file is empty.')
  if (file.size > MAX_FILE_SIZE) throw new Error('The file is too large. Maximum upload size is 10 MB.')

  const extension = getExtension(file.name)
  if (extension === '.xlsx') {
    onProgress('Reading Excel grading scale...')
    return extractWorkbookRows(file)
  }
  if (IMAGE_EXTENSIONS.has(extension)) {
    const expectedMime = ['.jpg', '.jpeg', '.jpe'].includes(extension) ? 'image/jpeg' : `image/${extension.slice(1)}`
    if (file.type && file.type !== expectedMime) throw new Error('The selected image file type does not match its extension.')
    onProgress('Preparing image recognition...')
    return extractImageRows(file, onProgress)
  }
  throw new Error('Unsupported file type. Upload an .xlsx Excel file or a PNG, JPG, JPEG, JPE, or WEBP image of a grading scale.')
}
