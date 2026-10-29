.message-badge{position:absolute;top:2px;right:2px;min-width:19px;height:19px;padding:0 5px;border-radius:10px;background:#e44747;color:#fff;font-size:11px;line-height:19px;text-align:center}
<template>
  <div class="prof-root">
    <div v-if="submissionNotice" class="submission-notice" role="status">Grades submitted successfully. Opening Registrar...</div>
    <div v-if="isMobile && !sidebarCollapsed" class="sidebar-overlay" @click="sidebarCollapsed = true"></div>
    <aside class="prof-sidebar" :class="{ open: !sidebarCollapsed && isMobile }">
      <div class="seal-wrap"><img :src="colegioLogo" alt="Colegio de Montalban" /></div>
      <div class="sidebar-rule"></div>
      <nav class="prof-nav" aria-label="Professor navigation">
        <button class="nav-button" :class="{ active: view === 'lists' || view === 'grades' }" type="button" aria-label="Class lists" @click="goToLists(); sidebarCollapsed = true"><span v-if="view === 'lists' || view === 'grades'" class="active-bar"></span><svg viewBox="0 0 24 24"><rect x="4" y="4" width="5" height="5" /><rect x="15" y="4" width="5" height="5" /><rect x="4" y="15" width="5" height="5" /><rect x="15" y="15" width="5" height="5" /></svg></button>
      </nav>
      <div class="prof-sidebar-footer"><div class="sidebar-rule footer-rule"></div><div class="prof-profile"><svg viewBox="0 0 24 24"><circle cx="12" cy="8" r="4" /><path d="M4 21a8 8 0 0 1 16 0" /></svg><span>{{ props.user?.name || 'PROFESSOR' }}</span></div><button class="logout-button" type="button" aria-label="Sign out" @click.stop="signOut"><svg viewBox="0 0 24 24"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4" /><path d="m16 17 5-5-5-5" /><path d="M21 12H9" /></svg></button></div>
    </aside>
    <main class="prof-page">
      <header class="prof-header"><button v-if="isMobile" class="mobile-hamburger" type="button" aria-label="Open menu" @click="sidebarCollapsed = false"><svg viewBox="0 0 24 24"><path d="M4 6h16M4 12h16M4 18h16" /></svg></button><h1>GRADE ENTRY</h1></header>
      <div v-if="view === 'lists'" class="dashboard-content"><div class="toolbar"><label class="search-box"><svg viewBox="0 0 24 24"><circle cx="11" cy="11" r="7" /><path d="m21 21-5-5" /></svg><input v-model="searchStudent" placeholder="Search student name or ID..." /></label><select v-model="selectedSection"><option value="all">Section &amp; Year</option><option v-for="section in sections" :key="section" :value="section">{{ section }}</option></select></div><section class="roster-panel"><div class="roster-heading"><h2>Student Rosters by Institute</h2><p>Search by student name or ID, filter by section, then expand an institute and year to choose a course and enter grades.</p></div><button v-for="institute in institutes" :key="institute.id" class="institute-line" :class="{ expanded: expandedInstitute === institute.id }" type="button" @click="toggleInstitute(institute.id)"><span>{{ expandedInstitute === institute.id ? '&#8964;' : '&#8250;' }}</span>{{ institute.name }}</button><template v-if="expandedInstitute"><div class="year-line"><strong>YEAR LEVEL:</strong><button v-for="year in years" :key="year" :class="{ chosen: selectedYear === year }" type="button" @click="selectedYear = year">{{ year }}</button></div><div v-for="group in visibleGroups" :key="group.name" class="course-group"><h3>{{ group.name }}</h3><div class="course-grid"><button v-for="course in group.courses" :key="`${course.code}-${course.section}-${course.semester}`" class="course-card" type="button" @click="openGrades(course)"><small>{{ course.code }}</small><strong>{{ course.section }}</strong><span>{{ course.semester }} | &#9673; {{ course.students }} Students</span></button></div></div><p v-if="visibleGroups.length === 0" class="roster-empty">No courses for {{ selectedYear }}.</p></template></section></div>
      <div v-else class="grades-content">
        <button class="back-link" type="button" @click="goToLists">&#8592; Return</button>
        <div class="grade-toolbar">
          <label class="search-box"><input v-model="searchStudent" placeholder="Search student name or ID..." /></label>
          <div class="grade-actions">
            <template v-if="activePeriod !== 'final-grade'"><div class="choice-action"><button type="button" class="add-button" :disabled="isGradeLocked(activeScorePeriod)" @click="toggleAddChoices">+ Add Assessment</button><div v-if="addChoiceOpen" class="assessment-choices"><button v-for="type in assessmentTypes" :key="type.category" type="button" :disabled="isGradeLocked(activeScorePeriod)" @click="addAssessment(type.category)">{{ type.name }}</button><button type="button" :disabled="isGradeLocked(activeScorePeriod)" @click="openCustomAssessment"><span aria-hidden="true">+</span> Custom Assessment</button></div></div>
            <div class="choice-action"><button type="button" class="remove-button" :disabled="gradeHeaders.length <= 1 || isGradeLocked(activeScorePeriod)" @click="toggleRemoveChoices">Remove</button><div v-if="removeChoiceOpen" class="assessment-choices"><button v-for="assessment in gradeHeaders" :key="assessment" type="button" :disabled="isGradeLocked(activeScorePeriod)" @click="selectedAssessment = assessment; removeSelectedAssessment()">{{ assessmentDisplayName(assessment) }}</button></div></div>
            <div class="choice-action"><button type="button" class="restore-button" :disabled="isGradeLocked(activeScorePeriod)" @click="toggleRestoreChoices">Restore</button><div v-if="restoreChoiceOpen" class="assessment-choices"><button v-for="assessment in defaultGradeHeaders" :key="assessment" type="button" :disabled="isGradeLocked(activeScorePeriod) || gradeHeaders.includes(assessment)" @click="selectedAssessment = assessment; restoreSelectedAssessment()">{{ assessment }}</button></div></div>
            <button type="button" class="criteria-open-button" :disabled="isGradeLocked(activeScorePeriod)" @click="openCriteriaEditor">▤ Criteria</button>
            </template>
            <span v-if="draftSaveStatus" class="draft-save-status" role="status">
              <svg v-if="draftSaveStatus === 'Saving draft...'" class="draft-save-icon is-saving" viewBox="0 0 24 24" aria-hidden="true"><path d="M20 11a8 8 0 0 0-14.9-3M4 4v4h4M4 13a8 8 0 0 0 14.9 3M20 20v-4h-4" /></svg>
              <span v-else>{{ draftSaveStatus }}</span>
            </span>
          </div>
        </div>
        <p class="professor-feature-help">Manage assessments and grading criteria here. Scores save automatically; a score of 0 means No Output and results in FAILED.</p>
        <p v-if="isSubmissionLockedByRegistrar" class="draft-save-status" role="status">The Registrar locked grade submission. Grades are read-only.</p>
        <p v-if="scoreEntryError || maxScoreError" class="criteria-error" role="alert">{{ scoreEntryError || maxScoreError }}</p>
        <div class="grade-columns">
          <section class="grade-table-card">
            <h2>{{ selectedCourse.name }} ({{ selectedCourse.section }})</h2>
            <p v-if="selectedSchoolYear" class="school-year-label">
              School Year: {{ selectedSchoolYear }}
              <span v-if="selectedSemester">| Semester: {{ selectedSemester }}</span>
            </p>
            <div class="period-tabs"><button :class="{ chosen: activePeriod === 'midterm' }" type="button" @click="openGradingPeriod('midterm')">Midterm</button><button :class="{ chosen: activePeriod === 'finals' }" type="button" @click="openGradingPeriod('finals')">Finals</button><button :class="{ chosen: activePeriod === 'final-grade' }" type="button" @click="activePeriod = 'final-grade'">Final Grade</button></div>
            <p class="professor-feature-help period-feature-help">{{ activePeriod === 'final-grade' ? 'Final Grade = Midterm (40%) + Finals (60%). Export selected grades or submit the sheet to the Registrar.' : `Enter ${activePeriod === 'midterm' ? 'Midterm' : 'Finals'} scores. Grades use the saved criteria and assessment maximums.` }}</p>
            <div class="table-scroll" :class="{ 'final-grade-scroll': activePeriod === 'final-grade' }"><table class="grade-table" :class="{ 'final-grade-table': activePeriod === 'final-grade' }">
              <thead v-if="activePeriod === 'final-grade'"><tr><th>#</th><th>STUDENT ID</th><th class="wide">STUDENT NAME</th><th>MIDTERM ({{ FINAL_GRADE_WEIGHTS.midterm }}%)</th><th>FINALS ({{ FINAL_GRADE_WEIGHTS.finals }}%)</th><th>FINAL<br />GRADE</th><th class="equivalent-grade-heading">EQUIVALENT<br />GRADE</th></tr></thead>
              <thead v-else><tr><th rowspan="2">#</th><th rowspan="2">STUDENT ID</th><th rowspan="2" class="wide">STUDENT NAME</th><th rowspan="2">{{ activePeriod.toUpperCase() }}</th><th v-for="group in gradeGroups" :key="group.category" class="category-heading" :colspan="Math.max(group.headers.length, 1)">{{ group.name }}</th></tr><tr><template v-for="group in gradeGroups" :key="group.category"><th v-if="!group.headers.length">-</th><th v-for="label in group.headers" :key="label" class="assessment-column-heading">{{ assessmentDisplayName(label) }}</th></template></tr></thead>
              <tbody><tr v-for="(student, index) in gradeStudents" :key="student.id"><td>{{ index + 1 }}</td><td>{{ student.id }}</td><td class="name-cell">{{ student.name }}</td><template v-if="activePeriod === 'final-grade'"><td>{{ displayTermGrade(student, 'midterm') }}</td><td>{{ displayTermGrade(student, 'finals') }}</td><td>{{ displayGrade(student, activePeriod) }}</td><td>{{ displayEquivalentGrade(student) }}</td></template><template v-else><td>{{ displayGrade(student, activePeriod) }}</td><template v-for="group in gradeGroups" :key="group.category"><td v-if="!group.headers.length">-</td><td v-for="label in group.headers" :key="label" class="assessment-score-cell" :data-label="assessmentDisplayName(label)"><input :value="student.scores[activeScorePeriod][label]" type="number" min="0" :max="maxScoreFor(label, activeScorePeriod)" step="any" :disabled="isGradeLocked(activeScorePeriod)" :class="{ 'score-over-max': exceedsMaxScore(student, label, activeScorePeriod) }" :aria-invalid="exceedsMaxScore(student, label, activeScorePeriod)" :title="isGradeLocked(activeScorePeriod) ? periodLockMessage(activeScorePeriod) : exceedsMaxScore(student, label, activeScorePeriod) ? `Score must be between 0 and ${maxScoreFor(label, activeScorePeriod)}.` : undefined" @input="updateScore(student, label, activeScorePeriod, $event)" /></td></template></template></tr></tbody>
            </table></div>
          </section>
          <aside class="summary-box"><template v-if="activePeriod === 'final-grade'"><h2>FINAL GRADE WEIGHTS</h2><table><thead><tr><th>Grading Period</th><th>Percentage</th></tr></thead><tbody><tr><td>Midterm</td><td>{{ FINAL_GRADE_WEIGHTS.midterm }}%</td></tr><tr><td>Finals</td><td>{{ FINAL_GRADE_WEIGHTS.finals }}%</td></tr><tr class="total"><td>TOTAL</td><td>{{ FINAL_GRADE_WEIGHTS.midterm + FINAL_GRADE_WEIGHTS.finals }}%</td></tr></tbody></table></template><template v-else><h2>{{ criteriaPeriod.toUpperCase() }}</h2><table><thead><tr><th>Items</th><th>Max Score</th><th>Percentage</th></tr></thead><tbody><tr v-for="item in criteriaRows" :key="item.key"><td>{{ item.name }}</td><td>{{ item.maxScore }}</td><td>{{ item.weight }}%</td></tr><tr class="total"><td>TOTAL</td><td>-</td><td>{{ criteriaWeightTotal }}%</td></tr></tbody></table></template></aside>
        </div>
        <template v-if="activePeriod === 'final-grade'"><div class="final-grade-actions"><button class="export-excel-btn" type="button" :disabled="allGradeStudents.length === 0" @click="exportChoiceOpen = true">Export to Excel</button><button class="submit-action-btn" :class="{ 'is-submitted': isGradeSheetSubmitted }" type="button" :disabled="isSubmitting || isGradeSheetSubmitted || !hasFinalGradeSubmissionDeadline || isSubmissionLockedByRegistrar" :title="isGradeSheetSubmitted ? 'Grades have already been submitted to the Registrar.' : finalGradeSubmissionDeadlineMessage" @click="openSubmissionConfirmation">{{ isGradeSheetSubmitted ? 'Submitted' : hasReturnedGradeSheet ? 'Resubmit to Registrar' : 'Submit to Registrar' }}</button></div><small role="status">{{ isGradeSheetSubmitted ? 'Grades have been submitted to the Registrar.' : finalGradeSubmissionDeadlineMessage }}</small></template>
        <div v-if="exportChoiceOpen" class="modal-backdrop" @click.self="exportChoiceOpen = false"><section class="export-choice-dialog" role="dialog" aria-modal="true" aria-labelledby="export-choice-title"><h2 id="export-choice-title">Choose grades to export</h2><p>Select which grading results to include in the Excel file.</p><div class="export-choice-actions"><button type="button" @click="exportGrades('midterm')">Midterm Grades</button><button type="button" @click="exportGrades('finals')">Finals Grades</button><button type="button" @click="exportGrades('final-grade')">Final Grade</button></div><button class="export-choice-cancel" type="button" @click="exportChoiceOpen = false">Cancel</button></section></div>
        <div v-if="showFinalsNotice" class="modal-backdrop" @click.self="showFinalsNotice = false"><section class="finals-notice" role="dialog" aria-modal="true" aria-labelledby="period-lock-title"><div class="notice-icon">&#128197;</div><h2 id="period-lock-title">{{ periodLockTitle(noticePeriod) }}</h2><p>{{ periodLockMessage(noticePeriod) }}</p><button type="button" @click="showFinalsNotice = false">Close</button></section></div>
        <div v-if="customAssessmentOpen" class="modal-backdrop" @click.self="customAssessmentOpen = false"><form class="custom-assessment-dialog" role="dialog" aria-modal="true" aria-labelledby="custom-assessment-title" @submit.prevent="submitCustomAssessment"><h2 id="custom-assessment-title">Add Custom Assessment</h2><label>Include in category<select v-model="customAssessmentCategory" :disabled="isGradeLocked(activeScorePeriod)"><option v-for="type in customAssessmentCategories" :key="type.value" :value="type.value">{{ type.name }}</option></select></label><label>Assessment name<input v-model="customAssessmentName" :disabled="isGradeLocked(activeScorePeriod)" autofocus maxlength="40" placeholder="e.g. Project" required /></label><p v-if="customAssessmentCategory === 'custom'">This creates a separate grading category. Set its percentage and maximum score in Criteria after adding it.</p><p v-else>Scores are averaged with the selected category. Project uses the Assignment weight; Attendance uses the Recitation weight.</p><p v-if="customAssessmentError" class="custom-assessment-error" role="alert">{{ customAssessmentError }}</p><div class="custom-assessment-actions"><button type="button" @click="customAssessmentOpen = false">Cancel</button><button type="submit" :disabled="isGradeLocked(activeScorePeriod)">Add Assessment</button></div></form></div>
        <Teleport to="body"><div v-if="submissionConfirmOpen" class="modal-backdrop submission-confirmation-backdrop" @click.self="closeSubmissionConfirmation"><section class="submission-confirmation" role="dialog" aria-modal="true" aria-labelledby="submission-confirmation-title"><h2 id="submission-confirmation-title">Are you sure you want to submit?</h2><p>Once submitted, grades will be sent to the Registrar.<br />To request changes after submission, please contact the Registrar's Office directly.</p><p v-if="submissionError" class="submission-confirmation-error" role="alert">{{ submissionError }}</p><div class="submission-confirmation-actions"><button type="button" :disabled="isSubmitting" @click="closeSubmissionConfirmation">Go Back</button><button type="button" :disabled="isSubmitting || isSubmissionLockedByRegistrar" @click="submitToRegistrar(true)">{{ isSubmitting ? 'Submitting...' : 'Submit Grades' }}</button></div></section></div></Teleport>
        <div v-if="criteriaOpen" class="modal-backdrop" @click.self="closeCriteriaEditor"><section class="criteria-dialog" role="dialog" aria-modal="true" aria-labelledby="criteria-title"><h2 id="criteria-title">Grading Criteria</h2><p class="criteria-period-label">{{ criteriaPeriod === 'midterm' ? 'MIDTERM' : 'FINALS' }}</p><div class="criteria-table-scroll"><table><thead><tr><th>Items</th><th>Max Score</th><th>Percentage</th></tr></thead><tbody><tr v-for="item in criteriaRows" :key="item.key"><td>{{ item.name }}</td><td><input v-if="criteriaEditing" v-model.number="criteriaDraft[criteriaPeriod][item.key].maxScore" type="number" min="1" max="100" step="any" :disabled="isGradeLocked(criteriaPeriod)" :aria-label="`${item.name} maximum score`" @input="clearCriteriaError" /><span v-else>{{ item.maxScore }}</span></td><td><input v-if="criteriaEditing" v-model.number="criteriaDraft[criteriaPeriod][item.key].weight" type="number" min="0" :max="maxCriterionWeight(item.key)" step="0.01" :disabled="isGradeLocked(criteriaPeriod)" :aria-label="`${item.name} percentage`" @input="limitCriterionWeight(item.key, $event)" /><span v-else>{{ item.weight }}%</span></td></tr><tr class="total"><td>TOTAL</td><td>-</td><td>{{ criteriaWeightTotal }}%</td></tr></tbody></table></div><p class="criteria-help">Percentages for each grading period must total 100%. Scores are converted using each item's maximum score. Each assessment maximum must be between 1 and 100.</p><p v-if="criteriaError" class="criteria-error" role="alert">{{ criteriaError }}</p><div class="criteria-actions"><button v-if="!criteriaEditing" type="button" :disabled="isGradeLocked(criteriaPeriod)" @click="startCriteriaEdit">Edit</button><button v-if="!criteriaEditing" type="button" @click="closeCriteriaEditor">Done</button><template v-else><button type="button" :disabled="criteriaSaving" @click="cancelCriteriaEdit">Cancel</button><button type="button" :disabled="!canSaveCriteria" @click="saveCriteria">{{ criteriaSaving ? 'Saving...' : 'Save' }}</button></template></div></section></div>
      </div>
    </main>
  </div>
</template>

<script setup>
import { computed, onMounted, onUnmounted, reactive, ref, watch } from 'vue'
import colegioLogo from '../logo/The_Colegio_de_Montalban_Seal (1).png'
import { getGradeCriteria, getGradeDraft, getGradeSchedules, getGradeSheets, getStudents, saveGradeCriteria, saveGradeDraft, submitGradeSheet } from '../services/dataService'
import { assessmentCategory, assessmentCriterionKey, assessmentCriterionName, createDefaultCriteria, normalizePeriodScores, calculateRecordGrades, calculateTermGrade, FINAL_GRADE_WEIGHTS, hasZeroScore } from '../services/grading'
import { parseAcademicPeriod, sameAcademicPeriod } from '../services/academicPeriod'
import { getSystemDateString, parseSystemDate } from '../services/systemTime'
const activeRoster = ref([])
const draftSaveStatus = ref('')
const props = defineProps({ user: { type: Object, default: null } })
const emit = defineEmits(['signout', 'submitted'])
const isMobile = ref(window.innerWidth <= 900); const sidebarCollapsed = ref(isMobile.value); const view = ref('lists'); const searchStudent = ref(''); const selectedSection = ref('all'); const selectedYear = ref('1st Year'); const expandedInstitute = ref(''); const activePeriod = ref('midterm'); const selectedAssessment = ref('Q1'); const addChoiceOpen = ref(false); const removeChoiceOpen = ref(false); const restoreChoiceOpen = ref(false); const customAssessmentOpen = ref(false); const customAssessmentName = ref(''); const customAssessmentCategory = ref('assignment'); const customAssessmentError = ref(''); const submissionConfirmOpen = ref(false); const submissionError = ref(''); const isSubmitting = ref(false); const showFinalsNotice = ref(false); const noticePeriod = ref('finals'); const submissionNotice = ref(false); const submissionSent = ref(false); const years = ['1st Year', '2nd Year', '3rd Year', '4th Year']; const defaultGradeHeaders = ['Q1', 'Q2', 'Q3', 'Q4', 'Q5', 'L1', 'A1', 'A2', 'A3', 'A4', 'R1', 'Mid Ex']
const exportChoiceOpen = ref(false)
const gradeHeadersByPeriod = reactive({
  midterm: [...defaultGradeHeaders],
  finals: [...defaultGradeHeaders],
})
const assessmentTypes = [
  { name: 'Quiz', category: 'quiz', prefix: 'Q' },
  { name: 'Lab', category: 'lab', prefix: 'L' },
  { name: 'Assignment', category: 'assignment', prefix: 'A' },
  { name: 'Recitation', category: 'recitation', prefix: 'R' },
  { name: 'Major Exam', category: 'major_exam', prefix: 'Mid Ex' },
]
const customAssessmentCategories = [
  ...assessmentTypes.map(type => ({ ...type, value: type.category })),
  { name: 'Project', category: 'assignment', prefix: 'A', value: 'project' },
  { name: 'Attendance', category: 'recitation', prefix: 'R', value: 'attendance' },
  { name: 'Custom Category', category: 'assignment', prefix: 'A', value: 'custom' },
]
const gradingCriteria = ref(createDefaultCriteria(defaultGradeHeaders))
const criteriaDraft = ref(createDefaultCriteria(defaultGradeHeaders))
const criteriaOpen = ref(false)
const criteriaEditing = ref(false)
const criteriaSaving = ref(false)
const criteriaError = ref('')
const scoreEntryError = ref('')
const maxScoreError = ref('')
const criteriaPeriod = ref('midterm')
const midtermItems = [{ name: 'Quiz' }, { name: 'Activity' }, { name: 'Recitation' }, { name: 'Major Exam' }]
const gradePeriods = ref([])
const gradeSheets = ref([])
const currentClock = ref(Date.now())
const currentGradePeriod = computed(() => {
  const openPeriods = gradePeriods.value.filter(period => !period.submitGradeLocked)
  const periodsToSort = openPeriods.length ? openPeriods : gradePeriods.value

  return [...periodsToSort].sort((left, right) => {
    const leftPeriod = parseAcademicPeriod(left.semester, left.schoolYear)
    const rightPeriod = parseAcademicPeriod(right.semester, right.schoolYear)
    const yearDifference = Number(rightPeriod.schoolYear.slice(0, 4)) - Number(leftPeriod.schoolYear.slice(0, 4))
    if (yearDifference) return yearDifference
    const semesterRank = (semester) => semester.startsWith('2nd') ? 2 : 1
    return semesterRank(rightPeriod.semester) - semesterRank(leftPeriod.semester)
  })[0]
})
const professorGradeSheets = computed(() => {
  const currentPeriod = currentGradePeriod.value
  if (!currentPeriod) return gradeSheets.value

  const currentSheets = gradeSheets.value.filter(sheet => sameAcademicPeriod(
    sheet.semester,
    '',
    currentPeriod.semester,
    currentPeriod.schoolYear,
  ))
  const currentRank = academicPeriodRank(`${currentPeriod.semester} ${currentPeriod.schoolYear}`)
  const currentCourseKeys = new Set(currentSheets.map(sheet => `${sheet.code}:${sheet.section}`))
  const latestPreviousSheets = new Map()
  gradeSheets.value
    .filter(sheet => academicPeriodRank(sheet.semester) < currentRank)
    .sort((left, right) => academicPeriodRank(right.semester) - academicPeriodRank(left.semester))
    .forEach(sheet => {
      const key = `${sheet.code}:${sheet.section}`
      if (!currentCourseKeys.has(key) && !latestPreviousSheets.has(key)) {
        latestPreviousSheets.set(key, sheet)
      }
    })

  if (currentPeriod.submitGradeLocked) {
    return gradeSheets.value
  }

  const blankCurrentSheets = [...latestPreviousSheets.values()].map(sheet => {
    const studentRecords = (sheet.student_records || []).map(student => ({
      id: student.id,
      name: student.name,
      midterm: '',
      finals: '',
      finalGrade: '',
      gradePoint: '',
      remarks: '',
      scores: { midterm: {}, finals: {} },
    }))
    return {
      ...sheet,
      semester: `${currentPeriod.semester} ${currentPeriod.schoolYear}`,
      students: studentRecords.length || sheet.students,
      submitted: '',
      status: 'Submitted',
      rejection_reason: null,
      student_records: studentRecords,
    }
  })

  return [...gradeSheets.value, ...blankCurrentSheets]
})
const isFinalGradeSubmissionLocked = computed(() => {
  const gradePeriod = getGradePeriodSchedule()
  return !parseSystemDate(gradePeriod?.submitGradeDeadline) || Boolean(gradePeriod?.submitGradeLocked)
})
const isSubmissionLockedByRegistrar = computed(() => Boolean(getGradePeriodSchedule()?.submitGradeLocked))
const hasFinalGradeSubmissionDeadline = computed(() => Boolean(parseSystemDate(getGradePeriodSchedule()?.submitGradeDeadline)))
const finalGradeSubmissionDeadlineMessage = computed(() => {
  const gradePeriod = getGradePeriodSchedule()
  const deadline = resolveDeadline(gradePeriod?.submitGradeDeadline)
  if (!deadline) return 'Submit of Grade Deadline is not set.'
  const formattedDeadline = deadline.toLocaleDateString('en-US', { month: 'long', day: 'numeric', year: 'numeric' })
  if (gradePeriod.submitGradeLocked) return `The Registrar locked grade submission after the deadline on ${formattedDeadline}.`
  if (getSystemDateString(currentClock.value) > gradePeriod.submitGradeDeadline) {
    return `The Submit of Grade Deadline passed on ${formattedDeadline}. Grade submission is awaiting Registrar lock.`
  }
  return `Submit final grades by ${formattedDeadline}.`
})
const activeScorePeriod = computed(() => activePeriod.value === 'finals' ? 'finals' : 'midterm')
const gradeHeaders = computed(() => gradeHeadersByPeriod[activeScorePeriod.value])
const headersByPeriod = computed(() => ({
  midterm: [...gradeHeadersByPeriod.midterm],
  finals: [...gradeHeadersByPeriod.finals],
}))
const gradeGroups = computed(() => [
  { name: 'QUIZ', category: 'quiz' },
  { name: 'LAB', category: 'lab' },
  { name: 'RECITATION', category: 'recitation' },
  { name: 'ASSIGNMENT', category: 'assignment' },
  { name: 'MAJOR EXAM', category: 'major_exam' },
].map(group => ({ ...group, headers: gradeHeaders.value.filter(header => assessmentCategory(header) === group.category) })))
const criteriaRows = computed(() => {
  const currentCriteria = criteriaEditing.value ? criteriaDraft.value : gradingCriteria.value
  const rules = currentCriteria[criteriaPeriod.value] || {}
  const customKeys = [...new Set(gradeHeaders.value
    .map(assessmentCriterionKey)
    .filter(key => key?.startsWith('custom:')))]
  const keys = [...new Set([...Object.keys(rules).filter(key => !key.startsWith('custom:')), ...customKeys])]
  return keys.map(key => ({
    key,
    name: key.startsWith('custom:')
      ? assessmentDisplayName(gradeHeaders.value.find(header => assessmentCriterionKey(header) === key) || key)
      : assessmentCriterionName(key),
    weight: Number(rules[key]?.weight ?? 0),
    maxScore: Number(rules[key]?.maxScore ?? 100),
  }))
})
const criteriaWeightTotal = computed(() => Math.round(criteriaRows.value.reduce((sum, item) => sum + item.weight, 0) * 100) / 100)
const canSaveCriteria = computed(() => (
  !criteriaSaving.value
    && !isGradeLocked(criteriaPeriod.value)
    && !criteriaError.value
    && Math.abs(criteriaWeightTotal.value - 100) <= 0.001
    && criteriaRows.value.every(item => Number.isFinite(item.weight) && item.weight >= 0 && Number.isFinite(item.maxScore) && item.maxScore > 0 && item.maxScore <= 100)
))
watch(activeScorePeriod, period => {
  criteriaPeriod.value = period
})

function criteriaForHeaders(headersByPeriod, savedCriteria = null) {
  const defaults = {
    midterm: createDefaultCriteria(headersByPeriod.midterm).midterm,
    finals: createDefaultCriteria(headersByPeriod.finals).finals,
  }
  if (!savedCriteria) return defaults
  for (const period of ['midterm', 'finals']) {
    const keys = new Set(headersByPeriod[period].map(assessmentCriterionKey).filter(Boolean))
    for (const key of keys) {
      const saved = savedCriteria[period]?.[key]
      if (saved
        && Number.isFinite(Number(saved.weight))
        && Number(saved.weight) >= 0
        && Number(saved.weight) <= 100
        && Number.isFinite(Number(saved.maxScore))
        && Number(saved.maxScore) > 0
        && Number(saved.maxScore) <= 100) {
        defaults[period][key] = { weight: Number(saved.weight), maxScore: Number(saved.maxScore) }
      }
    }
  }
  return defaults
}

function maxScoreFor(header, period) {
  const key = assessmentCriterionKey(header)
  return Math.min(100, key ? gradingCriteria.value[period]?.[key]?.maxScore || 100 : 100)
}

function updateScore(student, header, period, event) {
  if (isGradeLocked(period)) {
    openGradingPeriod(period)
    return
  }
  const input = event.target
  const value = input.value
  if (value === '') {
    student.scores[period][header] = ''
    scoreEntryError.value = ''
    return
  }
  const maximum = maxScoreFor(header, period)
  const score = Number(value)
  if (!Number.isFinite(score) || score < 0 || score > maximum) {
    input.value = student.scores[period][header] ?? ''
    scoreEntryError.value = `Score must be between 0 and ${maximum}. The previous valid score was kept.`
    return
  }
  input.value = String(score)
  student.scores[period][header] = String(score)
  scoreEntryError.value = ''
}

function maxCriterionWeight(key) {
  const periodDraft = criteriaDraft.value[criteriaPeriod.value] || {}
  const otherWeights = Object.entries(periodDraft)
    .filter(([criterionKey]) => criterionKey !== key)
    .reduce((sum, [, criterion]) => sum + Number(criterion.weight || 0), 0)
  return Math.max(0, Math.round((100 - otherWeights) * 100) / 100)
}

function limitCriterionWeight(key, event) {
  const input = event.target
  const enteredWeight = Number(input.value)
  if (!Number.isFinite(enteredWeight)) return
  const maximumWeight = maxCriterionWeight(key)
  if (enteredWeight > maximumWeight) {
    criteriaDraft.value[criteriaPeriod.value][key].weight = maximumWeight
    input.value = String(maximumWeight)
    criteriaError.value = `Maximum allowed is ${maximumWeight}%. Total criteria cannot exceed 100%.`
    return
  }
  criteriaError.value = ''
}

function clearCriteriaError() {
  criteriaError.value = ''
}

function openCriteriaEditor() {
  criteriaPeriod.value = activePeriod.value === 'finals' ? 'finals' : 'midterm'
  criteriaEditing.value = false
  criteriaError.value = ''
  criteriaOpen.value = true
}

function startCriteriaEdit() {
  criteriaDraft.value = JSON.parse(JSON.stringify(gradingCriteria.value))
  criteriaEditing.value = true
  criteriaError.value = ''
}

function cancelCriteriaEdit() {
  criteriaEditing.value = false
  criteriaError.value = ''
}

function closeCriteriaEditor() {
  if (criteriaSaving.value) return
  criteriaOpen.value = false
  criteriaEditing.value = false
  criteriaError.value = ''
}

async function saveCriteria() {
  if (!canSaveCriteria.value) return
  const criteria = criteriaForHeaders(headersByPeriod.value, criteriaDraft.value)
  for (const period of ['midterm', 'finals']) {
    const periodCriteria = Object.values(criteria[period])
    const total = periodCriteria.reduce((sum, item) => sum + Number(item.weight), 0)
    if (periodCriteria.some(item => !Number.isFinite(Number(item.weight)) || Number(item.weight) < 0 || !Number.isFinite(Number(item.maxScore)) || Number(item.maxScore) <= 0 || Number(item.maxScore) > 100)) {
      criteriaError.value = 'Enter valid percentages and maximum scores from 1 to 100 for all criteria.'
      return
    }
    if (Math.abs(total - 100) > 0.001) {
      criteriaError.value = `${period === 'midterm' ? 'Midterm' : 'Finals'} percentages must total 100%.`
      return
    }
  }
  if (!selectedCourse.value.code || !selectedCourse.value.semester || !selectedCourse.value.section) {
    criteriaError.value = 'Select a valid course before saving criteria.'
    return
  }

  criteriaSaving.value = true
  criteriaError.value = ''
  try {
    const saved = await saveGradeCriteria({
      code: selectedCourse.value.code,
      section: selectedCourse.value.section,
      semester: selectedCourse.value.semester,
    }, criteria, headersByPeriod.value)
    gradingCriteria.value = criteriaForHeaders(headersByPeriod.value, saved.criteria)
    criteriaDraft.value = JSON.parse(JSON.stringify(gradingCriteria.value))
    criteriaEditing.value = false
  } catch (error) {
    criteriaError.value = error instanceof Error ? error.message : 'Unable to save grading criteria.'
  } finally {
    criteriaSaving.value = false
  }
}

function gradeResult(student) {
  return calculateRecordGrades(student.scores || { midterm: {}, finals: {} }, headersByPeriod.value, gradingCriteria.value)
}

function displayGrade(student, period) {
  if (period !== 'final-grade') {
    return displayTermGrade(student, period)
  }
  if (hasZeroScore(student.scores || { midterm: {}, finals: {} })) {
    return 'FAILED'
  }
  const grades = gradeResult(student)
  return grades ? grades.finalGrade : 'FAILED'
}

function displayEquivalentGrade(student) {
  return gradeResult(student)?.gradePoint || 'FAILED'
}

function displayTermGrade(student, period) {
  if (hasZeroScore(student.scores || { midterm: {}, finals: {} }, period)) {
    return 'FAILED'
  }
  const termGrade = calculateTermGrade(student.scores || { midterm: {}, finals: {} }, period, headersByPeriod.value[period], gradingCriteria.value)
  return termGrade === null ? 'FAILED' : `${termGrade.toFixed(2)}%`
}

async function exportGrades(period) {
  try {
    const isFinalGrade = period === 'final-grade'
    const periodLabel = period === 'midterm' ? 'Midterm' : period === 'finals' ? 'Finals' : 'Final Grade'
    const columns = isFinalGrade
      ? [
          { header: '#', key: 'number', width: 6 },
          { header: 'Student ID', key: 'studentId', width: 16 },
          { header: 'Student Name', key: 'studentName', width: 28 },
          { header: `Midterm (${FINAL_GRADE_WEIGHTS.midterm}%)`, key: 'midterm', width: 18 },
          { header: `Finals (${FINAL_GRADE_WEIGHTS.finals}%)`, key: 'finals', width: 16 },
          { header: 'Final Grade', key: 'finalGrade', width: 14 },
          { header: 'Equivalent Grade', key: 'equivalentGrade', width: 18 },
        ]
      : [
          { header: '#', key: 'number', width: 6 },
          { header: 'Student ID', key: 'studentId', width: 16 },
          { header: 'Student Name', key: 'studentName', width: 28 },
          { header: `${periodLabel} Grade`, key: 'grade', width: 18 },
        ]
    const rows = allGradeStudents.map((student, index) => {
      const row = {
        number: index + 1,
        studentId: student.id,
        studentName: student.name,
      }
      if (isFinalGrade) {
        row.midterm = displayTermGrade(student, 'midterm')
        row.finals = displayTermGrade(student, 'finals')
        row.finalGrade = displayGrade(student, 'final-grade')
        row.equivalentGrade = displayEquivalentGrade(student)
      } else {
        row.grade = displayTermGrade(student, period)
      }
      return row
    })
    const filePart = value => value.trim().replace(/[<>:"/\\|?*\x00-\x1F]/g, '-').replace(/\s+/g, '_')
    const filename = [
      selectedCourse.value.code,
      selectedCourse.value.section,
      selectedCourse.value.semester,
      period === 'final-grade' ? 'final_grades' : `${period}_grades`,
    ].map(filePart).filter(Boolean).join('_') || 'final_grades'
    const ExcelJS = await import('exceljs')
    const workbook = new ExcelJS.Workbook()
    const worksheet = workbook.addWorksheet(periodLabel)
    worksheet.columns = columns
    worksheet.addRows(rows)
    worksheet.autoFilter = { from: 'A1', to: `${String.fromCharCode(64 + columns.length)}${Math.max(rows.length + 1, 1)}` }
    const content = await workbook.xlsx.writeBuffer()
    const url = URL.createObjectURL(new Blob([content], {
      type: 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
    }))
    const link = document.createElement('a')
    link.href = url
    link.download = `${filename}.xlsx`
    document.body.append(link)
    link.click()
    link.remove()
    exportChoiceOpen.value = false
    window.setTimeout(() => URL.revokeObjectURL(url), 1000)
  } catch (error) {
    console.error('Unable to export grades.', error)
    window.alert(error instanceof Error ? error.message : 'Unable to export grades.')
  }
}

function exceedsMaxScore(student, header, period) {
  const score = student.scores?.[period]?.[header]
  return score !== undefined && score !== null && score !== ''
    && Number(score) > maxScoreFor(header, period)
}

function resolveDeadline(dateValue) {
  return parseSystemDate(dateValue)
}

function academicPeriodRank(value) {
  const period = parseAcademicPeriod(value)
  const startYear = Number(period.schoolYear.match(/^\d{4}/)?.[0])
  const semesterRank = period.semester.startsWith('2nd') ? 2 : 1
  return (Number.isFinite(startYear) ? startYear : 0) * 10 + semesterRank
}

function getGradePeriodSchedule() {
  return gradePeriods.value.find(item => sameAcademicPeriod(
    item.semester,
    item.schoolYear,
    selectedCourse.value.semester || '',
  ))
}

function getGradePeriod(type) {
  const period = getGradePeriodSchedule()
  if (!period) return null
  return {
    submissionLocked: period.submitGradeLocked,
  }
}

function isGradeLocked(type) {
  const period = getGradePeriod(type)
  return !period || period.submissionLocked
}

function periodLockTitle(type) {
  const label = type === 'midterm' ? 'Midterm' : 'Finals'
  const period = getGradePeriod(type)
  return period?.submissionLocked ? 'Grade submission is locked' : `${label} grading period is not set`
}

function periodLockMessage(type) {
  const period = getGradePeriod(type)
  if (period?.submissionLocked) return 'The Registrar has locked grade submission for this semester.'
  const academicPeriod = parseAcademicPeriod(selectedCourse.value.semester || '')
  if (academicPeriod.semester && academicPeriod.schoolYear.match(/^\d{4}-\d{4}$/)) {
    const semesterLabel = academicPeriod.semester.replace(/semester/i, 'Semester')
    return `No Grade Period is set for ${semesterLabel}, A.Y. ${academicPeriod.schoolYear}. Set a submission deadline in the Registrar dashboard.`
  }
  return 'The Registrar must set a Grade Period for this semester before grading can begin.'
}

async function openGradingPeriod(type) {
  try {
    const schedules = await getGradeSchedules()
    gradePeriods.value = schedules.filter(schedule => schedule.yearLevel === 'Grade Period')
  } catch (error) {
    window.alert(error instanceof Error ? error.message : 'Unable to verify the Registrar grading schedule.')
    return false
  }
  currentClock.value = Date.now()
  if (!getGradePeriod(type)) {
    noticePeriod.value = type
    showFinalsNotice.value = true
    return false
  }
  activePeriod.value = type
  showFinalsNotice.value = false
  return true
}
function instituteForSection(section = '') {
  const program = section.split(' -')[0].trim().toUpperCase()
  if (/^BSIT|^IT/.test(program)) return { id: 'ics', name: 'ICS - Institute of Computing Studies' }
  if (/^BEED|^BSED/.test(program)) return { id: 'ite', name: 'ITE - Institute of Teachers Education' }
  return { id: 'ibe', name: 'IBE - Institute of Business Entrepreneurship' }
}

const institutes = computed(() => {
  const instituteMap = new Map()
  ;[
    { id: 'ibe', name: 'IBE - Institute of Business Entrepreneurship' },
    { id: 'ics', name: 'ICS - Institute of Computing Studies' },
    { id: 'ite', name: 'ITE - Institute of Teachers Education' },
  ].forEach(institute => instituteMap.set(institute.id, { ...institute, groups: [] }))
  professorGradeSheets.value.forEach(sheet => {
    const institute = instituteForSection(sheet.section)
    const target = instituteMap.get(institute.id)
    let group = target.groups.find(item => item.name === sheet.subject.toUpperCase())
    if (!group) {
      group = { name: sheet.subject.toUpperCase(), courses: [] }
      target.groups.push(group)
    }
    group.courses.push({
      id: sheet.id,
      code: sheet.code,
      section: sheet.section,
      students: sheet.student_records?.length ?? sheet.students ?? 0,
      units: sheet.units,
      name: sheet.subject,
      semester: sheet.semester,
      rejection_reason: sheet.rejection_reason,
      student_records: sheet.student_records || [],
    })
  })
  return [...instituteMap.values()]
})

const selectedInstitute = computed(() => institutes.value.find(institute => institute.id === expandedInstitute.value) || institutes.value[0] || { groups: [] })
const visibleGroups = computed(() => (selectedInstitute.value.groups || []).map(group => ({
  ...group,
  courses: group.courses.filter(course => {
    const courseYear = String(course.section || '').match(/\b([1-4])[A-D]?\b/i)?.[1]
    const matchesYear = courseYear === selectedYear.value.charAt(0)
    const matchesSection = selectedSection.value === 'all' || course.section.includes(selectedSection.value)
    return matchesYear && matchesSection
  }),
})).filter(group => group.courses.length > 0))
const sections = computed(() => institutes.value.flatMap(institute => institute.groups.flatMap(group => group.courses.map(course => course.section))))
const selectedCourse = ref({ name: '', section: '' })
const currentGradeSheet = computed(() => gradeSheets.value.find(sheet =>
  sheet.code === selectedCourse.value.code
    && sheet.section === selectedCourse.value.section
    && sameAcademicPeriod(sheet.semester, '', selectedCourse.value.semester || ''),
))
const isGradeSheetSubmitted = computed(() => {
  const sheet = currentGradeSheet.value
  return Boolean(sheet && ['Submitted', 'Approved', 'Published'].includes(sheet.status) && !sheet.rejection_reason)
})
const hasReturnedGradeSheet = computed(() => Boolean(currentGradeSheet.value?.rejection_reason))
const selectedAcademicPeriod = computed(() => parseAcademicPeriod(selectedCourse.value.semester || ''))
const selectedSchoolYear = computed(() => selectedAcademicPeriod.value.schoolYear)
const selectedSemester = computed(() => selectedAcademicPeriod.value.semester.replace(/\b\w/g, character => character.toUpperCase()))
let draftSaveTimer
let draftSaveQueue = Promise.resolve()
let draftSaveVersion = 0
const gradeStudents = reactive([])
const hasIncompleteGrades = computed(() => gradeStudents.length === 0 || gradeStudents.some(student => !String(student.id || '').trim() || !String(student.name || '').trim() || !String(student.midterm || '').trim() || !String(student.finals || '').trim() || !String(student.finalGrade || '').trim() || !String(student.gradePoint || '').trim()))
function toggleInstitute(id) {
  expandedInstitute.value = expandedInstitute.value === id ? '' : id
}

async function openGrades(course) {
  await flushGradeDraftSave()
  selectedCourse.value = course || selectedCourse.value
  const sectionParts = String(selectedCourse.value.section || '').toUpperCase().split(' - ')
  const classProgram = sectionParts[0] || ''
  const classSection = sectionParts[1] || ''
  const classYear = classSection.match(/[1-4]/)?.[0]
  const classLetter = classSection.match(/[A-D]$/)?.[0]
  const roster = allGradeStudents.filter(student => {
    const studentCourse = String(student.course || '').toUpperCase()
    const studentYear = String(student.year_level || '')
    const studentSection = String(student.section || '').toUpperCase()
    return (!studentCourse || !classProgram || classProgram.startsWith(studentCourse) || studentCourse.startsWith(classProgram))
      && (!classYear || !studentYear || studentYear.startsWith(classYear))
      && (!classLetter || !studentSection || studentSection === classLetter)
  })
  const savedRecords = new Map((selectedCourse.value.student_records || []).map(record => [record.id, record]))
  const courseKey = `${selectedCourse.value.code}:${selectedCourse.value.section}:${selectedCourse.value.semester}`
  let savedCriteria
  let savedDraft
  try {
    const courseIdentity = {
      code: selectedCourse.value.code,
      section: selectedCourse.value.section,
      semester: selectedCourse.value.semester,
    }
    const savedData = await Promise.all([
      getGradeCriteria(courseIdentity),
      getGradeDraft(courseIdentity),
    ])
    savedCriteria = savedData[0]
    savedDraft = savedData[1]
  } catch (error) {
    window.alert(error instanceof Error ? error.message : 'Unable to load saved grading data.')
    return
  }
  if (`${selectedCourse.value.code}:${selectedCourse.value.section}:${selectedCourse.value.semester}` !== courseKey) return
  restoreGradeHeaders(selectedCourse.value.student_records || [], savedDraft?.assessmentHeaders || savedCriteria.assessmentHeaders)
  gradingCriteria.value = criteriaForHeaders(headersByPeriod.value, savedCriteria.criteria)
  const rosterById = new Map(roster.map(student => [student.id, student]))
  savedRecords.forEach(saved => {
    if (!rosterById.has(saved.id)) rosterById.set(saved.id, { id: saved.id, name: saved.name })
  })
  const draftRecords = new Map((savedDraft?.studentRecords || []).map(record => [record.id, record]))
  draftRecords.forEach(saved => {
    if (!rosterById.has(saved.id)) rosterById.set(saved.id, { id: saved.id, name: saved.name })
  })
  if (savedDraft) {
    selectedCourse.value = { ...selectedCourse.value, name: savedDraft.subject, units: savedDraft.units }
  }
  const loadedRoster = [...rosterById.values()].map(student => {
    const saved = draftRecords.get(student.id) || savedRecords.get(student.id)
    const periodScores = normalizePeriodScores(saved?.scores || student.scores || {})
    gradeHeadersByPeriod.midterm.forEach(label => { periodScores.midterm[label] ??= '' })
    gradeHeadersByPeriod.finals.forEach(label => { periodScores.finals[label] ??= '' })
    return { ...student, ...(saved || {}), scores: periodScores }
  })
  activeRoster.value = loadedRoster
  originalGradeStudents.splice(0, originalGradeStudents.length, ...loadedRoster.map(student => ({
    ...student,
    scores: { midterm: { ...student.scores.midterm }, finals: { ...student.scores.finals } },
  })))
  allGradeStudents.splice(0, allGradeStudents.length, ...loadedRoster)
  gradeStudents.splice(0, gradeStudents.length, ...loadedRoster)
  activePeriod.value = 'midterm'
  draftSaveStatus.value = ''
  view.value = 'grades'
  openGradingPeriod('midterm')
}

async function submitToRegistrar(confirmed = false) {
  if (isSubmitting.value || isGradeSheetSubmitted.value) return
  if (activePeriod.value !== 'final-grade') {
    return
  }
  if (isFinalGradeSubmissionLocked.value) {
    submissionError.value = isSubmissionLockedByRegistrar.value
      ? 'The submission is locked by the Registrar.'
      : finalGradeSubmissionDeadlineMessage.value
    submissionConfirmOpen.value = true
    return
  }

  if (!confirmed) {
    submissionError.value = ''
    submissionConfirmOpen.value = true
    return
  }
  await flushGradeDraftSave()
  currentClock.value = Date.now()
  if (isFinalGradeSubmissionLocked.value) {
    submissionError.value = isSubmissionLockedByRegistrar.value
      ? 'The submission is locked by the Registrar.'
      : finalGradeSubmissionDeadlineMessage.value
    submissionConfirmOpen.value = true
    return
  }

  const records = allGradeStudents
    .filter(student => student.id.trim() && student.name.trim())
    .map(student => {
      const scores = student.scores || { midterm: {}, finals: {} }
      const grades = gradeResult(student)
      const midtermGrade = calculateTermGrade(scores, 'midterm', headersByPeriod.value.midterm, gradingCriteria.value)
      const finalsGrade = calculateTermGrade(scores, 'finals', headersByPeriod.value.finals, gradingCriteria.value)
      return {
        id: student.id.trim(),
        name: student.name.trim(),
        midterm: grades?.midterm || midtermGrade?.toFixed(2) || student.midterm || '',
        finals: grades?.finals || finalsGrade?.toFixed(2) || student.finals || '',
        finalGrade: grades?.finalGrade || '',
        gradePoint: grades?.gradePoint || '',
        remarks: grades?.remarks || '',
        midtermGrade,
        finalsGrade,
        scores: { midterm: scores.midterm || {}, finals: scores.finals || {} },
      }
    })

  if (!records.length || records.length !== allGradeStudents.length) {
    submissionError.value = 'Enter a student ID and name for every row before submitting.'
    return
  }
  if (records.some(student => student.midtermGrade === null || student.finalsGrade === null)) {
    submissionError.value = 'Complete every assessment score in both Midterm and Finals before submitting the final grade. Scores must not exceed their maximum.'
    return
  }
  if (!selectedCourse.value.code || !selectedCourse.value.semester) {
    submissionError.value = 'Select a valid course before submitting grades.'
    return
  }
  if (!Number(selectedCourse.value.units)) {
    submissionError.value = 'Enter the actual units for this subject before submitting.'
    return
  }

  isSubmitting.value = true
  try {
    const savedSheet = await submitGradeSheet({
      code: selectedCourse.value.code,
      subject: selectedCourse.value.name,
      section: selectedCourse.value.section,
      professor: props.user?.name || 'Professor',
      semester: selectedCourse.value.semester,
      students: records.length,
      units: Number(selectedCourse.value.units),
      submitted: getSystemDateString(currentClock.value),
      status: 'Submitted',
    }, records.map(({ midtermGrade, finalsGrade, ...record }) => record), 'both')
    const savedSheetIndex = gradeSheets.value.findIndex(sheet => sheet.id === savedSheet.id)
    if (savedSheetIndex === -1) gradeSheets.value.unshift(savedSheet)
    else gradeSheets.value[savedSheetIndex] = savedSheet
    selectedCourse.value = { ...selectedCourse.value, ...savedSheet, rejection_reason: null }
    submissionSent.value = true
    submissionConfirmOpen.value = false
    emit('submitted')
  } catch (error) {
    submissionError.value = error instanceof Error && error.message.includes('locked grade submission')
      ? 'The submission is locked by the Registrar.'
      : error instanceof Error ? error.message : 'Unable to submit grades.'
  } finally {
    isSubmitting.value = false
  }
}

function openSubmissionConfirmation() {
  if (isSubmitting.value || isGradeSheetSubmitted.value || activePeriod.value !== 'final-grade') return
  if (isSubmissionLockedByRegistrar.value) {
    noticePeriod.value = activeScorePeriod.value
    showFinalsNotice.value = true
    return
  }
  submissionError.value = isFinalGradeSubmissionLocked.value ? finalGradeSubmissionDeadlineMessage.value : ''
  submissionConfirmOpen.value = true
}

function closeSubmissionConfirmation() {
  if (isSubmitting.value) return
  submissionConfirmOpen.value = false
  submissionError.value = ''
}

function goToLists() {
  void flushGradeDraftSave()
  view.value = 'lists'
}

async function signOut() {
  await flushGradeDraftSave()
  if (draftSaveStatus.value) {
    window.alert('Your grade changes could not be saved. Please check your connection and try signing out again.')
    return
  }
  emit('signout')
}

function handleResize() {
  const nextIsMobile = window.innerWidth <= 900
  if (nextIsMobile !== isMobile.value) {
    sidebarCollapsed.value = nextIsMobile
    isMobile.value = nextIsMobile
  }
}

onMounted(() => {
  window.addEventListener('resize', handleResize)
})

onUnmounted(() => window.removeEventListener('resize', handleResize))
  gradeStudents.splice(0)
  selectedCourse.value = { name: '', section: '' }
const allGradeStudents = reactive([])
const originalGradeStudents = reactive([])

function currentGradeDraft() {
  if (view.value !== 'grades' || isGradeLocked(activeScorePeriod.value) || !selectedCourse.value.code || !selectedCourse.value.section || !selectedCourse.value.semester) return null
  return {
    course: {
      code: selectedCourse.value.code,
      section: selectedCourse.value.section,
      semester: selectedCourse.value.semester,
    },
    draft: {
      subject: selectedCourse.value.name || '',
      units: Number(selectedCourse.value.units) || 0,
      assessmentHeaders: headersByPeriod.value,
      studentRecords: allGradeStudents.map(student => ({
        id: String(student.id),
        name: String(student.name),
        scores: {
          midterm: { ...student.scores.midterm },
          finals: { ...student.scores.finals },
        },
      })),
    },
  }
}

function enqueueGradeDraftSave(snapshot, version) {
  const save = draftSaveQueue.then(() => saveGradeDraft(snapshot.course, snapshot.draft))
  draftSaveQueue = save.then(() => {
    if (version === draftSaveVersion) draftSaveStatus.value = ''
  }).catch(error => {
    if (version === draftSaveVersion) {
      draftSaveStatus.value = 'Draft could not be saved. Check your connection and keep this page open.'
    }
    console.error('Unable to save grade draft.', error)
  })
  return draftSaveQueue
}

function scheduleGradeDraftSave() {
  window.clearTimeout(draftSaveTimer)
  const snapshot = currentGradeDraft()
  if (!snapshot) {
    draftSaveStatus.value = ''
    return
  }
  const version = ++draftSaveVersion
  draftSaveStatus.value = 'Saving draft...'
  draftSaveTimer = window.setTimeout(() => {
    enqueueGradeDraftSave(snapshot, version)
  }, 250)
}

async function flushGradeDraftSave() {
  window.clearTimeout(draftSaveTimer)
  const snapshot = currentGradeDraft()
  if (snapshot) {
    const version = ++draftSaveVersion
    draftSaveStatus.value = 'Saving draft...'
    await enqueueGradeDraftSave(snapshot, version)
  } else {
    await draftSaveQueue
  }
}

watch(
  [() => view.value, () => selectedCourse.value.code, () => selectedCourse.value.section, () => selectedCourse.value.semester, () => headersByPeriod.value, () => allGradeStudents, () => isSubmissionLockedByRegistrar.value],
  scheduleGradeDraftSave,
  { deep: true, flush: 'post' },
)

watch(submissionSent, (submitted) => {
  if (submitted) {
    submissionNotice.value = true
    window.setTimeout(() => {
      submissionNotice.value = false
    }, 2500)
  }
})

function assessmentPrefix(category) {
  return assessmentTypes.find(type => type.category === category)?.prefix
}

function assessmentDisplayName(label) {
  return label.replace(/^(?:Mid Ex|[QLAR])\d+:\s*/, '')
}

function restoreGradeHeaders(records, storedHeaders = []) {
  const legacyHeaders = Array.isArray(storedHeaders) ? storedHeaders : []
  const headers = Array.isArray(storedHeaders)
    ? { midterm: [], finals: [] }
    : storedHeaders
  const scoreHeaders = records.flatMap(record => {
    const scores = normalizePeriodScores(record.scores || {})
    return [...Object.keys(scores.midterm), ...Object.keys(scores.finals)]
  })
  const customHeaders = [...new Set([...legacyHeaders, ...scoreHeaders])]
    .filter(header => !defaultGradeHeaders.includes(header) && assessmentCategory(header))
  for (const period of ['midterm', 'finals']) {
    const periodCustomHeaders = customHeaders.filter(header => {
      if (!Array.isArray(storedHeaders)) {
        return headers[period].includes(header) || records.some(record => {
          const scores = normalizePeriodScores(record.scores || {})
          return scores[period][header] !== undefined
        })
      }
      const hasScoreInPeriod = records.some(record => {
        const scores = normalizePeriodScores(record.scores || {})
        const score = scores[period][header]
        return score !== undefined && score !== null && score !== ''
      })
      const hasScoreInOtherPeriod = records.some(record => {
        const scores = normalizePeriodScores(record.scores || {})
        const otherPeriod = period === 'midterm' ? 'finals' : 'midterm'
        const score = scores[otherPeriod][header]
        return score !== undefined && score !== null && score !== ''
      })
      return hasScoreInPeriod || (!hasScoreInOtherPeriod && period === 'midterm')
    })
    const restoredHeaders = [...new Set([...defaultGradeHeaders, ...headers[period], ...periodCustomHeaders])]
      .filter(header => assessmentCategory(header))
    gradeHeadersByPeriod[period].splice(0, gradeHeadersByPeriod[period].length, ...restoredHeaders)
  }
}

function addAssessment(category, name = '') {
  const period = activeScorePeriod.value
  if (isGradeLocked(period)) {
    openGradingPeriod(period)
    return
  }
  const periodHeaders = gradeHeadersByPeriod[period]
  const prefix = assessmentPrefix(category)
  if (!prefix) return
  let number = 1
  while (periodHeaders.some(header => header === `${prefix}${number}` || header.startsWith(`${prefix}${number}:`))) {
    number += 1
  }
  const label = `${prefix}${number}${name ? `: ${name}` : ''}`
  periodHeaders.push(label)
  const criterionKey = assessmentCriterionKey(label)
  if (criterionKey?.startsWith('custom:')) {
    gradingCriteria.value[period][criterionKey] ??= { weight: 0, maxScore: 100 }
  }
  allGradeStudents.forEach(student => {
    student.scores[period][label] = ''
  })
  gradeStudents.forEach(student => {
    student.scores[period][label] = ''
  })
  selectedAssessment.value = label
  addChoiceOpen.value = false
}

function openCustomAssessment() {
  addChoiceOpen.value = false
  customAssessmentName.value = ''
  customAssessmentCategory.value = 'assignment'
  customAssessmentError.value = ''
  customAssessmentOpen.value = true
}

function submitCustomAssessment() {
  if (isGradeLocked(activeScorePeriod.value)) {
    openGradingPeriod(activeScorePeriod.value)
    customAssessmentOpen.value = false
    return
  }
  const name = customAssessmentName.value.trim()
  const selectedCategory = customAssessmentCategories.find(type => type.value === customAssessmentCategory.value)
  if (!name || !selectedCategory) {
    customAssessmentError.value = 'Enter a name and choose a valid grading category.'
    return
  }
  const duplicate = gradeHeaders.value.some(header => (
    assessmentCategory(header) === selectedCategory.category
      && assessmentDisplayName(header).toLocaleLowerCase() === name.toLocaleLowerCase()
  ))
  if (duplicate) {
    customAssessmentError.value = 'An assessment with this name already exists in that category.'
    return
  }
  addAssessment(selectedCategory.category, name)
  customAssessmentOpen.value = false
}

function toggleAddChoices() {
  addChoiceOpen.value = !addChoiceOpen.value
  removeChoiceOpen.value = false
  restoreChoiceOpen.value = false
}

function removeAssessment() {
  const period = activeScorePeriod.value
  if (isGradeLocked(period)) {
    openGradingPeriod(period)
    return
  }
  const periodHeaders = gradeHeadersByPeriod[period]
  const index = periodHeaders.indexOf(selectedAssessment.value)
  if (index === -1 || periodHeaders.length <= 1) return
  const [label] = periodHeaders.splice(index, 1)
  allGradeStudents.forEach(student => {
    delete student.scores[period][label]
  })
  gradeStudents.forEach(student => {
    delete student.scores[period][label]
  })
  const criterionKey = assessmentCriterionKey(label)
  if (criterionKey?.startsWith('custom:') && !periodHeaders.some(header => assessmentCriterionKey(header) === criterionKey)) {
    delete gradingCriteria.value[period][criterionKey]
  }
  selectedAssessment.value = periodHeaders[Math.max(0, index - 1)]
}

function restoreAssessment() {
  if (gradeHeaders.value.includes(selectedAssessment.value)) return
  const period = activeScorePeriod.value
  if (isGradeLocked(period)) {
    openGradingPeriod(period)
    return
  }
  const originalIndex = defaultGradeHeaders.indexOf(selectedAssessment.value)
  if (originalIndex === -1) return
  gradeHeadersByPeriod[period].splice(Math.min(originalIndex, gradeHeaders.value.length), 0, selectedAssessment.value)
  const originalScores = new Map(originalGradeStudents.map(student => [student.id, student.scores || {}]))
  allGradeStudents.forEach(student => {
    student.scores[period][selectedAssessment.value] = originalScores.get(student.id)?.[period]?.[selectedAssessment.value] || ''
  })
  gradeStudents.forEach(student => {
    student.scores[period][selectedAssessment.value] = originalScores.get(student.id)?.[period]?.[selectedAssessment.value] || ''
  })
}

function toggleRemoveChoices() {
  removeChoiceOpen.value = !removeChoiceOpen.value
  addChoiceOpen.value = false
  restoreChoiceOpen.value = false
  selectedAssessment.value = ''
}

function toggleRestoreChoices() {
  restoreChoiceOpen.value = !restoreChoiceOpen.value
  addChoiceOpen.value = false
  removeChoiceOpen.value = false
  selectedAssessment.value = ''
}

function removeSelectedAssessment() {
  removeAssessment()
  removeChoiceOpen.value = false
}

function restoreSelectedAssessment() {
  restoreAssessment()
  restoreChoiceOpen.value = false
}

function restoreGrades() {
  searchStudent.value = ''
  for (const period of ['midterm', 'finals']) {
    gradeHeadersByPeriod[period].splice(0, gradeHeadersByPeriod[period].length, ...defaultGradeHeaders)
  }
  gradingCriteria.value = criteriaForHeaders(headersByPeriod.value)
  const restoredStudents = originalGradeStudents.map(student => ({
    ...student,
    scores: { midterm: { ...student.scores.midterm }, finals: { ...student.scores.finals } },
  }))
  allGradeStudents.splice(0, allGradeStudents.length, ...restoredStudents.map(student => ({
    ...student,
    scores: { midterm: { ...student.scores.midterm }, finals: { ...student.scores.finals } },
  })))
  gradeStudents.splice(0, gradeStudents.length, ...restoredStudents)
  selectedAssessment.value = defaultGradeHeaders[0]
}

watch(searchStudent, (query) => {
  const normalizedQuery = query.trim().toLowerCase()
  const visibleStudents = activeRoster.value.filter(student => !normalizedQuery || `${student.id} ${student.name}`.toLowerCase().includes(normalizedQuery))
  gradeStudents.splice(0, gradeStudents.length, ...visibleStudents)
})

let gradeClockTimer

onMounted(async () => {
  gradeClockTimer = window.setInterval(() => {
    currentClock.value = Date.now()
  }, 60000)

  try {
    const [studentsResult, schedulesResult, sheetsResult] = await Promise.allSettled([getStudents(), getGradeSchedules(), getGradeSheets()])
    if (studentsResult.status !== 'fulfilled' || schedulesResult.status !== 'fulfilled' || sheetsResult.status !== 'fulfilled') throw new Error('Unable to load grade data.')
    const students = studentsResult.value
    const schedules = schedulesResult.value
    const sheets = sheetsResult.value
    const records = students.map(student => ({ id: student.student_id, name: student.name, institute: student.institute, course: student.course, year_level: student.year_level, section: student.section, midterm: '', finals: '', finalGrade: '', gradePoint: '', remarks: '', scores: { midterm: Object.fromEntries(gradeHeadersByPeriod.midterm.map(label => [label, ''])), finals: Object.fromEntries(gradeHeadersByPeriod.finals.map(label => [label, ''])) } }))
    originalGradeStudents.splice(0, originalGradeStudents.length, ...records.map(student => ({ ...student, scores: { ...student.scores } })))
    allGradeStudents.splice(0, allGradeStudents.length, ...records)
    activeRoster.value = records
    gradeStudents.splice(0, gradeStudents.length, ...records)
    gradePeriods.value = schedules.filter(schedule => schedule.yearLevel === 'Grade Period')
    gradeSheets.value = sheets
  } catch (error) {
    window.alert(error instanceof Error ? error.message : 'Unable to load students.')
  }
})

onUnmounted(() => {
  if (gradeClockTimer) window.clearInterval(gradeClockTimer)
})

</script>

<style scoped>
*,*::before,*::after{box-sizing:border-box}.prof-root{position:fixed;inset:0;display:flex;width:100%;height:100vh;height:100dvh;overflow:hidden;background:#fff;color:#111;font-family:Arial,Helvetica,sans-serif}.prof-sidebar{width:108px;flex:0 0 108px;display:flex;flex-direction:column;align-items:center;padding:50px 0 28px;color:#fff;background:linear-gradient(#124a29 0%,#17552c 58%,#72a900 100%);z-index:20}.seal-wrap img{width:68px;height:68px;object-fit:contain}.sidebar-rule{width:70px;height:1px;margin:34px 0 56px;background:#ffffffb3}.prof-nav{display:flex;flex-direction:column;gap:24px;align-items:center;width:100%}.nav-button,.logout-button,.mobile-hamburger{border:0;background:transparent;color:inherit;cursor:pointer}.nav-button{position:relative;width:70px;height:54px;border-radius:9px}.nav-button.active{background:#54a569a6}.active-bar{position:absolute;left:-9px;top:8px;width:6px;height:38px;border-radius:0 7px 7px 0;background:#ffdf4f}.nav-button svg,.logout-button svg,.prof-profile svg,.mobile-hamburger svg{width:28px;height:28px;fill:none;stroke:currentColor;stroke-width:2;stroke-linecap:round;stroke-linejoin:round}.nav-button.active svg{color:#ffe66b}.prof-sidebar-footer{width:100%;margin-top:auto;display:flex;flex-direction:column;align-items:center}.footer-rule{margin:0 0 20px}.prof-profile{display:flex;flex-direction:column;align-items:center;gap:6px;margin-bottom:32px;font-size:12px;font-weight:800}.prof-profile svg{width:38px;height:38px}.prof-page{min-width:0;flex:1;overflow:hidden;background:#fff}.prof-header{height:80px;display:flex;align-items:center;padding:0 29px;border-bottom:1px solid #ccc;box-shadow:0 2px 4px #0002}.prof-header h1{margin:0;font-family:Georgia,serif;font-size:30px}.bell{margin-left:auto;font-size:25px}.dashboard-content,.messages-content,.grades-content{height:calc(100% - 80px);overflow:auto;padding:31px 29px}.toolbar,.grade-toolbar{display:flex;align-items:center;gap:32px;margin-bottom:26px}.grades-content{display:flex;flex-direction:column;align-items:flex-start}.search-box{width:320px;height:36px;display:flex;align-items:center;gap:10px;padding:0 12px;border:1px solid #777;border-radius:7px}.search-box svg{width:22px;height:22px;fill:none;stroke:#666;stroke-width:2}.search-box input{width:100%;border:0;outline:0;font-size:12px}.toolbar select{width:168px;height:36px;padding:0 10px;border:1px solid #777;border-radius:7px;background:#fff}.deadlines{margin-left:auto;align-self:flex-start;font-size:19px;font-weight:900}.tabs{width:392px;display:flex;margin-bottom:23px;border:1px solid #c5c5c5;border-radius:13px;overflow:hidden}.tabs button{flex:1;height:48px;border:0;background:#fff;font-size:17px;cursor:pointer}.tabs button.selected{background:#e0ffeb;color:#145b2f;border-bottom:2px solid #145b2f}.roster-panel{max-width:1010px;border:1px solid #bbb;border-radius:9px;overflow:hidden}.roster-heading{padding:15px 18px 12px;border-bottom:1px solid #bbb}.roster-heading h2{margin:0;font-size:27px}.roster-heading p{margin:2px 0 0;font-size:17px}.institute-line{height:55px;display:flex;align-items:center;gap:13px;padding:0 14px;border-bottom:1px solid #bbb;font-size:18px;font-weight:800}.institute-line span{font-size:24px}.year-line{display:flex;align-items:center;gap:16px;padding:14px 16px;background:#effff4;border-bottom:1px solid #bbb}.year-line strong{font-size:17px}.year-line button{padding:4px 18px;border:1px solid #bbb;border-radius:8px;background:#f2f2f2;cursor:pointer}.year-line button.chosen{background:#ffffbd;border-color:#b6b66e}.course-group{padding:15px 16px 27px;border-bottom:1px solid #bbb}.course-group h3{margin:0 0 17px;font-size:18px}.course-grid{display:flex;flex-wrap:wrap;gap:22px}.course-card{width:164px;min-height:68px;padding:5px;text-align:left;border:1px solid #c6c47b;border-radius:8px;background:#ffffcc;box-shadow:0 2px 3px #0003;cursor:pointer}.course-card small,.course-card strong,.course-card span{display:block}.course-card small{width:max-content;padding:1px 7px;border-radius:7px;background:#e3e3e3;font-size:9px}.course-card strong{margin:7px 0 4px;text-align:center;font-size:16px}.course-card span{font-size:8px;white-space:nowrap}.message-layout{display:grid;grid-template-columns:318px minmax(450px,1fr);gap:20px;max-width:1015px}.conversation-list,.conversation{min-height:325px;border:1px solid #ccc;border-radius:13px;box-shadow:0 2px 3px #0003;overflow:hidden}.conversation-list h2{margin:0;padding:14px 40px;border-bottom:1px solid #bbb;font-family:Georgia,serif;font-size:23px}.message-preview{display:block;width:calc(100% - 38px);margin:17px 19px;padding:10px 14px;text-align:left;border:1px solid #ddd;border-radius:8px;background:#fff;cursor:pointer}.message-preview.active{border-left:6px solid #27683f}.message-preview strong,.message-preview span,.message-preview small{display:block}.message-preview span{margin:3px 0;font-size:12px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}.message-preview small{color:#ef8e24;font-weight:800}.conversation-head{display:flex;align-items:center;gap:10px;padding:10px;border-bottom:1px solid #aaa}.avatar{width:36px;height:36px;display:grid;place-items:center;border-radius:50%;background:#264f39;color:#fff;font-weight:900}.conversation-head strong,.conversation-head small{display:block}.conversation-head select{margin-left:auto;width:108px}.chat{height:210px;padding:13px 16px}.chat-time{display:block;margin-bottom:10px;text-align:center;color:#777;font-size:10px}.chat p{max-width:315px;padding:9px 11px;border-radius:12px;font-size:14px}.incoming{border:1px solid #98af9d}.outgoing{margin:4px 0 0 auto;background:#506d5b;color:#fff}.reply{display:flex;gap:14px;padding:10px 16px}.reply input{flex:1;padding:10px;border:1px solid #aaa;border-radius:8px}.reply button,.grade-actions button{padding:8px 18px;border:1px solid #bbb;border-radius:7px;background:#fff;cursor:pointer}.reply button{border:0;background:#295d3e;color:#fff;font-weight:800}.back-link{padding:7px 13px;border:0;border-radius:14px;background:#d3f6df;color:#175d33;font-weight:800;cursor:pointer}.grade-toolbar{margin-top:56px}.grade-actions{display:flex;gap:8px}.add-button{background:#c4f0d1!important;border-color:#176337!important}.remove-button{background:#ff5c61!important;color:#fff}.grade-columns{display:grid;grid-template-columns:minmax(650px,1fr) 250px;gap:22px}.grade-table-card h2{margin:0 0 10px;font-family:Georgia,serif}.period-tabs{display:flex;width:386px;margin:0 0 10px 25px;padding:5px;border:1px solid #777;border-radius:20px}.period-tabs button{flex:1;border:0;border-radius:16px;background:#fff;padding:4px;cursor:pointer}.period-tabs button.chosen{background:#58c77e;color:#fff}.table-scroll{overflow:auto}.grade-table{width:100%;min-width:970px;border-collapse:collapse}.grade-table th,.grade-table td{border:1px solid #6b9875;text-align:center;font-size:12px}.grade-table th{height:29px;background:#cbf5d5}.grade-table td{height:30px}.grade-table th.wide{min-width:270px}.grade-table .name-cell{text-align:left;padding-left:7px}.grade-table input{width:35px;border:0;text-align:center;background:transparent}.summary-box{height:max-content;border:1px solid #333}.summary-box h2{margin:0;padding:4px;background:#b8e4c4;text-align:center;font-size:17px}.summary-box table{width:100%;border-collapse:collapse}.summary-box th,.summary-box td{padding:4px;border:1px solid #333;text-align:center;font-size:12px}.summary-box .total td{background:#b8e4c4;font-weight:900}@media(max-width:900px){.prof-sidebar{position:fixed;inset:0 auto 0 0;transform:translateX(-100%);transition:transform .2s}.prof-sidebar.open{transform:translateX(0)}.sidebar-overlay{position:fixed;inset:0;display:block;background:#0005;z-index:10}.mobile-hamburger{display:block;margin-right:12px}.toolbar{flex-wrap:wrap}.deadlines{display:none}.message-layout,.grade-columns{grid-template-columns:1fr}.summary-box{width:250px}.dashboard-content,.messages-content,.grades-content{padding:22px 16px}}@media(min-width:901px){.mobile-hamburger{display:none}}
.institute-line{width:100%;border:0;border-bottom:1px solid #bbb;background:#fff;color:#111;font:inherit;text-align:left;cursor:pointer}.institute-line.expanded{background:#fff}.modal-backdrop{position:fixed;inset:0;z-index:30;display:grid;place-items:center;background:#0005}.finals-notice{width:min(470px,calc(100% - 32px));padding:18px 34px 22px;border-radius:15px;background:repeating-linear-gradient(135deg,#284b38 0,#284b38 13px,#2d563f 13px,#2d563f 26px);box-shadow:0 7px 20px #0006;color:#fff;text-align:center}.notice-icon{width:52px;height:52px;display:grid;place-items:center;margin:0 auto 12px;border-radius:50%;background:#fff;color:#111;font-size:27px}.finals-notice h2{margin:0 0 7px;color:#fff;font-size:21px}.finals-notice p{margin:0 auto 16px;max-width:345px;font-size:14px;line-height:1.25}.finals-notice strong{display:block;width:max-content;max-width:100%;margin:0 auto 14px;padding:5px 15px;border-radius:14px;background:#fff;color:#222;font-size:15px}.finals-notice button{padding:7px 25px;border:0;border-radius:14px;background:#fff;color:#222;font-weight:800;cursor:pointer}
.grade-table-card{text-align:left}.submit-action-wrap{display:flex;justify-content:flex-end;margin-top:14px;padding-top:8px}.submit-action-btn,.submit-bottom{display:inline-flex;align-items:center;justify-content:center;padding:12px 20px;border:1px solid #2d5d3d;border-radius:8px;background:#2d7b4a;color:#fff;font-size:14px;font-weight:700;cursor:pointer;box-shadow:0 4px 10px rgba(0,0,0,.08)}.submit-action-btn:hover,.submit-bottom:hover{background:#246c3f}.save-draft{display:none!important}.submission-notice{position:fixed;top:18px;left:50%;z-index:60;transform:translateX(-50%);padding:12px 18px;border-radius:8px;background:#287442;color:#fff;font-size:14px;font-weight:700;box-shadow:0 4px 14px #0003}
.dashboard-content .toolbar > select,
.messages-content .toolbar > select {
  display: none;
}

@media (max-width: 600px) {
  .prof-root { position: fixed; width: 100%; min-width: 0; }
  .prof-page { width: 100%; min-width: 0; }
  .prof-header { height: 62px; padding: 0 16px; }
  .prof-header h1 { font-size: 22px; }
  .bell { font-size: 19px; }
  .dashboard-content,.messages-content,.grades-content { height: calc(100% - 62px); padding: 18px 12px 28px; }
  .toolbar,.grade-toolbar { align-items: stretch; flex-direction: column; gap: 10px; margin-bottom: 18px; }
  .search-box,.toolbar select { width: 100%; max-width: none; }
  .deadlines { display: none; }
  .tabs { width: 100%; margin-bottom: 16px; }
  .tabs button { height: 42px; font-size: 14px; }
  .roster-heading { padding: 13px 12px 10px; }
  .roster-heading h2 { font-size: 20px; line-height: 1.15; }
  .roster-heading p { font-size: 13px; line-height: 1.3; }
  .institute-line { height: auto; min-height: 52px; padding: 11px 12px; font-size: 14px; }
  .year-line { flex-wrap: wrap; gap: 8px; padding: 12px; }
  .year-line strong { width: 100%; font-size: 14px; }
  .year-line button { flex: 1 1 calc(50% - 8px); padding: 6px 5px; font-size: 12px; }
  .course-group { padding: 13px 12px 18px; }
  .course-group h3 { font-size: 15px; }
  .course-grid { gap: 10px; }
  .course-card { flex: 1 1 calc(50% - 10px); width: auto; min-width: 135px; }
  .course-card span { white-space: normal; line-height: 1.2; }
  .message-layout { display: flex; flex-direction: column; gap: 12px; }
  .conversation-list,.conversation { width: 100%; min-height: 0; }
  .conversation-list h2 { padding: 12px 16px; font-size: 19px; }
  .message-preview { width: calc(100% - 24px); margin: 12px; }
  .conversation-head { flex-wrap: wrap; }
  .conversation-head > div:nth-child(2) { min-width: 0; flex: 1; }
  .conversation-head select { width: 100%; margin-left: 46px; }
  .chat { height: auto; min-height: 205px; }
  .chat p { max-width: 88%; font-size: 13px; }
  .reply { flex-direction: column; gap: 8px; }
  .reply button { width: 100%; }
  .back-link { align-self: flex-start; }
  .grade-toolbar { margin-top: 20px; }
  .grade-actions { width: 100%; }
  .grade-actions > button,
  .grade-actions .choice-action > button { flex: 0 0 auto; padding: 8px 12px; font-size: 11px; }
  .grade-columns { display: flex; flex-direction: column; gap: 14px; }
  .grade-table-card { width: 100%; min-width: 0; }
  .grade-table-card h2 { font-size: 18px; }
  .period-tabs { width: 100%; margin: 0 0 10px; }
  .period-tabs button { font-size: 12px; }
  .table-scroll { width: 100%; overflow-x: auto; -webkit-overflow-scrolling: touch; }
  .grade-table { min-width: 850px; }
  .summary-box { width: 100%; max-width: 280px; }
  .submit-bottom { margin-top: 10px; font-size: 12px; }
  .save-draft { display: none !important; }
  .submit-bottom { margin-left: 5px; }
}
.grade-actions .choice-action {
  position: relative;
  display: flex;
  flex-direction: column;
  align-items: stretch;
  gap: 0;
}

.grade-actions > .add-button,
.grade-actions .choice-action > button {
  flex: 0 0 auto;
  height: 34px;
  white-space: nowrap;
}

.grade-actions .assessment-choice {
  min-width: 150px;
  height: 34px;
  padding: 0 8px;
  border: 1px solid #bbb;
  border-radius: 7px;
  background: #fff;
  color: #222;
}

.grade-actions .assessment-choices {
  position: absolute;
  top: calc(100% + 5px);
  left: 0;
  z-index: 20;
  display: grid;
  grid-template-columns: repeat(2, minmax(0, 1fr));
  gap: 4px;
  width: 210px;
  max-width: calc(100vw - 20px);
  padding: 5px;
  border: 1px solid #bbb;
  border-radius: 7px;
  background: #fff;
  box-shadow: 0 4px 10px #0002;
}

.grade-actions .assessment-choices button {
  min-width: 0;
  width: 100%;
  min-height: 34px;
  padding: 5px 6px;
  border: 1px solid #bbb;
  border-radius: 4px;
  background: #f7f7f7;
  color: #222;
  cursor: pointer;
  font-size: 12px;
  line-height: 1.2;
  white-space: normal;
  overflow-wrap: anywhere;
}

.grade-actions .assessment-choices button:disabled {
  cursor: not-allowed;
  opacity: .45;
}

.bell{position:relative}.notification-count{position:absolute;top:-8px;right:-10px;min-width:16px;padding:2px 4px;border-radius:10px;background:#d84b4b;color:#fff;font-size:10px;text-align:center}
.message-bubble{display:flex;align-items:flex-start;gap:8px;max-width:80%;margin:8px 0;padding:8px 10px;border:1px solid #8ca995;border-radius:12px;background:#fff;color:#111}.message-bubble.outgoing{margin-left:auto;background:#b8dfc2;color:#111}.message-bubble .message-content{flex:1;min-width:0}.message-bubble p{color:#111}.message-bubble img{display:block;max-width:220px;margin-bottom:6px;border-radius:8px}.message-bubble .message-action-wrap{position:relative;display:flex;align-items:center;justify-content:center}.message-bubble .message-menu-button{align-self:center;flex:0 0 auto;border:0;border-radius:6px;padding:2px 4px;background:transparent;color:#1a5636;cursor:pointer;font-size:20px;line-height:1}.message-bubble .message-menu{position:absolute;top:calc(100% + 4px);right:0;z-index:2;padding:5px;border:1px solid #b5d4c0;border-radius:8px;background:#fff;box-shadow:0 6px 18px rgba(0,0,0,.08)}.message-bubble .message-menu-item{display:block;padding:6px 10px;border:0;border-radius:6px;background:#eef8f0;color:#145b2f;font-size:12px;font-weight:700;cursor:pointer}.message-bubble small{display:block;margin-top:5px;color:#555;font-size:10px}.message-empty{color:#111;text-align:center}
.conversation{display:flex;flex-direction:column}.conversation-head,.reply{flex-shrink:0}.chat{min-height:0;overflow-y:auto;flex:1}
.message-layout .conversation{height:520px;min-height:0}.message-layout .chat{height:auto;min-height:0;flex:1;overflow-y:auto;overflow-x:hidden}
@media (max-width: 600px){.message-layout .conversation{height:520px}.message-layout .chat{height:auto;min-height:0}}
.custom-assessment-dialog{width:min(420px,calc(100% - 32px));padding:22px;border:0;border-radius:12px;background:#fff;box-shadow:0 7px 20px #0006;color:#111}
.custom-assessment-dialog h2{margin:0 0 16px;font-family:Arial,Helvetica,sans-serif;font-size:20px;font-weight:700}
.custom-assessment-dialog label{display:grid;gap:6px;margin:12px 0;font-size:13px;font-weight:700}
.custom-assessment-dialog input,.custom-assessment-dialog select{width:100%;min-height:38px;padding:8px;border:1px solid #999;border-radius:6px;background:#fff;color:#111;font:inherit}
.custom-assessment-dialog p{margin:12px 0;color:#555;font-size:12px;line-height:1.4}
.custom-assessment-dialog .custom-assessment-error{color:#b42318;font-weight:700}
.custom-assessment-actions{display:flex;justify-content:flex-end;gap:8px;margin-top:16px}
.custom-assessment-actions button{padding:8px 14px;border:1px solid #999;border-radius:6px;background:#fff;color:#111;cursor:pointer}
.custom-assessment-actions button[type="submit"]{border-color:#176337;background:#176337;color:#fff;font-weight:700}
.criteria-open-button{display:block;margin:7px auto 9px;padding:6px 11px;border:1px solid #789881;border-radius:7px;background:#f7fff9;color:#164e2d;font-size:12px;font-weight:700;cursor:pointer}
.criteria-open-button:hover{background:#e5f7eb}
.grade-actions > .criteria-open-button{display:inline-flex;align-items:center;justify-content:center;flex:0 0 auto;height:34px;margin:0;padding:8px 12px;white-space:nowrap}
.criteria-dialog{width:min(440px,calc(100% - 32px));padding:20px;border:1px solid #8b9d91;border-radius:9px;background:#fff;color:#111;box-shadow:0 7px 20px #0005}
.criteria-dialog h2{margin:0 0 13px;padding:7px;border:1px solid #699675;border-radius:5px;background:#b5e3c2;color:#111;text-align:center;font:700 17px Arial,Helvetica,sans-serif}
.criteria-period-label{margin:-4px 0 10px;padding:7px;border:1px solid #8bba98;border-radius:5px;background:#e8f7ec;color:#175d33;text-align:center;font-size:13px;font-weight:700}
.criteria-table-scroll{max-height:55vh;overflow:auto}
.criteria-table-scroll table{width:100%;border-collapse:collapse}
.criteria-table-scroll th,.criteria-table-scroll td{padding:6px;border:1px solid #555;text-align:center;font-size:12px}
.criteria-table-scroll th{background:#f0f0f0}
.criteria-table-scroll td:first-child{text-align:left}
.criteria-table-scroll .total td{background:#b5e3c2;font-weight:700}
.criteria-table-scroll input{width:82px;max-width:100%;padding:5px;border:1px solid #888;border-radius:4px;text-align:center}
.criteria-help{margin:10px 0;color:#555;font-size:11px;line-height:1.35}
.criteria-error{margin:8px 0;color:#b42318;font-size:12px;font-weight:700}
.criteria-actions{display:flex;justify-content:flex-end;gap:8px;margin-top:13px}
.criteria-actions button{min-width:76px;padding:7px 14px;border:1px solid #888;border-radius:6px;background:#f5f5f5;color:#111;font-weight:700;cursor:pointer}
.criteria-actions button:last-child,.criteria-actions button:nth-child(2){border-color:#276d42;background:#2d7b4a;color:#fff}
.criteria-actions button:disabled{cursor:wait;opacity:.65}
.grade-table input.score-over-max{border-radius:3px;background:#fff0ee;color:#b42318;outline:1px solid #b42318}
.final-grade-table .equivalent-grade-heading{font-size:9px;overflow-wrap:normal;word-break:keep-all;white-space:nowrap}
.draft-save-status{display:inline-flex;align-self:center;align-items:center;gap:6px;color:#54735d;font-size:12px}
.draft-save-icon{width:15px;height:15px;flex:none;fill:none;stroke:currentColor;stroke-linecap:round;stroke-linejoin:round;stroke-width:2}
.draft-save-icon.is-saving{animation:draft-save-rotate .9s linear infinite}
@keyframes draft-save-rotate{to{transform:rotate(360deg)}}
.submission-confirmation-backdrop{z-index:1000}
.submission-confirmation{width:min(396px,calc(100% - 32px));padding:36px 30px 29px;border:1px solid #8b9d91;border-radius:12px;background:repeating-linear-gradient(135deg,#294b38 0,#294b38 14px,#2d523d 14px,#2d523d 28px);box-shadow:0 4px 14px #0005;color:#fff;text-align:center}
.submission-confirmation h2{margin:0 0 10px;color:#fff;font-family:Arial,Helvetica,sans-serif;font-size:20px;font-weight:700;letter-spacing:0}
.submission-confirmation p{margin:0 auto 12px;font-size:12px;line-height:1.35}
.submission-confirmation .submission-confirmation-error{color:#ffe2df;font-size:11px;font-weight:700}
.submission-confirmation-actions{display:flex;justify-content:center;gap:40px;margin-top:14px}
.submission-confirmation-actions button{min-width:118px;min-height:28px;padding:6px 14px;border:0;border-radius:12px;background:#eee;color:#111;font-size:11px;font-weight:700;cursor:pointer}
.submission-confirmation-actions button:last-child{background:#a9e2bd}
.submission-confirmation-actions button:disabled{cursor:wait;opacity:.65}

.institute-line {
  border-left: 6px solid transparent;
  transition: background .18s ease, color .18s ease, border-color .18s ease;
}

.roster-panel > .institute-line:nth-of-type(1) {
  border-left-color: #e4c52f;
  color: #806d08;
}

.roster-panel > .institute-line:nth-of-type(2) {
  border-left-color: #ef8a2f;
  color: #a84f0b;
}

.roster-panel > .institute-line:nth-of-type(3) {
  border-left-color: #4aa8dc;
  color: #17658e;
}

.roster-panel > .institute-line:nth-of-type(1):hover,
.roster-panel > .institute-line:nth-of-type(1).expanded {
  background: #fffbe1;
  color: #6f5e00;
}

.roster-panel > .institute-line:nth-of-type(2):hover,
.roster-panel > .institute-line:nth-of-type(2).expanded {
  background: #fff1df;
  color: #934307;
}

.roster-panel > .institute-line:nth-of-type(3):hover,
.roster-panel > .institute-line:nth-of-type(3).expanded {
  background: #e7f5fc;
  color: #125878;
}

.institute-line.institute-ics {
  border-left-color: #ef8a2f;
  color: #a84f0b;
}

.institute-line.institute-ics.expanded,
.institute-line.institute-ics:hover {
  background: #fff1df;
  color: #934307;
}

.institute-line.institute-ibe {
  border-left-color: #e4c52f;
  color: #806d08;
}

.institute-line.institute-ibe.expanded,
.institute-line.institute-ibe:hover {
  background: #fffbe1;
  color: #6f5e00;
}

.institute-line.institute-ite {
  border-left-color: #4aa8dc;
  color: #17658e;
}

.institute-line.institute-ite.expanded,
.institute-line.institute-ite:hover {
  background: #e7f5fc;
  color: #125878;
}

.year-line {
  background: linear-gradient(90deg, #eff8f1 0%, #f8fcf9 100%);
  border-top: 1px solid #d7e7da;
  border-bottom: 1px solid #c9dfcf;
  color: #215c37;
}

.year-line strong {
  color: #18562f;
  letter-spacing: 0.04em;
}

.year-line button {
  border-color: #bfd4c4;
  background: #ffffff;
  color: #356247;
}

.year-line button:hover,
.year-line button.chosen {
  border-color: #d6b928;
  background: #fff8c9;
  color: #705f00;
}

.course-group {
  background: linear-gradient(135deg, #f8fcf8 0%, #edf7ef 100%);
  border-bottom: 1px solid #d2e5d6;
}

.course-group h3 {
  color: #145d31;
  letter-spacing: 0.06em;
}

.course-card {
  border: 1px solid #c4dcc9;
  border-top: 4px solid #4ca968;
  background: #ffffff;
  color: #174e2d;
}

.course-card:hover {
  border-color: #3b9a59;
  border-top-color: #e1bd24;
  background: #fffef1;
  box-shadow: 0 8px 18px rgba(50, 113, 67, 0.14);
  transform: translateY(-2px);
}

.course-card small {
  color: #2a7a45;
  font-weight: 800;
}

.course-card strong {
  color: #174e2d;
}

.roster-panel:has(.institute-line:nth-of-type(1).expanded) .course-group {
  background: linear-gradient(135deg, #fffdf0 0%, #fff8d6 100%);
  border-bottom-color: #eadb82;
}

.roster-panel:has(.institute-line:nth-of-type(1).expanded) .course-card {
  border-color: #e6d77b;
  border-top-color: #d8b923;
}

.roster-panel:has(.institute-line:nth-of-type(2).expanded) .course-group {
  background: linear-gradient(135deg, #fff8ef 0%, #ffecd9 100%);
  border-bottom-color: #efc49a;
}

.roster-panel:has(.institute-line:nth-of-type(2).expanded) .course-card {
  border-color: #e9bd8b;
  border-top-color: #ed8125;
}

.roster-panel:has(.institute-line:nth-of-type(3).expanded) .course-group {
  background: linear-gradient(135deg, #f1faff 0%, #e0f2fb 100%);
  border-bottom-color: #add7eb;
}

.roster-panel:has(.institute-line:nth-of-type(3).expanded) .course-card {
  border-color: #a9d5e9;
  border-top-color: #3f9fd2;
}

.prof-root {
  background: #f5f8f5;
  color: #183326;
  font-family: Georgia, 'Times New Roman', serif;
}

.prof-page {
  background: #f5f8f5;
}

.prof-header {
  background: #ffffff;
  border-bottom-color: #d9e6dc;
}

.prof-header h1 {
  color: #164b2b;
  letter-spacing: 0.04em;
}

.dashboard-content,
.messages-content,
.grades-content {
  color: #274936;
}

.toolbar,
.grade-toolbar {
  color: #365c45;
}

.professor-feature-help {
  max-width: 1000px;
  margin: -14px 0 18px;
  padding: 10px 13px;
  border-left: 3px solid #78ad85;
  border-radius: 0 6px 6px 0;
  background: #f3f8f4;
  color: #405b48;
  font-size: 14px;
  line-height: 1.55;
}

.period-feature-help {
  margin: 0 0 12px;
}

.search-box {
  border-color: #b8cfbf;
  background: #ffffff;
  color: #245638;
}

.search-box input {
  color-scheme: light;
  background-color: #ffffff;
  color: #214732;
}

.search-box input::placeholder {
  color: #7b9683;
}

.tabs {
  border-color: #bfd5c5;
  background: #eaf4ed;
}

.tabs button {
  color: #4d6d59;
}

.tabs button.selected {
  background: #ccefd7;
  color: #125d30;
}

.roster-panel,
.conversation-list,
.conversation {
  border-color: #c7dbcc;
  background: #ffffff;
  box-shadow: 0 8px 24px rgba(31, 82, 49, 0.07);
}

.roster-heading h2,
.conversation-list h2 {
  color: #185b32;
}

.roster-heading p,
.course-card span,
.message-preview small {
  color: #6c8875;
}

.institute-line {
  border-bottom-color: #d7e5da;
  color: #245538;
}

.institute-line:hover,
.course-card:hover,
.message-preview:hover {
  background: #f0f8f2;
}

.year-line,
.course-group {
  background: #f7fbf8;
  border-color: #d7e7da;
}

.year-line strong,
.course-group h3 {
  color: #26623c;
}

.course-card {
  border-color: #c5dccb;
  background: #ffffff;
  color: #1f4e32;
  box-shadow: 0 4px 12px rgba(31, 82, 49, 0.06);
}

.course-card small {
  color: #18703a;
}

.message-preview {
  border-color: #d6e5d9;
  color: #244d34;
}

.message-preview.active {
  border-left-color: #2b9a55;
  background: #ecf8ef;
}

.message-preview strong,
.conversation-head strong {
  color: #174e2d;
}

.message-preview span,
.conversation-head small,
.chat-time,
.message-empty {
  color: #708b79;
}

.conversation-head {
  background: #f2faf4;
  border-bottom-color: #d5e6d9;
}

.reply input,
.conversation-head select {
  border-color: #bcd4c2;
  color: #234d32;
}

.prof-sidebar-footer {
  position: relative;
  z-index: 25;
}

.logout-button {
  position: relative;
  z-index: 26;
  width: 58px;
  height: 48px;
  display: grid;
  place-items: center;
  pointer-events: auto;
  color: #ffffff;
}

.logout-button:hover,
.logout-button:focus-visible {
  background: rgba(255, 255, 255, 0.16);
  border-radius: 9px;
  outline: none;
}

@media (max-width: 900px) {
  .prof-root,
  .prof-page {
    min-width: 0;
  }

  .dashboard-content,
  .messages-content,
  .grades-content {
    min-width: 0;
    overscroll-behavior: contain;
  }

  .roster-panel,
  .message-layout,
  .grade-columns,
  .grade-table-card,
  .conversation-list,
  .conversation {
    min-width: 0;
    max-width: 100%;
  }

  .message-layout {
    grid-template-columns: minmax(220px, 0.8fr) minmax(0, 1.2fr);
  }

  .grade-columns {
    grid-template-columns: minmax(0, 1fr);
  }

  .grade-table-card,
  .summary-box {
    width: 100%;
  }

  .summary-box {
    max-width: none;
  }

  .grade-toolbar {
    width: 100%;
    flex-wrap: wrap;
    align-items: flex-start;
  }

  .grade-toolbar > .search-box {
    width: min(100%, 420px);
  }

  .grade-actions {
    flex-wrap: wrap;
    max-width: 100%;
  }

  .final-grade-scroll {
    overflow-x: hidden;
  }

  .final-grade-table {
    width: 100%;
    min-width: 0;
    table-layout: fixed;
  }

  .final-grade-table th,
  .final-grade-table td {
    padding: 6px 3px;
    font-size: 11px;
    line-height: 1.2;
    overflow-wrap: anywhere;
  }

  .final-grade-table th:nth-child(1),
  .final-grade-table td:nth-child(1) {
    width: 6%;
  }

  .final-grade-table th:nth-child(2),
  .final-grade-table td:nth-child(2) {
    width: 14%;
  }

  .final-grade-table th:nth-child(3),
  .final-grade-table td:nth-child(3) {
    width: 24%;
  }

  .final-grade-table th:nth-child(4),
  .final-grade-table td:nth-child(4),
  .final-grade-table th:nth-child(5),
  .final-grade-table td:nth-child(5) {
    width: 15%;
  }

  .final-grade-table th:nth-child(6),
  .final-grade-table td:nth-child(6),
  .final-grade-table th:nth-child(7),
  .final-grade-table td:nth-child(7) {
    width: 13%;
  }

  .final-grade-table th.wide {
    min-width: 0;
  }
}

@media (max-width: 600px) {
  .prof-header {
    position: sticky;
    top: 0;
    z-index: 5;
  }

  .prof-header h1 {
    min-width: 0;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
  }

  .dashboard-content,
  .messages-content,
  .grades-content {
    width: 100%;
    padding-right: 12px;
    padding-left: 12px;
  }

  .grade-toolbar {
    align-items: stretch;
  }

  .grade-toolbar > .search-box {
    width: 100%;
    min-width: 0;
  }

  .grade-actions {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 8px;
    width: 100%;
  }

  .grade-actions .choice-action,
  .grade-actions .choice-action > button,
  .grade-actions > .criteria-open-button {
    width: 100%;
    min-width: 0;
  }

  .draft-save-status {
    grid-column: 1 / -1;
    padding: 2px 3px;
  }

  .grade-actions > .criteria-open-button {
    height: 38px;
    margin: 0;
  }

  .grade-actions .choice-action > button {
    min-height: 38px;
    padding: 7px 6px;
    overflow-wrap: anywhere;
    white-space: normal;
  }

  .grade-actions .assessment-choices {
    width: min(240px, calc(100vw - 32px));
    max-height: min(55vh, 320px);
    overflow-y: auto;
  }

  .grade-columns {
    display: flex;
    flex-direction: column;
    gap: 14px;
    width: 100%;
  }

  .grade-table-card h2 {
    overflow-wrap: anywhere;
    line-height: 1.25;
  }

  .period-tabs {
    max-width: 100%;
  }

  .table-scroll {
    max-width: 100%;
    border: 1px solid #c7dbcc;
    border-radius: 6px;
    overscroll-behavior-x: contain;
  }

  .grade-table {
    min-width: 760px;
  }

  .final-grade-scroll {
    overflow-x: hidden;
  }

  .final-grade-table {
    width: 100%;
    min-width: 0;
    table-layout: fixed;
  }

  .final-grade-table th,
  .final-grade-table td {
    padding: 5px 2px;
    font-size: 10px;
    line-height: 1.2;
    overflow-wrap: anywhere;
  }

  .final-grade-table th:nth-child(1),
  .final-grade-table td:nth-child(1) {
    width: 6%;
  }

  .final-grade-table th:nth-child(2),
  .final-grade-table td:nth-child(2) {
    width: 14%;
  }

  .final-grade-table th:nth-child(3),
  .final-grade-table td:nth-child(3) {
    width: 24%;
  }

  .final-grade-table th:nth-child(4),
  .final-grade-table td:nth-child(4),
  .final-grade-table th:nth-child(5),
  .final-grade-table td:nth-child(5) {
    width: 15%;
  }

  .final-grade-table th:nth-child(6),
  .final-grade-table td:nth-child(6),
  .final-grade-table th:nth-child(7),
  .final-grade-table td:nth-child(7) {
    width: 13%;
  }

  .final-grade-table th.wide {
    min-width: 0;
  }

  .summary-box {
    max-width: none;
  }

  .summary-box th,
  .summary-box td {
    padding: 7px 5px;
    overflow-wrap: anywhere;
  }

  .modal-backdrop {
    align-items: flex-end;
    padding: 12px;
    overflow-y: auto;
  }

  .criteria-dialog,
  .custom-assessment-dialog,
  .submission-confirmation,
  .finals-notice {
    width: 100%;
    max-width: 440px;
    max-height: calc(100dvh - 24px);
    overflow-y: auto;
    margin: auto;
  }

  .criteria-table-scroll {
    max-height: 45dvh;
  }

  .criteria-table-scroll th,
  .criteria-table-scroll td {
    padding: 6px 4px;
    overflow-wrap: anywhere;
  }

  .criteria-table-scroll input {
    width: min(100%, 82px);
  }

  .criteria-actions button,
  .custom-assessment-actions button,
  .submission-confirmation-actions button {
    min-height: 40px;
  }

  .message-layout {
    width: 100%;
  }

  .message-layout .conversation {
    width: 100%;
  }

  .reply {
    padding: 10px;
  }

  .reply input {
    min-width: 0;
    font-size: 16px;
  }
}

@media (max-width: 380px) {
  .dashboard-content,
  .messages-content,
  .grades-content {
    padding-right: 8px;
    padding-left: 8px;
  }

  .grade-actions {
    grid-template-columns: minmax(0, 1fr);
  }

  .grade-actions .assessment-choices {
    width: calc(100vw - 24px);
  }

  .criteria-dialog,
  .custom-assessment-dialog {
    padding: 14px;
  }

  .criteria-actions {
    flex-wrap: wrap;
  }

  .criteria-actions button {
    flex: 1 1 90px;
  }
}

.grade-table-card {
  container-type: inline-size;
  width: 100%;
  min-width: 0;
}

.submit-action-btn {
  align-self: flex-start;
  width: auto;
  min-width: 0;
  margin: 12px 0 0;
  padding: 8px 16px;
  border-radius: 6px;
  box-shadow: none;
  font-size: 14px;
}

.submit-action-btn.is-submitted,
.submit-action-btn.is-submitted:hover {
  border-color: #c7cdd1;
  background: #e5e7eb;
  color: #6b7280;
  cursor: not-allowed;
  box-shadow: none;
}

.grades-content {
  align-items: stretch;
  padding: 24px 18px;
}

.grades-content > .back-link {
  align-self: flex-start;
  margin: 0 auto 0 0;
  min-height: 0;
  padding: 5px 10px;
  border-radius: 8px;
  font-size: 13px;
  line-height: 1.2;
}

.grade-toolbar,
.grade-columns {
  width: 100%;
  min-width: 0;
}

.grade-columns {
  grid-template-columns: minmax(0, 1fr) minmax(220px, 270px);
  gap: 16px;
}

.table-scroll {
  width: 100%;
  max-width: 100%;
  overflow-x: auto;
  -webkit-overflow-scrolling: touch;
}

.grade-table {
  width: max-content;
  min-width: 100%;
  table-layout: auto;
}

.grade-table th,
.grade-table td {
  padding: 6px 8px;
  overflow-wrap: normal;
  word-break: normal;
}

.grade-table .category-heading {
  min-width: 96px;
  white-space: nowrap;
}

.grade-table th.wide {
  min-width: 150px;
}

.grade-table input {
  box-sizing: border-box;
  width: 52px;
  min-width: 44px;
  max-width: 72px;
}

@media (max-width: 1100px) {
  .grades-content {
    padding: 18px 12px;
  }

  .grade-columns {
    grid-template-columns: minmax(0, 1fr);
  }

  .grade-table-card,
  .summary-box {
    width: 100%;
    max-width: none;
  }

  .responsive-assessment-maxes {
    display: grid;
    gap: 12px;
    width: 100%;
    margin: 0 0 12px;
  }

  .responsive-assessment-group > strong {
    display: block;
    margin: 0 0 6px;
    font-size: 13px;
  }

  .responsive-assessment-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(min(100%, 130px), 1fr));
    gap: 8px;
  }

  .responsive-assessment-max {
    display: flex;
    flex-direction: column;
    align-items: stretch;
    gap: 6px;
    min-width: 0;
    padding: 9px;
    border: 1px solid #d3e3d6;
    border-radius: 6px;
    background: #f8fff9;
    font-size: 12px;
    font-weight: 700;
  }

  .responsive-max-editor {
    display: flex;
    flex-direction: column;
    gap: 4px;
    font-size: 10px;
  }

  .responsive-max-editor input {
    box-sizing: border-box;
    width: 100%;
    min-width: 0;
    height: 34px;
    padding: 4px 7px;
    border: 1px solid #8ca697;
    border-radius: 4px;
    background: #fff;
    font-size: 14px;
    text-align: center;
  }

  .table-scroll:not(.final-grade-scroll) {
    overflow-x: auto;
  }

  .grade-table:not(.final-grade-table) {
    display: table;
    width: max-content;
    min-width: 100%;
    table-layout: auto;
  }

  .grade-table:not(.final-grade-table) thead {
    display: table-header-group;
  }

  .grade-table:not(.final-grade-table) tbody {
    display: table-row-group;
  }

  .grade-table:not(.final-grade-table) tbody tr {
    display: table-row;
  }

  .grade-table:not(.final-grade-table) tbody td {
    display: table-cell;
    min-height: 0;
    padding: 6px 8px;
    border: 1px solid #6b9875;
    border-radius: 0;
    text-align: center;
    font-size: 12px;
  }

  .grade-table:not(.final-grade-table) tbody td.assessment-score-cell {
    display: table-cell;
    min-height: 0;
    background: transparent;
    font-size: 12px;
    font-weight: 400;
  }

  .grade-table:not(.final-grade-table) tbody td.assessment-score-cell::before {
    content: none;
  }

  .grade-table:not(.final-grade-table) tbody td.assessment-score-cell input {
    width: 52px;
    height: auto;
    padding: 2px 4px;
    border: 0;
    border-radius: 0;
    background: transparent;
    text-align: center;
    font-size: 12px;
  }
}

@media (min-width: 1101px) {
  .responsive-assessment-maxes {
    display: none;
  }
}

.final-grade-actions {
  display: flex;
  flex-wrap: wrap;
  gap: 10px;
  margin-top: 12px;
}

.export-choice-dialog {
  width: min(420px, calc(100% - 32px));
  padding: 22px 22px 20px;
  border: 1px solid #8b9d91;
  border-radius: 12px;
  background: repeating-linear-gradient(135deg, #294b38 0, #294b38 14px, #2d523d 14px, #2d523d 28px);
  box-shadow: 0 4px 14px #0005;
  color: #fff;
  text-align: center;
}

.export-choice-dialog h2 {
  margin: 0 0 7px;
  color: #fff;
  font-size: 24px;
  font-weight: 700;
}

.export-choice-dialog p {
  margin: 0 auto 19px;
  color: #fff;
  font-size: 18px;
  line-height: 1.35;
}

.export-choice-actions {
  display: grid;
  gap: 8px;
}

.export-choice-cancel {
  min-height: 44px;
  padding: 8px 14px;
  border: 1px solid #2b7546;
  border-radius: 6px;
  background: #fff;
  color: #21663b;
  font-size: 17px;
  font-weight: 700;
  cursor: pointer;
}

.export-choice-actions button {
  min-height: 44px;
  padding: 8px 14px;
  border: 1px solid #2b7546;
  border-radius: 6px;
  background: #a9e2bd;
  color: #174b2c;
  font-size: 17px;
  font-weight: 700;
  cursor: pointer;
}

.export-choice-actions button:hover {
  background: #98d7ae;
}

.export-choice-cancel:hover {
  background: #eaf8ee;
}

.export-choice-cancel {
  min-width: 90px;
  margin-top: 12px;
  border-color: #bbb;
  color: #222;
}

.export-excel-btn {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  min-width: 0;
  margin: 12px 0 0;
  padding: 8px 16px;
  border: 1px solid #2b7546;
  border-radius: 6px;
  background: #fff;
  color: #21663b;
  cursor: pointer;
  box-shadow: none;
  font-size: 14px;
  font-weight: 700;
}

.export-excel-btn:hover:not(:disabled) {
  background: #eaf8ee;
}

.export-excel-btn:disabled {
  cursor: not-allowed;
  opacity: 0.6;
}

.grades-content .grade-toolbar {
  margin-top: 18px;
  margin-bottom: 16px;
}

.grades-content .grade-columns {
  display: grid;
  grid-template-columns: minmax(0, 1fr);
  gap: 0;
}

.grades-content .summary-box {
  display: none;
}

.grades-content .grade-table-card h2 {
  margin-bottom: 8px;
  color: #173e26;
  font-size: 19px;
}

.grades-content .period-tabs {
  width: min(276px, 100%);
  margin: 0 0 10px;
  padding: 4px;
  border: 1px solid #d4ddd6;
  border-radius: 20px;
  background: #fff;
}

.grades-content .period-tabs button {
  min-height: 25px;
  padding: 4px 8px;
  border-radius: 16px;
  background: #fff;
  color: #222;
  font-size: 11px;
  font-weight: 600;
}

.grades-content .period-tabs button.chosen {
  background: #55c77e;
  color: #fff;
}

.grades-content .table-scroll {
  border: 1px solid #9ab9a1;
  border-radius: 0;
  overflow-x: auto;
}

.grades-content .grade-table {
  width: max-content;
  min-width: 100%;
  border-collapse: collapse;
  table-layout: auto;
}

.grades-content .grade-table th,
.grades-content .grade-table td {
  height: 21px;
  padding: 3px 4px;
  border: 1px solid #9ab9a1;
  color: #173e26;
  font-size: 10px;
  line-height: 1.15;
  text-align: center;
}

.grades-content .grade-table thead th {
  background: #d9f6df;
  font-weight: 700;
  white-space: nowrap;
}

.grades-content .grade-table th:first-child,
.grades-content .grade-table td:first-child {
  min-width: 24px;
}

.grades-content .grade-table th:nth-child(2),
.grades-content .grade-table td:nth-child(2) {
  min-width: 66px;
}

.grades-content .grade-table th.wide,
.grades-content .grade-table td.name-cell {
  min-width: 180px;
}

.grades-content .grade-table .category-heading {
  min-width: 0;
  padding-right: 5px;
  padding-left: 5px;
}

.grades-content .grade-table .assessment-column-heading {
  min-width: 30px;
  font-size: 9px;
}

.grades-content .grade-table tbody tr:hover {
  background: #f0fbf2;
}

.grades-content .grade-table tbody td.name-cell {
  text-align: left;
}

.grades-content .grade-table tbody td:nth-child(4) {
  background: #e4f9e8;
  font-weight: 700;
}

.grades-content .grade-table.final-grade-table tbody td:nth-child(6) {
  background: #e4f9e8;
  font-weight: 700;
}

.grades-content .grade-table .assessment-score-cell input {
  width: 32px;
  min-width: 28px;
  max-width: 40px;
  padding: 0;
  color: #173e26;
  font-size: 10px;
}

.grades-content .grade-table .assessment-score-cell input:disabled {
  color: #173e26;
  opacity: 1;
}

@media (max-width: 600px) {
  .grades-content .grade-columns {
    display: grid;
  }

  .grades-content .table-scroll,
  .grades-content .final-grade-scroll {
    overflow-x: auto;
  }
}

@container (max-width: 900px) {
  .final-grade-scroll {
    overflow-x: hidden;
  }

  .final-grade-table {
    width: 100%;
    min-width: 0;
    table-layout: fixed;
  }

  .final-grade-table th,
  .final-grade-table td {
    padding: 6px 3px;
    font-size: 11px;
    line-height: 1.2;
    overflow-wrap: anywhere;
  }

  .final-grade-table th:nth-child(1),
  .final-grade-table td:nth-child(1) {
    width: 6%;
  }

  .final-grade-table th:nth-child(2),
  .final-grade-table td:nth-child(2) {
    width: 14%;
  }

  .final-grade-table th:nth-child(3),
  .final-grade-table td:nth-child(3) {
    width: 24%;
  }

  .final-grade-table th:nth-child(4),
  .final-grade-table td:nth-child(4),
  .final-grade-table th:nth-child(5),
  .final-grade-table td:nth-child(5) {
    width: 15%;
  }

  .final-grade-table th:nth-child(6),
  .final-grade-table td:nth-child(6),
  .final-grade-table th:nth-child(7),
  .final-grade-table td:nth-child(7) {
    width: 13%;
  }

  .final-grade-table th.wide {
    min-width: 0;
  }
}

.grade-table tbody td:not(.name-cell),
.grade-table input,
.summary-box td {
  font-family: "Segoe UI", Arial, sans-serif;
  font-variant-numeric: lining-nums tabular-nums;
  font-feature-settings: "lnum" 1, "tnum" 1;
}

.school-year-label {
  margin: -4px 0 10px;
  color: #355b40;
  font-size: 13px;
  font-weight: 700;
}

.prof-root,
.prof-root button,
.prof-root input,
.prof-root select {
  font-family: Arial, Helvetica, sans-serif;
}

.prof-root .prof-header h1,
.prof-root .grade-table-card h2 {
  font-family: inherit;
}
</style>
