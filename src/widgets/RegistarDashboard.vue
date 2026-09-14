<template>
  <div class="registrar-root">
    <aside class="registrar-sidebar">
      <div class="seal-block">
        <img :src="colegioLogo" alt="Colegio de Montalban" />
      </div>

      <div class="sidebar-rule"></div>

      <nav class="registrar-nav" aria-label="Registrar navigation">
        <button
          class="nav-button active"
          type="button"
          aria-label="Grade management"
          @click="currentView = 'institute'"
        >
          <span class="active-bar"></span>
          <svg viewBox="0 0 24 24" aria-hidden="true">
            <rect x="4" y="4" width="5" height="5" />
            <rect x="15" y="4" width="5" height="5" />
            <rect x="4" y="15" width="5" height="5" />
            <rect x="15" y="15" width="5" height="5" />
          </svg>
        </button>
        <button class="nav-button" type="button" aria-label="Grade sheet">
          <svg viewBox="0 0 24 24" aria-hidden="true">
            <path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20" />
            <path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2Z" />
          </svg>
        </button>
      </nav>

      <div class="sidebar-bottom">
        <div class="sidebar-rule"></div>
        <div class="registrar-profile">
          <svg viewBox="0 0 24 24" aria-hidden="true">
            <circle cx="12" cy="8" r="4" />
            <path d="M4 21a8 8 0 0 1 16 0" />
          </svg>
          <span>{{ props.user?.name || 'REGISTRAR' }}</span>
        </div>
        <button class="logout-button" type="button" aria-label="Sign out" @click="$emit('signout')">
          <svg viewBox="0 0 24 24" aria-hidden="true">
            <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4" />
            <path d="m16 17 5-5-5-5" />
            <path d="M21 12H9" />
          </svg>
        </button>
      </div>
    </aside>

    <main class="registrar-page">
      <header class="registrar-header">
        <h1>GRADE MANAGEMENT</h1>
      </header>

      <section v-if="currentView === 'institute'" class="institute-view">
        <div class="building-lines" aria-hidden="true"></div>
        <div class="institute-content">
          <h2>INSTITUTE</h2>
          <div class="institute-grid">
            <button
              v-for="institute in institutes"
              :key="institute.id"
              class="institute-card"
              :style="{ '--accent': institute.color }"
              type="button"
              @click="selectInstitute(institute)"
            >
              <div class="card-accent"></div>
              <div class="card-white"></div>
              <div class="institute-seal" :class="institute.id">
                <img :src="colegioLogo" :alt="institute.name" />
                <span>{{ institute.short }}</span>
              </div>
            </button>
          </div>
        </div>
      </section>

      <section v-else class="management-view">
        <h2 class="institute-title">{{ selectedInstitute.name }}</h2>

        <div class="management-panel">
          <div class="tabs-row">
            <button class="tab-button" :class="{ active: activeTab === 'approval' }" type="button" @click="activeTab = 'approval'">
              <svg viewBox="0 0 24 24" aria-hidden="true">
                <path d="m9 12 2 2 4-5" />
                <circle cx="12" cy="12" r="9" />
              </svg>
              Grade Approval
            </button>
            <button class="tab-button" :class="{ active: activeTab === 'release' }" type="button" @click="activeTab = 'release'">
              <svg viewBox="0 0 24 24" aria-hidden="true">
                <rect x="4" y="5" width="16" height="15" rx="2" />
                <path d="M8 3v4M16 3v4M4 10h16" />
              </svg>
              Release &amp; Schedule
            </button>
            <button class="tab-button" :class="{ active: activeTab === 'repository' }" type="button" @click="activeTab = 'repository'">
              <svg viewBox="0 0 24 24" aria-hidden="true">
                <path d="M6 3h9l3 3v15H6Z" />
                <path d="M14 3v4h4M9 12h6M9 16h6" />
              </svg>
              Grade Repository
            </button>
          </div>

          <div v-if="activeTab === 'approval'" class="status-grid">
            <article v-for="card in summaryCards" :key="card.label" class="status-card">
              <div>
                <h3>{{ card.label }}</h3>
                <strong>{{ card.count }}</strong>
                <p>{{ card.note }}</p>
              </div>
              <div class="status-icon" v-html="card.icon"></div>
            </article>
            <article class="calendar-card">
              <div class="calendar-heading">
                <b>Calendar</b>
                <button type="button" aria-label="Open calendar">...</button>
              </div>
              <div class="calendar-days">
                <span v-for="day in calendarDays" :key="day.label" :class="{ today: day.today, scheduled: day.scheduled }">
                  <small>{{ day.label }}</small>
                  <strong>{{ day.date }}</strong>
                </span>
              </div>
              <b class="calendar-month">SEPTEMBER</b>
            </article>
          </div>

          <div v-if="activeTab === 'approval'" class="approval-table-box">
            <div class="toolbar">
              <label class="search-box">
                <svg viewBox="0 0 24 24" aria-hidden="true">
                  <circle cx="11" cy="11" r="7" />
                  <path d="m21 21-5-5" />
                </svg>
                <input v-model="search" placeholder="Search by class, professor, section..." />
              </label>

              <div class="filter-group">
                <svg viewBox="0 0 24 24" aria-hidden="true">
                  <path d="M3 5h18l-7 8v5l-4 2v-7Z" />
                </svg>
                <select v-model="statusFilter" aria-label="Filter by status">
                  <option>All Status</option>
                  <option>Submitted</option>
                  <option>Approved</option>
                  <option>Published</option>
                </select>
              </div>
            </div>

            <section v-for="year in yearSections" :key="year.label" class="year-section" :class="{ expanded: expandedYears.includes(year.label) }">
              <button class="year-heading" type="button" @click="toggleYear(year.label)">
                <strong><span class="year-chevron">{{ expandedYears.includes(year.label) ? '&#8964;' : '&#8250;' }}</span>{{ year.label }}</strong>
                <span>{{ year.sheets.length }} submission(s)</span>
              </button>
              <div v-if="expandedYears.includes(year.label)" class="table-scroll">
                <table class="approval-table">
                  <thead>
                    <tr>
                      <th>SEM</th>
                      <th>DATE SUBMITTED</th>
                      <th>PROFESSOR</th>
                      <th>COURSE</th>
                      <th>SECTION</th>
                      <th>STUDENTS</th>
                      <th>STATUS</th>
                      <th>ACTION</th>
                    </tr>
                  </thead>
                  <tbody>
                    <tr v-for="sheet in year.sheets" :key="sheet.id || `${sheet.code}-${sheet.section}`">
                      <td>{{ semesterNumber(sheet.semester) }}</td>
                      <td>{{ sheet.submitted }}</td>
                      <td>{{ sheet.professor }}</td>
                      <td><strong>{{ sheet.code }}</strong></td>
                      <td>{{ sheet.section }}</td>
                      <td>{{ sheet.students }}</td>
                      <td><span class="submitted-pill">{{ sheet.status }}</span></td>
                      <td>
                        <button class="review-button" type="button" @click="openReview(sheet)">
                          <svg viewBox="0 0 24 24" aria-hidden="true">
                            <path d="M2 12s3.5-6 10-6 10 6 10 6-3.5 6-10 6S2 12 2 12Z" />
                            <circle cx="12" cy="12" r="3" />
                          </svg>
                          Review
                        </button>
                      </td>
                    </tr>
                    <tr v-if="year.sheets.length === 0">
                      <td colspan="8" class="empty-cell">No grade sheets found.</td>
                    </tr>
                  </tbody>
                </table>
              </div>
            </section>
          </div>

          <section v-if="activeTab === 'approval'" class="registrar-students-box">
            <div class="registrar-students-heading">
              <div>
                <h3>Student List</h3>
                <p>Add the students who will be used in grade sheets.</p>
              </div>
            </div>
            <div class="student-list-table">
              <div class="student-list-header">
                <strong>STUDENT ID</strong>
                <strong>STUDENT NAME</strong>
                <strong>INSTITUTE</strong>
                <span>ACTION</span>
              </div>
              <form class="student-add-row" @submit.prevent="saveStudent">
                <input v-model.trim="studentForm.student_id" placeholder="Enter student ID" required />
                <input v-model.trim="studentForm.name" placeholder="Enter student name" required />
                <input v-model.trim="studentForm.institute" placeholder="Enter student institute" required />
                <button type="submit">+ Add Student</button>
              </form>
              <div v-for="student in students" :key="student.id" class="student-list-row">
                <strong>{{ student.student_id }}</strong>
                <span>{{ student.name }}</span>
                <span>{{ student.institute || '-' }}</span>
                <span class="student-added-label">Added</span>
              </div>
              <p v-if="students.length === 0" class="student-list-empty">No students added yet.</p>
            </div>
          </section>

          <div v-else-if="activeTab === 'release'" class="release-panel">
            <h3>Grade Release Settings</h3>
            <div class="release-content">
              <form class="release-form" @submit.prevent="addRelease">
                <p class="release-form-title">+ Schedule New Release</p>
                <div class="release-fields">
                  <label>
                    School Year
                    <input v-model="releaseForm.schoolYear" type="text" />
                  </label>
                  <label>
                    Semester
                    <select v-model="releaseForm.semester">
                      <option>Semester</option>
                      <option>1st Semester</option>
                      <option>2nd Semester</option>
                    </select>
                  </label>
                  <label>
                    Year Level
                    <select v-model="releaseForm.yearLevel">
                      <option>Year</option>
                      <option>1st Year</option>
                      <option>2nd Year</option>
                      <option>3rd Year</option>
                      <option>4th Year</option>
                    </select>
                  </label>
                  <label>
                    Released Date
                    <input v-model="releaseForm.releasedDate" type="date" />
                  </label>
                </div>
                <label class="course-field">
                  Course <span>(multi-select)</span>
                  <div class="course-chips">
                    <button v-for="course in courses" :key="course" type="button" :class="{ selected: selectedCourses.includes(course) }" @click="toggleCourse(course)">
                      {{ course }}
                    </button>
                  </div>
                </label>
                <button class="add-release-button" type="submit">+ Add</button>
              </form>
              <article class="published-card">
                <div class="published-ring"></div>
                <strong>{{ publishedCount }}</strong>
                <div>
                  <b>Published</b>
                  <small>of 10 Ready to publish</small>
                </div>
              </article>
            </div>

            <div class="release-year-list">
              <section v-for="year in releaseYearSections" :key="year.label" class="release-year-section">
                <button class="release-year-heading" type="button" @click="toggleReleaseYear(year.label)">
                  <span class="year-chevron">{{ expandedReleaseYears.includes(year.label) ? '&#8964;' : '&#8250;' }}</span>
                  <strong>{{ year.label }}</strong>
                </button>
                <div v-if="expandedReleaseYears.includes(year.label)" class="release-schedule-table">
                  <div class="release-schedule-row release-schedule-header">
                    <strong>School Year</strong>
                    <strong>Semester</strong>
                    <strong>Year Level</strong>
                    <strong>Course</strong>
                    <strong>Released Date</strong>
                    <strong>Status</strong>
                    <strong>Action</strong>
                  </div>
                  <div v-for="schedule in year.schedules" :key="schedule.id" class="release-schedule-row">
                    <span>{{ schedule.schoolYear }}</span>
                    <span>{{ schedule.semester }}</span>
                    <span>{{ schedule.yearLevel }}</span>
                    <span class="schedule-courses">
                      <small v-for="course in schedule.courses" :key="course">{{ course }}</small>
                    </span>
                    <span>{{ schedule.releasedDate || '-' }}</span>
                    <span><b class="schedule-status" :class="schedule.status.toLowerCase()">{{ schedule.status }}</b></span>
                    <span>
                      <button v-if="schedule.status === 'Scheduled'" class="release-now-button" type="button" @click="releaseNow(schedule)">Release Now</button>
                      <span v-else class="released-label">Released</span>
                    </span>
                  </div>
                  <p v-if="year.schedules.length === 0" class="release-empty">No release schedule added.</p>
                </div>
              </section>
            </div>

            <h3 class="period-title">Grade Period Setting</h3>
            <div class="period-content">
              <form class="period-form" @submit.prevent="saveGradePeriod">
                <p class="period-form-title">&#128197; Schedule New Grade Period</p>
                <div class="period-fields">
                  <label>School Year<input v-model="periodForm.schoolYear" type="text" /></label>
                  <label>Semester<select v-model="periodForm.semester"><option>Semester</option><option>1st Semester</option><option>2nd Semester</option></select></label>
                  <label>Midterm Opens On<input v-model="periodForm.midtermOpens" type="date" /></label>
                  <label>Finals Opens On<input v-model="periodForm.finalsOpens" type="date" /></label>
                  <button class="save-period-button" type="submit">&#128190; Save Sched</button>
                </div>
              </form>
              <section class="active-schedules">
                <h4>Active Schedules</h4>
                <div v-for="period in gradePeriods" :key="period.id" class="active-schedule-row">
                  <div><strong>A.Y {{ period.schoolYear }} &nbsp;-&nbsp; {{ period.semester }}</strong><small>Midterm: {{ period.midtermOpens || '-' }} &nbsp;|&nbsp; Finals: {{ period.finalsOpens || '-' }}</small></div>
                  <span class="period-state">Midterm Open</span>
                  <span class="period-state locked">Finals Locked</span>
                </div>
                <p v-if="gradePeriods.length === 0" class="release-empty">No active grade period schedule.</p>
              </section>
            </div>
          </div>

          <div v-else class="repository-panel">
            <h3>Grade Repository</h3>
            <p>No published grade sheets available.</p>
          </div>
        </div>
      </section>
    </main>

    <div v-if="reviewSheet" class="review-overlay" @click.self="reviewSheet = null">
      <section class="review-modal" role="dialog" aria-modal="true" aria-labelledby="review-title">
        <header class="review-header">
          <h2 id="review-title">GRADE SHEET REVIEW</h2>
          <button class="close-button" type="button" aria-label="Close review" @click="reviewSheet = null">
            <svg viewBox="0 0 24 24" aria-hidden="true">
              <path d="M18 6 6 18M6 6l12 12" />
            </svg>
          </button>
        </header>

        <div class="review-body">
          <div class="review-top">
            <div>
              <h3><span>{{ reviewSheet.code }}</span> - {{ reviewSheet.subject }}</h3>
              <p>{{ reviewSheet.section }} &bull; {{ reviewSheet.semester }}</p>
              <p>Professor: {{ reviewSheet.professor }}</p>
            </div>
            <span class="review-status">{{ reviewSheet.status }}</span>
          </div>

          <div class="review-summary">
            <div>
              <span>SUBMITTED</span>
              <strong>{{ reviewSheet.submitted }}</strong>
            </div>
            <div>
              <span>REVIEWED</span>
              <strong>-</strong>
            </div>
            <div>
              <span>REVIEWED BY</span>
              <strong>-</strong>
            </div>
            <div>
              <span>PUBLISHED</span>
              <strong>-</strong>
            </div>
          </div>

          <div class="review-table-wrap">
            <table class="review-table">
              <thead>
                <tr>
                  <th>STUDENT ID</th>
                  <th>NAME</th>
                  <th>MIDTERM</th>
                  <th>FINALS</th>
                  <th>FINAL<br />GRADE</th>
                  <th>GRADE<br />POINT</th>
                  <th>REMARKS</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="student in reviewStudents" :key="student.rowId">
                  <td>{{ student.id }}</td>
                  <td>{{ student.name }}</td>
                  <td>{{ student.midterm }}</td>
                  <td>{{ student.finals }}</td>
                  <td>{{ student.finalGrade }}</td>
                  <td><strong>{{ student.gradePoint }}</strong></td>
                  <td><span class="passed-pill">{{ student.remarks }}</span></td>
                </tr>
              </tbody>
            </table>

            <div class="modal-actions">
              <button class="approve-button" type="button" @click="changeStatus('Approved')">
                <svg viewBox="0 0 24 24" aria-hidden="true">
                  <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2v-8" />
                  <path d="m14 15 2 2 5-6" />
                  <path d="M14 2v6h6" />
                </svg>
                Approve Grades
              </button>
              <button class="reject-button" type="button" @click="changeStatus('Submitted')">
                <svg viewBox="0 0 24 24" aria-hidden="true">
                  <circle cx="12" cy="12" r="9" />
                  <path d="m15 9-6 6M9 9l6 6" />
                </svg>
                Reject &amp; Return
              </button>
            </div>
          </div>
        </div>
      </section>
    </div>
  </div>
</template>

<script setup>
import { computed, onMounted, ref } from 'vue'
import { addStudent, createGradePeriod, createGradeSchedule, getGradeSchedules, getGradeSheets, getInstitutes, getStudents, releaseGradeSchedule, updateGradeSheetStatus } from '../services/dataService'
import colegioLogo from '../logo/The_Colegio_de_Montalban_Seal (1).png'

const props = defineProps({ user: { type: Object, default: null } })
defineEmits(['signout'])

const currentView = ref('institute')
const selectedInstitute = ref(null)
const search = ref('')
const statusFilter = ref('All Status')
const reviewSheet = ref(null)
const activeTab = ref('approval')
const expandedYears = ref(['1st Year'])
const publishedCount = computed(() => gradeSheets.value.filter(sheet => sheet.status === 'Published').length)
const courses = ['BSBA HRM - Business Administration', 'BS Entrep - Entrepreneurship']
const selectedCourses = ref([...courses])
const releaseForm = ref({
  schoolYear: '2025-2026',
  semester: 'Semester',
  yearLevel: 'Year',
  releasedDate: '',
})
const expandedReleaseYears = ref(['A.Y 2025 - 2026'])
const periodForm = ref({
  schoolYear: '2025-2026',
  semester: 'Semester',
  midtermOpens: '',
  finalsOpens: '',
})
const gradePeriods = computed(() => schedules.value.filter(schedule => schedule.yearLevel === 'Grade Period'))
const institutes = getInstitutes()

const gradeSheets = ref([])
const schedules = ref([])
const reviewStudents = computed(() => reviewSheet.value?.student_records || [])
const students = ref([])
const studentForm = ref({ student_id: '', name: '' })

onMounted(async () => {
  try {
    ;[gradeSheets.value, students.value, schedules.value] = await Promise.all([getGradeSheets(), getStudents(), getGradeSchedules()])
  } catch {
    gradeSheets.value = []
    students.value = []
    schedules.value = []
  }
})

const calendarDays = computed(() => {
  const today = new Date()
  const start = new Date(today)
  start.setDate(today.getDate() - ((today.getDay() + 6) % 7))
  return Array.from({ length: 5 }, (_, index) => {
    const date = new Date(start)
    date.setDate(start.getDate() + index)
    const dateValue = date.toISOString().slice(0, 10)
    return { label: date.toLocaleDateString('en-US', { weekday: 'short' }), date: date.getDate(), today: dateValue === today.toISOString().slice(0, 10), scheduled: schedules.value.some(schedule => schedule.releasedDate === dateValue) }
  })
})

const summaryCards = computed(() => {
  const pending = gradeSheets.value.filter(s => s.status === 'Submitted').length
  const approved = gradeSheets.value.filter(s => s.status === 'Approved').length
  return [
    {
      label: 'Pending Review',
      count: pending,
      note: 'of 10 Waiting for approval',
      icon: `<svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="9"></circle><path d="M12 7v6l4 3"></path></svg>`,
    },
    {
      label: 'Approved',
      count: approved,
      note: 'of 10 Ready to publish',
      icon: `<svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="9"></circle><path d="m8 12 3 3 5-6"></path></svg>`,
    },
  ]
})

const filteredSheets = computed(() => {
  const query = search.value.trim().toLowerCase()
  return gradeSheets.value.filter((sheet) => {
    const matchesStatus = statusFilter.value === 'All Status' || sheet.status === statusFilter.value
    const matchesSearch =
      !query ||
      sheet.code.toLowerCase().includes(query) ||
      sheet.subject.toLowerCase().includes(query) ||
      sheet.professor.toLowerCase().includes(query) ||
      sheet.section.toLowerCase().includes(query)

    return matchesStatus && matchesSearch
  })
})

const yearSections = computed(() => [1, 2, 3, 4].map(year => ({
  label: `${year}${year === 1 ? 'st' : year === 2 ? 'nd' : year === 3 ? 'rd' : 'th'} Year`,
  sheets: filteredSheets.value.filter(sheet => new RegExp(`(^|[- ])${year}[A-D]?($|[- ])`, 'i').test(sheet.section)),
})))

const releaseYearSections = computed(() => {
  const schoolYears = [...new Set([...schedules.value.filter(schedule => schedule.yearLevel !== 'Grade Period').map(schedule => schedule.schoolYear)])]
  return schoolYears.map(schoolYear => ({
    label: `A.Y ${schoolYear.replace('-', ' - ')}`,
    schedules: schedules.value.filter(schedule => schedule.schoolYear === schoolYear && schedule.yearLevel !== 'Grade Period'),
  }))
})

function semesterNumber(semester) {
  return semester?.toLowerCase().includes('2nd') ? '2' : '1'
}

function toggleYear(label) {
  expandedYears.value = expandedYears.value.includes(label)
    ? expandedYears.value.filter(year => year !== label)
    : [...expandedYears.value, label]
}

function toggleReleaseYear(label) {
  expandedReleaseYears.value = expandedReleaseYears.value.includes(label)
    ? expandedReleaseYears.value.filter(year => year !== label)
    : [...expandedReleaseYears.value, label]
}

function selectInstitute(institute) {
  selectedInstitute.value = institute
  currentView.value = 'management'
}

function openReview(sheet) {
  reviewSheet.value = sheet
}

async function changeStatus(status) {
  if (!reviewSheet.value?.id) return

  try {
    const updated = await updateGradeSheetStatus(reviewSheet.value.id, status)
    const index = gradeSheets.value.findIndex(sheet => sheet.id === updated.id)
    if (index !== -1) gradeSheets.value[index] = updated
    reviewSheet.value = updated
  } catch (error) {
    window.alert(error instanceof Error ? error.message : 'Unable to update grade sheet.')
  }
}

function toggleCourse(course) {
  selectedCourses.value = selectedCourses.value.includes(course)
    ? selectedCourses.value.filter(item => item !== course)
    : [...selectedCourses.value, course]
}

function addRelease() {
  if (!selectedCourses.value.length || releaseForm.value.yearLevel === 'Year' || releaseForm.value.semester === 'Semester') return

  createGradeSchedule({
    schoolYear: releaseForm.value.schoolYear,
    semester: releaseForm.value.semester,
    yearLevel: releaseForm.value.yearLevel,
    courses: [...selectedCourses.value],
    releasedDate: releaseForm.value.releasedDate,
  }).then(schedule => {
    schedules.value.unshift(schedule)
    const label = `A.Y ${schedule.schoolYear.replace('-', ' - ')}`
    if (!expandedReleaseYears.value.includes(label)) expandedReleaseYears.value.push(label)
  }).catch(error => window.alert(error instanceof Error ? error.message : 'Unable to add release schedule.'))
}

function releaseNow(schedule) {
  if (!schedule.id) return
  releaseGradeSchedule(schedule.id).then(updated => {
    const index = schedules.value.findIndex(item => item.id === updated.id)
    if (index !== -1) schedules.value[index] = updated
  }).catch(error => window.alert(error instanceof Error ? error.message : 'Unable to release schedule.'))
}

function saveGradePeriod() {
  if (periodForm.value.semester === 'Semester') return
  createGradePeriod(periodForm.value).then(schedule => {
    schedules.value.unshift(schedule)
  }).catch(error => window.alert(error instanceof Error ? error.message : 'Unable to save grade period.'))
}

async function saveStudent() {
  try {
    students.value.unshift(await addStudent(studentForm.value))
    studentForm.value = { student_id: '', name: '' }
  } catch (error) {
    window.alert(error instanceof Error ? error.message : 'Unable to add student.')
  }
}
</script>

<style scoped>
*, *::before, *::after {
  box-sizing: border-box;
}

.registrar-root {
  position: absolute;
  inset: 0;
  display: flex;
  width: 100vw;
  height: 100vh;
  overflow: hidden;
  background: #ffffff;
  color: #000000;
  font-family: Arial, Helvetica, sans-serif;
  text-align: left;
}

.registrar-sidebar {
  width: 111px;
  flex: 0 0 111px;
  display: flex;
  flex-direction: column;
  align-items: center;
  padding: 52px 0 30px;
  background: linear-gradient(180deg, #124a29 0%, #124b27 58%, #72a900 100%);
  color: #ffffff;
  z-index: 5;
}

.seal-block img {
  width: 74px;
  height: 74px;
  display: block;
  object-fit: contain;
}

.sidebar-rule {
  width: 80px;
  height: 1px;
  margin: 34px 0 60px;
  background: rgba(255, 255, 255, 0.8);
}

.registrar-nav {
  display: flex;
  width: 100%;
  flex-direction: column;
  align-items: center;
  gap: 24px;
}

.nav-button,
.logout-button {
  appearance: none;
  border: 0;
  background: transparent;
  color: inherit;
  cursor: pointer;
}

.nav-button {
  position: relative;
  width: 72px;
  height: 56px;
  display: grid;
  place-items: center;
  border-radius: 8px;
}

.nav-button.active {
  background: rgba(84, 165, 105, 0.65);
}

.active-bar {
  position: absolute;
  left: -10px;
  width: 7px;
  height: 38px;
  border-radius: 0 8px 8px 0;
  background: #ffdf4f;
}

.nav-button svg,
.logout-button svg,
.registrar-profile svg {
  width: 28px;
  height: 28px;
  fill: none;
  stroke: currentColor;
  stroke-width: 2;
  stroke-linecap: round;
  stroke-linejoin: round;
}

.nav-button.active svg {
  color: #ffe66b;
}

.sidebar-bottom {
  width: 100%;
  margin-top: auto;
  display: flex;
  flex-direction: column;
  align-items: center;
}

.sidebar-bottom .sidebar-rule {
  margin: 0 0 20px;
}

.registrar-profile {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 6px;
  margin-bottom: 36px;
  color: #ffffff;
  font-size: 13px;
  font-weight: 800;
}

.registrar-profile svg {
  width: 38px;
  height: 38px;
  stroke-width: 1.8;
}

.logout-button svg {
  width: 31px;
  height: 31px;
}

.registrar-page {
  flex: 1;
  min-width: 0;
  display: flex;
  flex-direction: column;
  background: #ffffff;
  overflow: hidden;
}

.registrar-header {
  height: 80px;
  flex: 0 0 80px;
  display: flex;
  align-items: center;
  padding: 0 31px;
  background: #ffffff;
  border-bottom: 1px solid #dadada;
  box-shadow: 0 2px 5px rgba(0, 0, 0, 0.2);
  z-index: 3;
}

.registrar-header h1 {
  margin: 0;
  color: #000000;
  font-size: 31px;
  line-height: 1;
  font-weight: 800;
  letter-spacing: 0;
}

.institute-view {
  position: relative;
  flex: 1;
  overflow: hidden;
  background:
    linear-gradient(rgba(174, 216, 195, 0.72), rgba(174, 216, 195, 0.72)),
    linear-gradient(118deg, transparent 0 42%, rgba(255, 255, 255, 0.4) 42% 44%, transparent 44% 100%),
    linear-gradient(90deg, #cedbd2 0%, #9eb8aa 52%, #7f9d90 100%);
}

.building-lines {
  position: absolute;
  inset: 0;
  opacity: 0.44;
  background:
    linear-gradient(90deg, transparent 0 7%, rgba(255, 255, 255, 0.55) 7% 8%, transparent 8% 15%, rgba(255, 255, 255, 0.55) 15% 16%, transparent 16% 100%),
    repeating-linear-gradient(0deg, transparent 0 84px, rgba(255, 255, 255, 0.8) 84px 91px, transparent 91px 156px),
    repeating-linear-gradient(90deg, transparent 0 90px, rgba(24, 58, 41, 0.28) 90px 96px, transparent 96px 168px);
  transform: skewX(-8deg) scale(1.12);
  transform-origin: 20% 20%;
}

.institute-content {
  position: relative;
  z-index: 1;
  display: flex;
  min-height: 100%;
  flex-direction: column;
  align-items: center;
  padding: 151px 78px 80px;
}

.institute-content h2 {
  margin: 0 0 28px;
  color: #114c2b;
  font-size: 51px;
  line-height: 1;
  font-weight: 900;
  letter-spacing: 0;
  text-shadow: 2px 3px 0 rgba(255, 255, 255, 0.45), 0 2px 2px rgba(0, 0, 0, 0.25);
}

.institute-grid {
  width: 100%;
  max-width: 997px;
  display: grid;
  grid-template-columns: repeat(3, minmax(240px, 1fr));
  gap: 73px;
}

.institute-card {
  position: relative;
  height: 196px;
  overflow: hidden;
  border: 1px solid rgba(24, 24, 24, 0.35);
  border-radius: 34px;
  background: #f4f4f4;
  box-shadow: 0 4px 3px rgba(0, 0, 0, 0.35);
  cursor: pointer;
}

.card-accent {
  position: absolute;
  inset: 0 0 auto;
  height: 88px;
  background: var(--accent);
}

.card-white {
  position: absolute;
  left: -7%;
  right: -7%;
  bottom: -1px;
  height: 105px;
  border-radius: 50% 50% 0 0 / 40% 40% 0 0;
  background: rgba(249, 249, 249, 0.92);
}

.institute-seal {
  position: absolute;
  inset: 0;
  display: grid;
  place-items: center;
}

.institute-seal img {
  width: 150px;
  height: 150px;
  object-fit: contain;
  opacity: 0.72;
}

.institute-seal span {
  position: absolute;
  color: #0c4226;
  font-size: 25px;
  font-weight: 900;
  letter-spacing: 0;
}

.management-view {
  flex: 1;
  overflow: auto;
  padding: 31px 30px 46px;
  background: #f9f9f9;
}

.institute-title {
  margin: 0 0 30px;
  text-align: center;
  color: #155423;
  font-size: 36px;
  line-height: 1;
  font-weight: 900;
  letter-spacing: 0;
  text-shadow: -3px 0 0 #f3df23;
}

.management-panel {
  max-width: 1110px;
  margin: 0 auto;
  overflow: hidden;
  border: 1px solid #b9b9b9;
  border-radius: 14px;
  background: #ffffff;
}

.tabs-row {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  min-height: 81px;
  border-bottom: 1px solid #bdbdbd;
}

.tab-button {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 12px;
  border: 0;
  border-right: 1px solid #efefef;
  background: #ffffff;
  color: #000000;
  font-size: 25px;
  font-weight: 500;
  cursor: default;
}

.tab-button:last-child {
  border-right: 0;
}

.tab-button.active {
  color: #173f2c;
  background: #fbfbfb;
  box-shadow: inset 0 -2px 0 #18442e;
}

.tab-button svg {
  width: 24px;
  height: 24px;
  fill: none;
  stroke: currentColor;
  stroke-width: 2;
  stroke-linecap: round;
  stroke-linejoin: round;
}

.status-grid {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: 42px;
  padding: 30px 40px 26px;
}

.calendar-card {
  min-height: 105px;
  padding: 8px 10px 5px;
  border: 1px solid #adadad;
  border-radius: 10px;
  background: #ffffff;
}

.calendar-heading {
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin-bottom: 5px;
  color: #111111;
  font-size: 14px;
}

.calendar-heading button {
  border: 0;
  color: #111111;
  background: transparent;
  font-size: 14px;
  cursor: pointer;
}

.calendar-days {
  display: grid;
  grid-template-columns: repeat(5, 1fr);
  gap: 4px;
}

.calendar-days span {
  display: flex;
  min-height: 46px;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  border-radius: 4px;
  color: #111111;
}

.calendar-days span.today {
  background: #9ae9b0;
}

.calendar-days span.scheduled {
  box-shadow: inset 0 -3px 0 #2d6d48;
}

.calendar-days small {
  font-size: 9px;
  font-weight: 700;
}

.calendar-days strong {
  font-size: 20px;
  line-height: 1;
}

.calendar-month {
  display: block;
  margin-top: 3px;
  color: #111111;
  font-size: 9px;
  text-align: center;
}

.status-card {
  min-height: 142px;
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 16px;
  padding: 18px 24px;
  border: 1px solid #adadad;
  border-radius: 13px;
  background: #ffffff;
}

.status-card h3 {
  margin: 0 0 10px;
  color: #000000;
  font-size: 24px;
  line-height: 1.1;
  font-weight: 800;
}

.status-card strong {
  display: block;
  color: #000000;
  font-size: 38px;
  line-height: 1;
  font-weight: 900;
  text-align: center;
}

.status-card p {
  margin: 13px 0 0;
  color: #777777;
  font-size: 13px;
}

.status-icon {
  width: 68px;
  height: 56px;
  flex: 0 0 68px;
  display: grid;
  place-items: center;
  border-radius: 10px;
  background: #8ee1a8;
  color: #1b633f;
}

.status-icon :deep(svg) {
  width: 34px;
  height: 34px;
  fill: none;
  stroke: currentColor;
  stroke-width: 1.7;
  stroke-linecap: round;
  stroke-linejoin: round;
}

.approval-table-box {
  margin: 0 30px 124px;
  overflow: hidden;
  border: 1px solid #a9a9a9;
  border-radius: 13px 13px 0 0;
  background: #ffffff;
}

.year-section + .year-section {
  border-top: 1px solid #bdbdbd;
}

.year-heading {
  width: 100%;
  display: flex;
  align-items: center;
  gap: 14px;
  min-height: 54px;
  padding: 0 16px;
  border: 0;
  background: #ffffff;
  text-align: left;
  cursor: pointer;
}

.year-heading strong {
  display: inline-flex;
  align-items: center;
  min-height: 38px;
  padding: 0 12px;
  border: 1px solid #b7b7b7;
  border-radius: 6px;
  color: #111111;
  font-size: 16px;
}

.year-chevron {
  display: inline-block;
  width: 18px;
  margin-right: 2px;
  font-size: 22px;
  line-height: 1;
  text-align: center;
}

.year-heading span {
  color: #555555;
  font-size: 14px;
}

.release-panel,
.repository-panel {
  margin: 0 4px 20px;
  padding: 0 18px 18px;
}

.release-panel h3,
.repository-panel h3 {
  margin: 0 0 14px;
  color: #183f2a;
  font-size: 25px;
  line-height: 1;
  font-weight: 800;
}

.release-content {
  min-height: 350px;
  padding: 16px 14px 18px;
  border: 1px solid #ababab;
  border-radius: 13px;
  box-shadow: 0 2px 2px rgba(0, 0, 0, 0.2);
}

.release-form {
  position: relative;
  width: min(765px, 100%);
  min-height: 150px;
  padding: 0 14px 12px;
  border: 1px solid #d1d1d1;
  border-radius: 7px;
  box-shadow: 0 2px 2px rgba(0, 0, 0, 0.15);
  box-sizing: border-box;
}

.release-form-title {
  margin: -1px 0 8px;
  color: #111111;
  font-size: 15px;
  font-weight: 700;
}

.release-fields {
  display: grid;
  grid-template-columns: 1.1fr 0.9fr 0.9fr 1.1fr;
  gap: 14px;
}

.release-fields label,
.course-field {
  display: flex;
  flex-direction: column;
  gap: 5px;
  color: #111111;
  font-size: 12px;
  font-weight: 700;
}

.release-fields input,
.release-fields select {
  width: 100%;
  height: 29px;
  padding: 0 8px;
  border: 1px solid #c9c9c9;
  border-radius: 5px;
  color: #333333;
  background: #ffffff;
  font-size: 11px;
  box-sizing: border-box;
}

.course-field {
  margin-top: 7px;
}

.course-field span {
  color: #777777;
  font-size: 9px;
  font-weight: 400;
}

.course-chips {
  display: flex;
  gap: 5px;
  flex-wrap: wrap;
}

.course-chips button {
  height: 25px;
  padding: 0 8px;
  overflow: hidden;
  border: 1px solid #cfcfcf;
  border-radius: 5px;
  color: #222222;
  background: #ffffff;
  font-size: 10px;
  white-space: nowrap;
  text-overflow: ellipsis;
  cursor: pointer;
}

.course-chips button.selected {
  border-color: #77a586;
  background: #f0f8f1;
}

.add-release-button {
  position: absolute;
  right: 14px;
  bottom: 11px;
  width: 132px;
  height: 29px;
  border: 0;
  border-radius: 5px;
  color: #ffffff;
  background: #4d7e5d;
  font-size: 13px;
  font-weight: 700;
  cursor: pointer;
}

.published-card {
  position: absolute;
  display: flex;
  align-items: center;
  gap: 6px;
  margin: -101px 0 0 805px;
  width: 200px;
  height: 62px;
  padding: 0 11px;
  border: 1px solid #bdbdbd;
  border-radius: 10px;
  background: #ffffff;
  box-sizing: border-box;
}

.published-ring {
  width: 23px;
  height: 23px;
  flex: 0 0 23px;
  border: 3px solid #d7e6df;
  border-top-color: #357856;
  border-radius: 50%;
}

.published-card > strong {
  color: #111111;
  font-size: 26px;
  line-height: 1;
}

.published-card b,
.published-card small {
  display: block;
  color: #111111;
}

.published-card b {
  font-size: 15px;
  line-height: 1;
}

.published-card small {
  margin-top: 3px;
  color: #777777;
  font-size: 8px;
}

.release-year-list {
  display: grid;
  gap: 16px;
  margin: 18px 2px 24px;
}

.release-year-section {
  overflow: hidden;
  border: 1px solid #b8b8b8;
  border-radius: 6px;
  background: #ffffff;
}

.release-year-heading {
  width: 100%;
  min-height: 48px;
  display: flex;
  align-items: center;
  gap: 8px;
  padding: 0 14px;
  border: 0;
  background: #ffffff;
  color: #111111;
  font-size: 20px;
  text-align: left;
  cursor: pointer;
}

.release-year-heading .year-chevron {
  margin: 0;
}

.release-schedule-table {
  overflow-x: auto;
  padding: 0 12px 10px;
}

.release-schedule-row {
  min-width: 970px;
  display: grid;
  grid-template-columns: 1.05fr 0.8fr 0.85fr 1.55fr 1.05fr 0.9fr 0.9fr;
  align-items: center;
  min-height: 52px;
  border-top: 1px solid #c6c6c6;
  color: #111111;
  font-size: 14px;
  text-align: center;
}

.release-schedule-row > * {
  padding: 5px 8px;
}

.release-schedule-header {
  min-height: 44px;
  color: #111111;
  font-size: 14px;
}

.schedule-courses {
  display: flex;
  justify-content: center;
  gap: 5px;
  flex-wrap: wrap;
}

.schedule-courses small {
  padding: 4px 8px;
  border: 1px solid #bdbdbd;
  border-radius: 999px;
  background: #ffffff;
  white-space: nowrap;
}

.schedule-status {
  display: inline-block;
  padding: 4px 11px;
  border-radius: 999px;
  background: #ffe7a2;
  font-weight: 400;
}

.schedule-status.released {
  background: #bdf7d0;
}

.release-now-button,
.save-period-button {
  border: 0;
  border-radius: 5px;
  background: #3d704d;
  color: #ffffff;
  font-weight: 700;
  cursor: pointer;
}

.release-now-button {
  padding: 7px 10px;
}

.released-label {
  color: #999999;
}

.release-empty {
  margin: 12px 0 4px;
  color: #777777;
  font-size: 13px;
  text-align: center;
}

.period-title {
  margin: 16px 0 16px;
  color: #183f2a;
  font-size: 25px;
}

.period-content {
  display: grid;
  gap: 14px;
}

.period-form,
.active-schedules {
  padding: 14px 16px;
  border: 1px solid #c9c9c9;
  border-radius: 8px;
  background: #ffffff;
  box-shadow: 0 2px 2px rgba(0, 0, 0, 0.12);
}

.period-form-title {
  margin: 0 0 14px;
  color: #111111;
  font-size: 16px;
  font-weight: 700;
}

.period-fields {
  display: grid;
  grid-template-columns: 1fr 1fr 1.25fr 1.25fr auto;
  gap: 14px;
  align-items: end;
}

.period-fields label {
  display: grid;
  gap: 5px;
  color: #111111;
  font-size: 13px;
  font-weight: 700;
}

.period-fields input,
.period-fields select {
  height: 34px;
  min-width: 0;
  padding: 0 8px;
  border: 1px solid #c9c9c9;
  border-radius: 5px;
  background: #ffffff;
  color: #555555;
}

.save-period-button {
  height: 34px;
  padding: 0 14px;
  white-space: nowrap;
}

.active-schedules h4 {
  margin: 0 0 10px;
  color: #111111;
  font-size: 16px;
}

.active-schedule-row {
  display: grid;
  grid-template-columns: 1fr auto auto;
  align-items: center;
  gap: 14px;
  padding: 10px 14px;
  border: 1px solid #dddddd;
  border-radius: 6px;
}

.active-schedule-row strong,
.active-schedule-row small {
  display: block;
}

.active-schedule-row small {
  margin-top: 4px;
  color: #777777;
  font-size: 11px;
}

.period-state {
  padding: 5px 10px;
  border-radius: 999px;
  background: #5b8f67;
  color: #ffffff;
  font-size: 13px;
  white-space: nowrap;
}

.period-state.locked {
  background: #dddddd;
  color: #111111;
}

.repository-panel {
  min-height: 350px;
}

.repository-panel p {
  color: #666666;
  font-size: 14px;
}

.toolbar {
  height: 86px;
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 20px;
  padding: 18px 16px 21px;
}

.search-box {
  width: min(550px, 60%);
  height: 46px;
  display: flex;
  align-items: center;
  gap: 10px;
  padding: 0 14px;
  border: 1px solid #303030;
  border-radius: 7px;
  background: #ffffff;
}

.search-box svg,
.filter-group svg,
.review-button svg {
  flex: 0 0 auto;
  fill: none;
  stroke: currentColor;
  stroke-width: 2;
  stroke-linecap: round;
  stroke-linejoin: round;
}

.search-box svg {
  width: 30px;
  height: 30px;
  color: #646464;
}

.search-box input {
  width: 100%;
  border: 0;
  outline: 0;
  color: #555555;
  font-size: 16px;
  background: transparent;
}

.filter-group {
  display: flex;
  align-items: center;
  gap: 10px;
}

.filter-group svg {
  width: 25px;
  height: 25px;
  color: #555555;
}

.filter-group select {
  width: 173px;
  height: 46px;
  padding: 0 12px;
  border: 1px solid #303030;
  border-radius: 7px;
  color: #111111;
  background: #ffffff;
  font-size: 16px;
  font-weight: 700;
}

.table-scroll {
  overflow-x: auto;
}

.approval-table {
  width: 100%;
  min-width: 1040px;
  border-collapse: collapse;
}

.approval-table th {
  height: 52px;
  padding: 8px 16px;
  background: #a9f3bf;
  color: #102516;
  font-size: 15px;
  line-height: 1.2;
  font-weight: 900;
  text-align: center;
}

.approval-table td {
  height: 58px;
  padding: 8px 12px;
  border-top: 1px solid #bdbdbd;
  color: #000000;
  font-size: 15px;
  text-align: center;
  vertical-align: middle;
}

.submitted-pill,
.review-status {
  display: inline-flex;
  align-items: center;
  min-height: 26px;
  padding: 3px 12px;
  border-radius: 999px;
  background: #d9d9d9;
  color: #000000;
  font-size: 14px;
  font-weight: 400;
}

.review-button {
  display: inline-flex;
  align-items: center;
  gap: 3px;
  min-height: 26px;
  padding: 2px 8px;
  border: 0;
  border-radius: 7px;
  background: #bce8ed;
  color: #226f7c;
  font-size: 14px;
  cursor: pointer;
}

.review-button svg {
  width: 16px;
  height: 16px;
}

.empty-cell {
  text-align: center;
  color: #777777;
}

.review-overlay {
  position: fixed;
  inset: 0;
  display: flex;
  align-items: flex-start;
  justify-content: center;
  background: rgba(0, 0, 0, 0.12);
  z-index: 50;
}

.review-modal {
  width: min(878px, 100vw);
  min-height: 625px;
  overflow: hidden;
  border-radius: 0 13px 13px 13px;
  background: #ffffff;
  box-shadow: 0 10px 36px rgba(0, 0, 0, 0.25);
}

.review-header {
  height: 69px;
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 0 16px;
  border-bottom: 1px solid #bcbcbc;
}

.review-header h2 {
  margin: 0;
  color: #173f2c;
  font-size: 29px;
  font-weight: 900;
  letter-spacing: 0;
}

.close-button {
  width: 36px;
  height: 36px;
  display: grid;
  place-items: center;
  border: 0;
  background: transparent;
  color: #000000;
  cursor: pointer;
}

.close-button svg {
  width: 30px;
  height: 30px;
  fill: none;
  stroke: currentColor;
  stroke-width: 4;
  stroke-linecap: round;
}

.review-body {
  padding: 18px 16px 20px;
}

.review-top {
  display: flex;
  justify-content: space-between;
  gap: 16px;
}

.review-top h3 {
  margin: 0 0 10px;
  color: #000000;
  font-size: 23px;
  line-height: 1.1;
}

.review-top h3 span {
  color: #0d542a;
}

.review-top p {
  margin: 0 0 10px;
  color: #5a5a5a;
  font-size: 22px;
  line-height: 1;
  font-weight: 800;
}

.review-summary {
  height: 78px;
  display: grid;
  grid-template-columns: repeat(4, 1fr);
  align-items: center;
  margin: 8px 5px 12px;
  border: 1px solid #90d6a7;
  border-radius: 16px;
  background: #a9f3bf;
}

.review-summary div {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 8px;
}

.review-summary span {
  color: #555555;
  font-size: 18px;
  font-weight: 400;
}

.review-summary strong {
  color: #000000;
  font-size: 18px;
  font-weight: 900;
}

.review-table-wrap {
  margin: 0 8px;
  overflow: hidden;
  border: 1px solid #d6d6d6;
  border-radius: 17px;
}

.review-table {
  width: 100%;
  border-collapse: collapse;
}

.review-table th {
  height: 61px;
  padding: 8px 14px;
  border-bottom: 1px solid #dcdcdc;
  color: #000000;
  font-size: 19px;
  line-height: 1.05;
  font-weight: 500;
  text-align: center;
}

.review-table td {
  height: 45px;
  padding: 8px 14px;
  border-bottom: 1px solid #dcdcdc;
  color: #000000;
  font-size: 16px;
  text-align: center;
}

.passed-pill {
  display: inline-flex;
  align-items: center;
  padding: 3px 10px;
  border-radius: 999px;
  background: #c9f0d7;
  color: #187038;
  font-size: 14px;
  font-weight: 900;
}

.modal-actions {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 17px;
  padding: 108px 25px 12px;
}

.approve-button,
.reject-button {
  height: 48px;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 12px;
  border-radius: 12px;
  font-size: 20px;
  cursor: pointer;
}

.approve-button {
  border: 1px solid #187038;
  background: #8fdfa6;
  color: #062e18;
}

.reject-button {
  border: 1px solid #da7780;
  background: #fff8f7;
  color: #d84f58;
}

.approve-button svg,
.reject-button svg {
  width: 27px;
  height: 27px;
  fill: none;
  stroke: currentColor;
  stroke-width: 2;
  stroke-linecap: round;
  stroke-linejoin: round;
}

.registrar-students-box {
  margin-top: 18px;
  padding: 18px;
  border: 1px solid #c8d8cc;
  border-radius: 8px;
  background: #fff;
}

.registrar-students-heading {
  display: flex;
  align-items: end;
  justify-content: space-between;
  gap: 18px;
}

.registrar-students-heading h3,
.registrar-students-heading p {
  margin: 0;
}

.registrar-students-heading p {
  margin-top: 4px;
  color: #66706a;
  font-size: 12px;
}

.student-add-row,
.student-list-header,
.student-list-row {
  display: grid;
  grid-template-columns: 150px minmax(150px, 1fr) 180px 95px;
  align-items: center;
  gap: 8px;
}

.student-list-header {
  min-height: 34px;
  padding: 0 4px;
  color: #245c38;
  font-size: 11px;
  letter-spacing: .04em;
}

.student-add-row input {
  width: 100%;
  padding: 9px 10px;
  border: 1px solid #aebbb1;
  border-radius: 4px;
}

.student-add-row button {
  padding: 9px 12px;
  border: 0;
  border-radius: 4px;
  background: #287442;
  color: #fff;
  cursor: pointer;
}

.student-list-table {
  margin-top: 14px;
  border-top: 1px solid #d8dfda;
}

.student-list-row {
  min-height: 38px;
  padding: 7px 4px;
  border-bottom: 1px solid #edf0ed;
}

.student-list-row strong {
  width: auto;
}

.student-added-label {
  color: #287442;
  font-size: 12px;
}

.student-list-empty {
  margin: 12px 0 0;
  color: #777;
  font-size: 13px;
}

@media (max-width: 1020px) {
  .institute-grid,
  .status-grid {
    gap: 22px;
  }

  .status-grid {
    padding-inline: 20px;
  }

  .tab-button {
    font-size: 20px;
  }
}

@media (max-width: 820px) {
  .registrar-root {
    position: fixed;
  }

  .registrar-sidebar {
    width: 86px;
    flex-basis: 86px;
  }

  .registrar-header h1 {
    font-size: 25px;
  }

  .institute-content {
    padding: 64px 24px 40px;
  }

  .institute-content h2 {
    font-size: 42px;
  }

  .institute-grid,
  .status-grid,
  .tabs-row,
  .modal-actions {
    grid-template-columns: 1fr;
  }

  .institute-grid {
    max-width: 360px;
  }

  .management-view {
    padding: 22px 16px 32px;
  }

  .institute-title {
    font-size: 28px;
    line-height: 1.15;
  }

  .tabs-row {
    min-height: auto;
  }

  .release-fields {
    grid-template-columns: 1fr 1fr;
  }

  .published-card {
    position: static;
    margin: 18px 0 0;
  }

  .tab-button {
    min-height: 64px;
    border-right: 0;
    border-bottom: 1px solid #eeeeee;
  }

  .toolbar {
    height: auto;
    align-items: stretch;
    flex-direction: column;
  }

  .search-box {
    width: 100%;
  }

  .filter-group {
    justify-content: flex-end;
  }

  .release-content {
    min-height: 0;
  }

  .release-form {
    width: 100%;
  }

  .approval-table-box {
    margin: 0 12px 60px;
  }

  .review-modal {
    min-height: 100vh;
    border-radius: 0;
    overflow-y: auto;
  }

  .review-summary {
    height: auto;
    grid-template-columns: 1fr 1fr;
    gap: 18px;
    padding: 16px;
  }

  .modal-actions {
    padding-top: 44px;
  }
}

@media (max-width: 560px) {
  .registrar-sidebar {
    width: 70px;
    flex-basis: 70px;
    padding-top: 28px;
  }

  .seal-block img {
    width: 52px;
    height: 52px;
  }

  .sidebar-rule {
    width: 48px;
  }

  .registrar-profile span {
    font-size: 10px;
  }

  .registrar-header {
    height: 68px;
    flex-basis: 68px;
    padding-inline: 18px;
  }

  .registrar-header h1 {
    font-size: 20px;
  }

  .institute-content h2 {
    font-size: 34px;
  }

  .institute-card {
    height: 176px;
  }

  .release-fields {
    grid-template-columns: 1fr;
  }

  .add-release-button {
    position: static;
    margin-top: 12px;
  }

  .review-top,
  .review-header {
    align-items: flex-start;
  }

  .review-top {
    flex-direction: column;
  }

  .review-header h2 {
    font-size: 23px;
  }
}
</style>
