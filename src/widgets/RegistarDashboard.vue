<template>
  <div class="registrar-root">
    <aside class="registrar-sidebar">
      <div class="seal-block">
        <img :src="colegioLogo" alt="Colegio de Montalban" />
      </div>

      <div class="sidebar-rule"></div>

      <nav class="registrar-nav" aria-label="Registrar navigation">
        <button
          class="nav-button"
          :class="{ active: currentView === 'institute' || currentView === 'management' }"
          type="button"
          aria-label="Grade management"
          @click="currentView = 'institute'"
        >
          <span v-if="currentView === 'institute' || currentView === 'management'" class="active-bar"></span>
          <svg viewBox="0 0 24 24" aria-hidden="true">
            <rect x="4" y="4" width="5" height="5" />
            <rect x="15" y="4" width="5" height="5" />
            <rect x="4" y="15" width="5" height="5" />
            <rect x="15" y="15" width="5" height="5" />
          </svg>
        </button>
        <button class="nav-button" :class="{ active: currentView === 'students' }" type="button" aria-label="Student repository" @click="openRepository">
          <svg viewBox="0 0 24 24" aria-hidden="true">
            <path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20" />
            <path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2Z" />
          </svg>
        </button>
        <button class="nav-button" :class="{ active: currentView === 'professors' }" type="button" aria-label="Professor repository" @click="openProfessorRepository">
          <svg viewBox="0 0 24 24" aria-hidden="true">
            <rect x="4" y="4" width="16" height="16" rx="2" />
            <circle cx="12" cy="10" r="2.5" />
            <path d="M8 17a4 4 0 0 1 8 0" />
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
              @pointerup.stop="selectInstitute(institute)"
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

      <section v-else-if="currentView === 'management'" class="management-view">
        <button class="back-institute-button" type="button" @click="backToInstitutes">&#8592; Back to Institutes</button>
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
                <button type="button" aria-label="Open calendar" :aria-expanded="calendarOpen" @click="calendarOpen = !calendarOpen">...</button>
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

          <div v-else-if="activeTab === 'release'" class="release-panel">
            <h3>Grade Release Settings</h3>
            <div class="release-content">
              <div class="pending-schedule-list">
                <p class="release-form-title">Pending Approved Sections</p>
                <article v-for="group in pendingScheduleGroups" :key="group.key" class="pending-schedule-card">
                  <strong>{{ group.label }}</strong>
                  <span>{{ group.sheets.length }} approved grade sheet{{ group.sheets.length === 1 ? '' : 's' }} waiting for a release date</span>
                  <form @submit.prevent="addRelease(group)">
                    <input v-model="scheduleDraft(group).schoolYear" type="text" aria-label="School Year" />
                    <select v-model="scheduleDraft(group).semester" aria-label="Semester"><option>Semester</option><option>1st Semester</option><option>2nd Semester</option></select>
                    <select v-model="scheduleDraft(group).yearLevel" aria-label="Year Level"><option>Year</option><option>1st Year</option><option>2nd Year</option><option>3rd Year</option><option>4th Year</option></select>
                    <input v-model="scheduleDraft(group).releasedDate" type="date" aria-label="Released Date" required />
                    <button class="add-release-button" type="submit">+ Add Schedule</button>
                  </form>
                </article>
                <p v-if="pendingScheduleGroups.length === 0" class="release-empty">No approved sections waiting for a schedule.</p>
              </div>
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
                      <small v-for="course in schedule.courses" :key="course">{{ scheduleCourseLabel(course) }}</small>
                    </span>
                    <span>{{ schedule.releasedDate || '-' }}</span>
                    <span><b class="schedule-status" :class="schedule.status.toLowerCase()">{{ schedule.status }}</b></span>
                    <span>
                      <button v-if="schedule.status === 'Scheduled'" class="release-now-button" type="button" :disabled="!isReleaseAvailable(schedule)" :title="isReleaseAvailable(schedule) ? 'Release this schedule' : `Available on ${schedule.releasedDate}`" @click="releaseNow(schedule)">{{ isReleaseAvailable(schedule) ? 'Release Now' : 'Scheduled' }}</button>
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
                <p class="period-form-title">&#128197; Set Grade Period</p>
                <div class="period-fields">
                  <label>School Year<input v-model="periodForm.schoolYear" type="text" /></label>
                  <label>Semester<select v-model="periodForm.semester"><option>Semester</option><option>1st Semester</option><option>2nd Semester</option></select></label>
                  <label>Midterm Open<input v-model="periodForm.midtermOpens" type="date" required /></label>
                  <label>Midterm Deadline<input v-model="periodForm.midtermDeadline" type="date" required /></label>
                  <label>Finals Open<input v-model="periodForm.finalsOpens" type="date" required /></label>
                  <label>Finals Deadline<input v-model="periodForm.finalsDeadline" type="date" required /></label>
                  <button class="save-period-button" type="submit">&#128190; {{ editingPeriodId ? 'Update Grade Period' : 'Save Grade Period' }}</button>
                </div>
              </form>
              <section class="active-schedules">
                <h4>Active Schedules</h4>
                <div v-for="period in gradePeriods" :key="period.id" class="active-schedule-row">
                  <div><strong>A.Y {{ period.schoolYear }} &nbsp;-&nbsp; {{ period.semester }}</strong><small>Midterm: {{ period.midtermOpens || '-' }} to {{ period.midtermDeadline || '-' }} &nbsp;|&nbsp; Finals: {{ period.finalsOpens || '-' }} to {{ period.finalsDeadline || '-' }}</small></div>
                  <span class="period-state" :class="{ locked: periodState(period, 'midterm') === 'Closed' }">Midterm {{ periodState(period, 'midterm') }}</span>
                  <span class="period-state" :class="{ locked: periodState(period, 'finals') === 'Closed' }">Finals {{ periodState(period, 'finals') }}</span>
                  <div class="period-actions"><button type="button" class="edit-period-button" @click="editGradePeriod(period)">Edit</button><button type="button" class="remove-period-button" @click="removeGradePeriod(period)">Remove</button></div>
                </div>
                <p v-if="gradePeriods.length === 0" class="release-empty">No active grade period schedule.</p>
              </section>
            </div>
          </div>

        </div>
      </section>

      <section v-else-if="currentView === 'students'" class="repository-view">
        <div class="repository-panel">
          <div class="repository-heading">
            <div>
              <h3>Student Repository</h3>
              <p>View students and their published grades.</p>
            </div>
          </div>
          <div class="repository-layout">
            <aside class="repository-sidebar">
              <input v-model="repositorySearch" class="repository-search" placeholder="Search student name or ID..." />
              <div class="repository-filters">
                <label>Institute<select v-model="repositoryInstitute"><option value="">All Institute</option><option value="ICS">ICS</option><option value="IBE">IBE</option><option value="ITE">ITE</option></select></label>
                <label>Course<select v-model="repositoryCourse"><option value="">All Course</option><option v-for="course in repositoryCourses" :key="course" :value="course">{{ course }}</option></select></label>
                <label>Year Level<select v-model="repositoryYear"><option value="">Year</option><option v-for="year in repositoryYears" :key="year" :value="year">{{ year }}</option></select></label>
                <label>Section<select v-model="repositorySection"><option value="">All Section</option><option v-for="section in repositorySections" :key="section" :value="section">{{ section }}</option></select></label>
                <span>Last Name Group</span>
                <div class="letter-groups"><button v-for="group in lastNameGroups" :key="group" type="button" :class="{ selected: repositoryLetter === group }" @click="repositoryLetter = repositoryLetter === group ? '' : group">{{ group }}</button></div>
              </div>
              <div class="repository-student-list">
                <small>Students ({{ filteredRepositoryStudents.length }})</small>
                <template v-for="group in repositoryGroups" :key="`${group.institute}-${group.section}`">
                  <strong class="repository-group-heading">{{ group.institute }} - {{ group.course }} {{ yearNumber(group.year) }}-{{ group.section }}</strong>
                  <button v-for="student in group.students" :key="student.student_id" type="button" :class="{ selected: selectedRepositoryStudentId === student.student_id }" @click="selectedRepositoryStudentId = student.student_id">
                    <strong>{{ student.name }}</strong><span>{{ student.student_id }} &bull; {{ student.course || 'Course not set' }} {{ yearNumber(student.year_level) }}-{{ student.section || '-' }}</span><b>{{ student.status || 'Active' }}</b>
                  </button>
                </template>
                <p v-if="filteredRepositoryStudents.length === 0" class="repository-empty">No students found.</p>
              </div>
            </aside>
            <section v-if="selectedRepositoryStudent" class="repository-detail">
              <header><div><h3>{{ selectedRepositoryStudent.name }}</h3><p>{{ selectedRepositoryStudent.student_id }} &bull; {{ selectedRepositoryStudent.institute || '-' }} &bull; {{ selectedRepositoryStudent.course || 'Course not set' }} {{ yearNumber(selectedRepositoryStudent.year_level) }}-{{ selectedRepositoryStudent.section || '-' }}</p></div><span>Status: <b>{{ selectedRepositoryStudent.status || 'Active' }}</b></span></header>
              <div v-for="semester in repositorySemesters" :key="semester" class="repository-semester">
                <h4>{{ semester }} - {{ selectedRepositoryStudent.year_level || '1st Year' }}</h4>
                <div class="repository-grade-header"><strong>SUBJECT CODE</strong><strong>MIDTERM</strong><strong>FINALS</strong><strong>FINAL AVERAGE</strong><strong>GRADE POINT</strong><strong>REMARKS</strong></div>
                <div v-for="sheet in repositoryGradesBySemester(selectedRepositoryStudent.student_id, semester)" :key="sheet.id" class="repository-grade-row"><strong>{{ sheet.code }}</strong><span>{{ gradeRecord(sheet, selectedRepositoryStudent.student_id)?.midterm || '-' }}</span><span>{{ gradeRecord(sheet, selectedRepositoryStudent.student_id)?.finals || '-' }}</span><span>{{ gradeRecord(sheet, selectedRepositoryStudent.student_id)?.finalGrade || '-' }}</span><b>{{ gradeRecord(sheet, selectedRepositoryStudent.student_id)?.gradePoint || '-' }}</b><span>{{ gradeRecord(sheet, selectedRepositoryStudent.student_id)?.remarks || '-' }}</span></div>
                <p v-if="repositoryGradesBySemester(selectedRepositoryStudent.student_id, semester).length === 0" class="repository-empty">No published grades.</p>
              </div>
            </section>
            <p v-else class="repository-empty">Select a student to view grades.</p>
          </div>
        </div>
      </section>

      <section v-else class="professor-view">
        <div class="professor-repository">
          <div class="professor-heading">
            <div>
              <h2>PROFESSOR REPOSITORY</h2>
              <h3>{{ selectedInstitute?.name || 'Select an institute first' }}</h3>
            </div>

          </div>
          <div class="professor-toolbar">
            <input v-model="professorSearch" placeholder="Search professor name ......" />
          </div>
          <div class="professor-table-wrap">
            <table class="professor-table">
              <thead><tr><th>Name</th><th>Email</th><th>Institute</th><th>Department</th><th>Status</th></tr></thead>
              <tbody>
                <tr v-for="professor in filteredProfessors" :key="professor.id">
                  <td>{{ professor.name }}</td><td>{{ professor.email }}</td><td>{{ selectedInstitute?.short || '-' }}</td><td><span>{{ professorCourse(professor) }}</span></td><td><b>{{ professor.status === 'Pending' ? 'Active' : professor.status }}</b></td>
                </tr>
                <tr v-if="filteredProfessors.length === 0"><td colspan="5" class="empty-cell">No registered professors found.</td></tr>
              </tbody>
            </table>
          </div>
        </div>
      </section>
    </main>

    <div v-if="showAddProfessorNotice" class="student-form-backdrop" @click.self="showAddProfessorNotice = false">
      <section class="student-form-modal professor-notice" role="dialog" aria-modal="true">

      </section>
    </div>

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
            <span class="review-status" :class="{ published: reviewSheet.status === 'Published' }">{{ reviewSheet.status }}</span>
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
import { computed, onMounted, onUnmounted, reactive, ref } from 'vue'
import { createGradePeriod, createGradeSchedule, deleteGradePeriod, getGradeSchedules, getGradeSheets, getInstitutes, getProfessors, getStudents, releaseGradeSchedule, updateGradePeriod, updateGradeSheetStatus } from '../services/dataService'
import colegioLogo from '../logo/The_Colegio_de_Montalban_Seal (1).png'

const props = defineProps({ user: { type: Object, default: null } })
defineEmits(['signout'])

const currentView = ref('institute')
const selectedInstitute = ref(null)
const search = ref('')
const statusFilter = ref('All Status')
const reviewSheet = ref(null)
const activeTab = ref('approval')
const currentTime = ref(Date.now())
const calendarOpen = ref(false)
const showAddProfessorNotice = ref(false)
const expandedYears = ref(['1st Year'])
const scheduleDrafts = reactive({})
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
  midtermDeadline: '',
  finalsOpens: '',
  finalsDeadline: '',
})
const editingPeriodId = ref(null)
const gradePeriods = computed(() => schedules.value.filter(schedule => schedule.yearLevel === 'Grade Period'))
const institutes = getInstitutes()

function instituteMatches(value, institute) {
  const text = `${value?.code || ''} ${value?.subject || ''} ${value?.name || ''} ${value?.section || ''}`.toLowerCase()
  const isComputing = /\bbsit\b|\bitfund\b|\bit\d+\b|comput/.test(text)
  const isEducation = /\bbeed\b|\bbed\b|education|edu\d+|teacher/.test(text)
  const isBusiness = /\bbsba\b|business|buslaw|finacc|entrepreneur/.test(text)
  if (institute.id === 'ics') return isComputing && !isEducation
  if (institute.id === 'ioe') return isEducation && !isComputing
  if (institute.id === 'ibe') return isBusiness && !isComputing && !isEducation
  return false
}

const gradeSheets = ref([])
const schedules = ref([])
const instituteGradeSheets = computed(() => gradeSheets.value.filter(sheet => !selectedInstitute.value || instituteMatches(sheet, selectedInstitute.value)))
const publishedCount = computed(() => instituteGradeSheets.value.filter(sheet => sheet.status === 'Published').length)
const approvedGradeSheets = computed(() => instituteGradeSheets.value.filter(sheet => sheet.status === 'Approved' && sheet.id))
const reviewStudents = computed(() => reviewSheet.value?.student_records || [])
const students = ref([])
const professors = ref([])
const professorSearch = ref('')
const filteredProfessors = computed(() => {
  const query = professorSearch.value.trim().toLowerCase()
  return professors.value.filter(professor => {
    const matchesInstitute = !selectedInstitute.value || gradeSheets.value.some(sheet => sheet.professor === professor.name && instituteMatches(sheet, selectedInstitute.value))
    return matchesInstitute && (!query || `${professor.name} ${professor.email}`.toLowerCase().includes(query))
  })
})
const repositorySearch = ref('')
const repositoryInstitute = ref('')
const repositoryCourse = ref('')
const repositoryYear = ref('')
const repositorySection = ref('')
const repositoryLetter = ref('')
const selectedRepositoryStudentId = ref('')
const repositoryYears = ['1st Year', '2nd Year', '3rd Year', '4th Year']
const repositorySections = ['A', 'B', 'C', 'D']
const lastNameGroups = ['A - E', 'F - J', 'K - O', 'P - T', 'U - Z']
const repositorySemesters = ['1st Semester', '2nd Semester']
const repositoryCourses = computed(() => [...new Set(students.value.map(student => student.course).filter(Boolean))])
const filteredRepositoryStudents = computed(() => {
  const query = repositorySearch.value.trim().toLowerCase()
  return students.value.filter(student => {
    const nameInitial = student.name.trim().charAt(0).toUpperCase()
    const [start, end] = repositoryLetter.value.split('-').map(part => part.trim())
    const matchesLetter = !repositoryLetter.value || nameInitial >= start && nameInitial <= end
    return (!query || `${student.student_id} ${student.name}`.toLowerCase().includes(query)) && (!repositoryInstitute.value || student.institute === repositoryInstitute.value) && (!repositoryCourse.value || student.course === repositoryCourse.value) && (!repositoryYear.value || student.year_level === repositoryYear.value) && (!repositorySection.value || student.section?.split('-').pop()?.toUpperCase() === repositorySection.value) && matchesLetter
  })
})
const repositoryGroups = computed(() => {
  const groups = new Map()
  filteredRepositoryStudents.value.forEach(student => {
    const institute = student.institute || 'Unassigned'
    const section = student.section?.split('-').pop()?.toUpperCase() || 'Unassigned'
    const course = student.course || 'Course not set'
    const year = student.year_level || 'Year not set'
    const key = `${institute}-${course}-${year}-${section}`
    if (!groups.has(key)) groups.set(key, { institute, course, year, section, students: [] })
    groups.get(key).students.push(student)
  })
  return [...groups.values()]
})
const selectedRepositoryStudent = computed(() => students.value.find(student => student.student_id === selectedRepositoryStudentId.value) || filteredRepositoryStudents.value[0])

function isReleaseAvailable(schedule) {
  if (schedule.status !== 'Scheduled') return false
  if (!schedule.releasedDate) return true
  return new Date(`${schedule.releasedDate}T00:00:00`).getTime() <= currentTime.value
}

let releaseClock
let dataRefreshTimer

async function refreshRegistrarData() {
  const [gradeSheetsResult, schedulesResult] = await Promise.allSettled([getGradeSheets(), getGradeSchedules()])
  if (gradeSheetsResult.status === 'fulfilled') {
    gradeSheets.value = gradeSheetsResult.value
    if (reviewSheet.value?.id) {
      reviewSheet.value = gradeSheetsResult.value.find(sheet => sheet.id === reviewSheet.value.id) || reviewSheet.value
    }
  }
  if (schedulesResult.status === 'fulfilled') schedules.value = schedulesResult.value
}

onMounted(async () => {
  releaseClock = window.setInterval(() => { currentTime.value = Date.now() }, 30000)
  const [gradeSheetsResult, studentsResult, schedulesResult, professorsResult] = await Promise.allSettled([getGradeSheets(), getStudents(), getGradeSchedules(), getProfessors()])
  if (gradeSheetsResult.status === 'fulfilled') gradeSheets.value = gradeSheetsResult.value
  if (studentsResult.status === 'fulfilled') students.value = studentsResult.value
  if (schedulesResult.status === 'fulfilled') schedules.value = schedulesResult.value
  if (professorsResult.status === 'fulfilled') professors.value = professorsResult.value
  dataRefreshTimer = window.setInterval(() => { void refreshRegistrarData() }, 5000)
})

onUnmounted(() => {
  window.clearInterval(releaseClock)
  window.clearInterval(dataRefreshTimer)
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
  const pending = instituteGradeSheets.value.filter(s => s.status === 'Submitted').length
  const approved = instituteGradeSheets.value.filter(s => s.status === 'Approved').length
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
  return instituteGradeSheets.value.filter((sheet) => {
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
    schedules: schedules.value.filter(schedule => schedule.schoolYear === schoolYear && schedule.yearLevel !== 'Grade Period' && (!selectedInstitute.value || scheduleMatchesInstitute(schedule, selectedInstitute.value))),
  }))
})

const scheduledSheetIds = computed(() => new Set(schedules.value.flatMap(schedule => schedule.courses.filter(course => course.startsWith('sheet:')).map(course => Number(course.replace('sheet:', ''))))))
const pendingScheduleGroups = computed(() => {
  const groups = new Map()
  approvedGradeSheets.value.filter(sheet => !scheduledSheetIds.value.has(sheet.id)).forEach(sheet => {
    const key = `${sheet.code}-${sheet.section}`
    if (!groups.has(key)) groups.set(key, { key, label: `${sheet.code} - ${sheet.section}`, sheets: [] })
    groups.get(key).sheets.push(sheet)
  })
  return [...groups.values()]
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
  repositoryInstitute.value = institute.short
  currentView.value = 'management'
  activeTab.value = 'approval'
}

function backToInstitutes() {
  currentView.value = 'institute'
  activeTab.value = 'approval'
}

function openRepository() {
  if (selectedInstitute.value) repositoryInstitute.value = selectedInstitute.value.short
  currentView.value = 'students'
}

function openProfessorRepository() {
  if (!selectedInstitute.value) selectedInstitute.value = institutes[0]
  repositoryInstitute.value = selectedInstitute.value.short
  currentView.value = 'professors'
}

function professorCourse(professor) {
  const sheet = gradeSheets.value.find(item => item.professor === professor.name && (!selectedInstitute.value || instituteMatches(item, selectedInstitute.value)))
  return sheet?.code || 'No course yet'
}

function repositoryGrades(studentId) {
  return gradeSheets.value.filter(sheet => sheet.status === 'Published' && sheet.student_records?.some(record => record.id === studentId))
}

function repositoryGradesBySemester(studentId, semester) {
  return repositoryGrades(studentId).filter(sheet => sheet.semester.toLowerCase().includes(semester.toLowerCase().split(' ')[0]))
}

function yearNumber(year) {
  return year?.charAt(0) || ''
}

function gradeRecord(sheet, studentId) {
  return sheet.student_records?.find(record => record.id === studentId)
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

function scheduleCourseLabel(course) {
  if (!course.startsWith('sheet:')) return course
  const sheet = gradeSheets.value.find(item => `sheet:${item.id}` === course)
  return sheet ? `${sheet.code} - ${sheet.section}` : course
}

function scheduleDraft(group) {
  if (!scheduleDrafts[group.key]) {
    scheduleDrafts[group.key] = { schoolYear: '2025-2026', semester: 'Semester', yearLevel: 'Year', releasedDate: '' }
  }
  return scheduleDrafts[group.key]
}

function scheduleMatchesInstitute(schedule, institute) {
  return schedule.courses.length === 0 || schedule.courses.some(course => {
    if (!course.startsWith('sheet:')) return instituteMatches({ name: course }, institute)
    const sheet = gradeSheets.value.find(item => `sheet:${item.id}` === course)
    return sheet ? instituteMatches(sheet, institute) : false
  })
}

async function addRelease(group) {
  const draft = scheduleDraft(group)
  if (!draft.schoolYear.trim()) {
    window.alert('Enter a school year before adding the schedule.')
    return
  }
  if (draft.yearLevel === 'Year' || draft.semester === 'Semester') {
    window.alert('Select a semester and year level before adding the schedule.')
    return
  }
  if (!draft.releasedDate) {
    window.alert('Select a released date before adding the schedule.')
    return
  }
  try {
    const schedule = await createGradeSchedule({
      schoolYear: draft.schoolYear.trim(),
      semester: draft.semester,
      yearLevel: draft.yearLevel,
      courses: group.sheets.map(sheet => `sheet:${sheet.id}`),
      releasedDate: draft.releasedDate,
    })
    schedules.value.unshift(schedule)
    const label = `A.Y ${schedule.schoolYear.replace('-', ' - ')}`
    if (!expandedReleaseYears.value.includes(label)) expandedReleaseYears.value.push(label)
    delete scheduleDrafts[group.key]
  } catch (error) {
    window.alert(error instanceof Error ? error.message : 'Unable to add release schedule.')
  }
}

function releaseNow(schedule) {
  if (!schedule.id) return
  releaseGradeSchedule(schedule.id).then(updated => {
    const index = schedules.value.findIndex(item => item.id === updated.id)
    if (index !== -1) schedules.value[index] = updated
  }).catch(error => window.alert(error instanceof Error ? error.message : 'Unable to release schedule.'))
}

function resolveDeadline(dateValue) {
  if (!dateValue) return null
  const date = new Date(`${dateValue}T00:00:00`)
  return Number.isNaN(date.getTime()) ? null : date
}

function periodState(period, type) {
  const today = new Date(currentTime.value)
  today.setHours(0, 0, 0, 0)
  const opens = resolveDeadline(type === 'midterm' ? period.midtermOpens : period.finalsOpens)
  const deadline = resolveDeadline(type === 'midterm' ? period.midtermDeadline : period.finalsDeadline)
  if (!opens || !deadline) return 'Not set'
  if (today.getTime() < opens.getTime()) return 'Upcoming'

  return today.getTime() < deadline.getTime() ? 'Open' : 'Closed'
}

function editGradePeriod(period) {
  editingPeriodId.value = period.id
  periodForm.value = {
    schoolYear: period.schoolYear,
    semester: period.semester,
    midtermOpens: period.midtermOpens || '',
    midtermDeadline: period.midtermDeadline || '',
    finalsOpens: period.finalsOpens || '',
    finalsDeadline: period.finalsDeadline || '',
  }
}

async function removeGradePeriod(period) {
  if (!period.id || !window.confirm(`Remove the grade period for ${period.schoolYear} - ${period.semester}?`)) return
  try {
    await deleteGradePeriod(period.id)
    schedules.value = schedules.value.filter(schedule => schedule.id !== period.id)
    if (editingPeriodId.value === period.id) editingPeriodId.value = null
  } catch (error) {
    window.alert(error instanceof Error ? error.message : 'Unable to remove grade period.')
  }
}

async function saveGradePeriod() {
  if (periodForm.value.semester === 'Semester') return
  const request = editingPeriodId.value
    ? updateGradePeriod(editingPeriodId.value, periodForm.value)
    : createGradePeriod(periodForm.value)
  request.then(schedule => {
    const index = schedules.value.findIndex(item => item.id === schedule.id)
    if (index !== -1) schedules.value[index] = schedule
    else schedules.value.unshift(schedule)
    editingPeriodId.value = null
  }).catch(error => window.alert(error instanceof Error ? error.message : 'Unable to save grade period.'))
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
  pointer-events: none;
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
  position: relative;
  z-index: 2;
  width: 100%;
  max-width: 997px;
  display: grid;
  grid-template-columns: repeat(3, minmax(240px, 1fr));
  gap: 73px;
}

.institute-card {
  position: relative;
  z-index: 2;
  pointer-events: auto;
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
  pointer-events: none;
}

.card-white {
  position: absolute;
  left: -7%;
  right: -7%;
  bottom: -1px;
  height: 105px;
  border-radius: 50% 50% 0 0 / 40% 40% 0 0;
  background: rgba(249, 249, 249, 0.92);
  pointer-events: none;
}

.institute-seal {
  position: absolute;
  inset: 0;
  display: grid;
  place-items: center;
  pointer-events: none;
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

.back-institute-button {
  margin-bottom: 18px;
  padding: 8px 14px;
  border: 1px solid #4c865f;
  border-radius: 6px;
  background: #fff;
  color: #245c38;
  font-weight: 700;
  cursor: pointer;
}

.back-institute-button:hover {
  background: #e5f7e9;
}

.repository-view {
  flex: 1;
  overflow: auto;
  padding: 31px 30px 46px;
  background: #f9f9f9;
}

.professor-view {
  flex: 1;
  overflow: auto;
  padding: 31px 30px 46px;
  background: #f9f9f9;
}

.professor-repository {
  max-width: 1110px;
  margin: 0 auto;
}

.professor-heading {
  display: flex;
  align-items: end;
  justify-content: space-between;
  gap: 18px;
  margin-bottom: 18px;
}

.professor-heading h2,
.professor-heading h3 {
  margin: 0;
}

.professor-heading h2 {
  color: #111;
  font-size: 25px;
}

.professor-heading h3 {
  margin-top: 12px;
  color: #155423;
  font-size: 25px;
  text-shadow: -2px 0 0 #f3df23;
}

.add-professor-button {
  padding: 10px 15px;
  border: 0;
  border-radius: 5px;
  background: #4c865f;
  color: #fff;
  font-weight: 700;
  cursor: pointer;
}

.professor-toolbar input {
  width: min(420px, 100%);
  min-height: 36px;
  padding: 8px 10px;
  border: 1px solid #777;
  border-radius: 5px;
}

.professor-table-wrap {
  margin-top: 8px;
  overflow-x: auto;
  border: 1px solid #c8c8c8;
  border-radius: 5px;
  background: #fff;
}

.professor-table {
  width: 100%;
  border-collapse: collapse;
  font-size: 12px;
}

.professor-table th,
.professor-table td {
  padding: 8px 12px;
  border-bottom: 1px solid #d4d4d4;
  text-align: left;
  white-space: nowrap;
}

.professor-table th {
  background: #fff;
  font-weight: 700;
}

.professor-table td:nth-child(4) span {
  display: inline-block;
  margin-right: 6px;
  padding: 3px 8px;
  border: 1px solid #c8c8c8;
  border-radius: 10px;
  font-size: 10px;
}

.professor-table td:last-child b {
  padding: 3px 8px;
  border-radius: 4px;
  background: #4c865f;
  color: #fff;
  font-size: 10px;
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
  grid-template-columns: repeat(2, 1fr);
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

.repository-heading {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 16px;
}

.repository-heading p {
  margin: -8px 0 16px;
  color: #66706a;
  font-size: 12px;
}

.add-student-button,
.student-form-submit {
  border: 0;
  border-radius: 6px;
  background: #4c865f;
  color: #fff;
  font-weight: 700;
  cursor: pointer;
}

.add-student-button {
  padding: 10px 16px;
}

.repository-search {
  width: min(490px, 100%);
  margin-bottom: 14px;
  padding: 10px 12px;
  border: 1px solid #aebbb1;
  border-radius: 6px;
}

.repository-layout {
  display: grid;
  grid-template-columns: 285px minmax(0, 1fr);
  gap: 14px;
}

.repository-sidebar {
  min-width: 0;
}

.repository-sidebar .repository-search {
  width: 100%;
}

.repository-filters {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 8px;
  padding: 10px;
  border: 1px solid #c8d8cc;
  border-radius: 7px;
}

.repository-filters label {
  display: grid;
  gap: 4px;
  color: #222;
  font-size: 12px;
  font-weight: 700;
}

.repository-filters select {
  min-height: 30px;
  border: 1px solid #aebbb1;
  border-radius: 5px;
  background: #fff;
}

.repository-filters > span {
  grid-column: 1 / -1;
  font-size: 12px;
  font-weight: 700;
}

.letter-groups {
  grid-column: 1 / -1;
  display: grid;
  grid-template-columns: repeat(5, 1fr);
  gap: 5px;
}

.letter-groups button {
  padding: 7px 2px;
  border: 1px solid #aebbb1;
  border-radius: 5px;
  background: #fff;
  cursor: pointer;
  font-size: 11px;
}

.letter-groups button.selected {
  background: #4c865f;
  color: #fff;
}

.repository-student-list {
  margin-top: 8px;
  border: 1px solid #c8d8cc;
  border-radius: 7px;
  overflow: hidden;
}

.repository-student-list > small {
  display: block;
  padding: 8px 10px;
  color: #66706a;
}

.repository-group-heading {
  display: block;
  padding: 8px 10px;
  border-top: 1px solid #d8dfda;
  background: #eef8f0;
  color: #245c38;
  font-size: 12px;
}

.repository-student-list > button {
  position: relative;
  display: grid;
  width: 100%;
  gap: 2px;
  padding: 8px 42px 8px 10px;
  border: 0;
  border-top: 1px solid #d8dfda;
  background: #fff;
  text-align: left;
  cursor: pointer;
}

.repository-student-list > button.selected {
  background: #b8f1c7;
}

.repository-student-list > button span {
  color: #718078;
  font-size: 11px;
}

.repository-student-list > button b {
  position: absolute;
  top: 10px;
  right: 8px;
  padding: 2px 5px;
  border-radius: 3px;
  background: #4c865f;
  color: #fff;
  font-size: 10px;
}

.repository-detail {
  min-width: 0;
  border: 1px solid #c8d8cc;
  border-radius: 7px;
  overflow: hidden;
}

.repository-detail > header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 12px;
  padding: 12px 14px;
  border-bottom: 1px solid #b8b8b8;
}

.repository-detail > header h3,
.repository-detail > header p {
  margin: 0;
}

.repository-detail > header h3 {
  font-size: 21px;
}

.repository-detail > header p {
  color: #718078;
  font-size: 12px;
}

.repository-detail > header span {
  white-space: nowrap;
  font-size: 12px;
}

.repository-detail > header b {
  padding: 3px 7px;
  border-radius: 3px;
  background: #4c865f;
  color: #fff;
}

.repository-semester {
  padding: 0 12px 12px;
}

.repository-semester h4 {
  margin: 8px 0;
  font-size: 14px;
}

.repository-grade-header,
.repository-grade-row {
  display: grid;
  grid-template-columns: 1.5fr repeat(5, 1fr);
  gap: 8px;
  align-items: center;
  padding: 7px 10px;
  text-align: center;
}

.repository-grade-header {
  background: #b8f1c7;
  color: #245c38;
  font-size: 11px;
}

.repository-grade-row {
  border-bottom: 1px solid #bbb;
  font-size: 12px;
}

.repository-grade-row strong {
  text-align: left;
}

.repository-student {
  margin-bottom: 12px;
  border: 1px solid #c8d8cc;
  border-radius: 7px;
  overflow: hidden;
}

.repository-student-heading,
.repository-grade {
  display: grid;
  grid-template-columns: minmax(150px, 1.2fr) minmax(160px, 1fr) auto;
  gap: 12px;
  align-items: center;
  padding: 10px 12px;
}

.repository-student-heading {
  background: #e5f7e9;
}

.repository-student-heading span,
.repository-grade span {
  color: #526158;
  font-size: 12px;
}

.repository-student-heading b,
.repository-grade b {
  color: #287442;
  font-size: 12px;
}

.repository-grade {
  grid-template-columns: 110px minmax(150px, 1fr) repeat(3, auto) 90px;
  border-top: 1px solid #d8dfda;
}

.repository-empty {
  margin: 0;
  padding: 14px;
  color: #66706a;
  font-size: 13px;
}

.student-form-backdrop {
  position: fixed;
  inset: 0;
  z-index: 40;
  display: grid;
  place-items: center;
  padding: 18px;
  background: rgba(0, 0, 0, .35);
}

.student-form-modal {
  display: grid;
  grid-template-columns: repeat(3, minmax(0, 1fr));
  gap: 14px;
  width: min(780px, 100%);
  padding: 20px;
  border: 1px solid #c8d8cc;
  border-radius: 8px;
  background: #fff;
  box-shadow: 0 8px 24px rgba(0, 0, 0, .2);
}

.student-form-heading {
  grid-column: 1 / -1;
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.student-form-heading h2 {
  margin: 0;
  font-size: 20px;
}

.student-form-heading button {
  border: 0;
  background: transparent;
  font-size: 18px;
  cursor: pointer;
}

.student-form-modal label {
  display: grid;
  gap: 6px;
  color: #222;
  font-weight: 700;
  font-size: 13px;
}

.student-form-modal input,
.student-form-modal select {
  width: 100%;
  min-height: 36px;
  padding: 7px 9px;
  border: 1px solid #aebbb1;
  border-radius: 5px;
  background: #fff;
}

.student-form-submit {
  grid-column: 3;
  min-height: 38px;
  margin-top: 8px;
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

.pending-schedule-list {
  width: min(765px, 100%);
  padding: 0 14px 12px;
}

.pending-schedule-card {
  display: grid;
  grid-template-columns: minmax(170px, 1fr) auto;
  gap: 8px 12px;
  margin-bottom: 10px;
  padding: 12px;
  border: 1px solid #b9d1be;
  border-radius: 7px;
  background: #f5fbf6;
}

.pending-schedule-card > strong {
  color: #245c38;
}

.pending-schedule-card > span {
  color: #66706a;
  font-size: 12px;
  text-align: right;
}

.pending-schedule-card form {
  grid-column: 1 / -1;
  display: grid;
  grid-template-columns: 1fr 1fr 1fr 1fr auto;
  gap: 8px;
}

.pending-schedule-card input,
.pending-schedule-card select {
  min-width: 0;
  min-height: 34px;
  padding: 6px 8px;
  border: 1px solid #aebbb1;
  border-radius: 4px;
  background: #fff;
}

.pending-schedule-card .add-release-button {
  margin: 0;
  white-space: nowrap;
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
  grid-template-columns: repeat(6, minmax(110px, 1fr)) auto;
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
  grid-template-columns: 1fr auto auto auto;
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

.period-actions {
  display: flex;
  gap: 6px;
}

.period-actions button {
  padding: 6px 10px;
  border: 0;
  border-radius: 5px;
  color: #ffffff;
  cursor: pointer;
  font-size: 12px;
  font-weight: 700;
}

.edit-period-button {
  background: #2d7b4a;
}

.remove-period-button {
  background: #c94b4b;
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
  min-height: 36px;
  padding: 3px 12px;
  justify-content: center;
  border: 1px solid #90d6a7;
  border-radius: 12px;
  background: #a9f3bf;
  color: #173f2c;
  font-size: 14px;
  font-weight: 800;
}

.review-status.published {
  min-width: 92px;
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
