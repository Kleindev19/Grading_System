<template>
  <div class="prof-root">
    <div v-if="!sidebarCollapsed && isMobile" class="sidebar-overlay" @click="sidebarCollapsed = true"></div>

    <aside class="prof-sidebar" :class="{ open: !sidebarCollapsed && isMobile }">
      <div class="seal-wrap">
        <img :src="colegioLogo" alt="Colegio de Montalban" />
      </div>

      <div class="sidebar-rule"></div>

      <nav class="prof-nav" aria-label="Professor navigation">
        <button class="nav-button active" type="button" aria-label="Dashboard" @click="activeNav = 'dashboard'">
          <span class="active-bar"></span>
          <svg viewBox="0 0 24 24" aria-hidden="true">
            <rect x="4" y="4" width="5" height="5" />
            <rect x="15" y="4" width="5" height="5" />
            <rect x="4" y="15" width="5" height="5" />
            <rect x="15" y="15" width="5" height="5" />
          </svg>
        </button>
        <button class="nav-button" type="button" aria-label="My subjects" @click="activeNav = 'subjects'">
          <svg viewBox="0 0 24 24" aria-hidden="true">
            <path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20" />
            <path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2Z" />
          </svg>
        </button>
      </nav>

      <div class="prof-sidebar-footer">
        <div class="sidebar-rule footer-rule"></div>
        <div class="prof-profile">
          <svg viewBox="0 0 24 24" aria-hidden="true">
            <circle cx="12" cy="8" r="4" />
            <path d="M4 21a8 8 0 0 1 16 0" />
          </svg>
          <span>PROFESSOR</span>
        </div>
        <button class="logout-button" type="button" aria-label="Sign out" @click="signOut">
          <svg viewBox="0 0 24 24" aria-hidden="true">
            <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4" />
            <path d="m16 17 5-5-5-5" />
            <path d="M21 12H9" />
          </svg>
        </button>
      </div>
    </aside>

    <main class="prof-page">
      <header class="prof-header">
        <button v-if="isMobile" class="mobile-hamburger" type="button" aria-label="Open menu" @click="sidebarCollapsed = false">
          <svg viewBox="0 0 24 24" aria-hidden="true">
            <path d="M4 6h16M4 12h16M4 18h16" />
          </svg>
        </button>
        <h1>GRADE ENTRY</h1>
      </header>

      <div class="prof-content">
        <aside class="subject-column">
          <h2>Select Subject</h2>

          <div class="subject-cards">
            <button
              v-for="s in filteredSubjects"
              :key="s.code"
              class="subject-card"
              :class="{ active: selected && selected.code === s.code }"
              type="button"
              @click="selectSubject(s)"
            >
              <div class="subject-top">
                <strong>{{ s.code }}</strong>
                <span :class="['badge', s.status === 'APPROVED' ? 'approved' : 'pending']">{{ s.status }}</span>
              </div>
              <p class="subject-name">{{ s.name }}</p>
              <p v-if="s.subtitle" class="subject-subtitle">{{ s.subtitle }}</p>
              <p class="subject-meta">{{ s.section }} <span></span> {{ s.students }} Students</p>
              <p class="subject-dept">{{ s.dept }}</p>
            </button>
          </div>
        </aside>

        <section class="entry-column">
          <label class="student-search">
            <svg viewBox="0 0 24 24" aria-hidden="true">
              <circle cx="11" cy="11" r="7" />
              <path d="m21 21-5-5" />
            </svg>
            <input v-model="searchStudent" placeholder="Search student name or ID...." />
          </label>

          <div v-if="!selected" class="empty-card">
            <div class="empty-icon">
              <svg viewBox="0 0 24 24" aria-hidden="true">
                <path d="M9 3h6l1 2h3v16H5V5h3Z" />
                <path d="M8 10h8M8 14h8M8 18h5" />
              </svg>
            </div>
            <h3>Select a Subject</h3>
            <p>Choose a subject from the list to view your students and enter their grades.</p>
          </div>

          <div v-else class="grade-sheet">
            <div class="sheet-top">
              <div>
                <h3>{{ selected.code }} - {{ selected.name }}</h3>
                <p>Section: {{ selected.section }}</p>
              </div>
              <strong>GRADE SHEET REVIEW</strong>
            </div>

            <div class="action-strip">
              <div class="dropdown-wrap">
                <button class="mini-action primary" type="button" @click="toggleAddDropdown">+ Add Assessment</button>
                <div v-if="showAddDropdown" class="dropdown-panel">
                  <label>
                    Label
                    <input v-model="newAssLabel" placeholder="e.g. Q3" />
                  </label>
                  <label>
                    Max Score
                    <input v-model.number="newAssMax" type="number" min="1" />
                  </label>
                  <div class="dropdown-actions">
                    <button type="button" @click="addAssessment">Add</button>
                    <button type="button" @click="showAddDropdown = false">Cancel</button>
                  </div>
                </div>
              </div>
              <button class="mini-action" type="button" @click="removeSelected">Remove</button>
              <button class="mini-action" type="button" @click="restoreAll">Restore</button>
              <button class="mini-action submit" :class="{ done: submitSuccess }" type="button" @click="handleSubmit">
                {{ submitSuccess ? 'Submitted!' : 'Submit' }}
              </button>
            </div>

            <div class="table-wrap">
              <table class="sheet-table">
                <thead>
                  <tr>
                    <th rowspan="2" class="student-id">Student ID</th>
                    <th rowspan="2" class="student-name">Student Name</th>
                    <th rowspan="2">Midterm<br />Percentage</th>
                    <th
                      v-for="a in visibleAssessments"
                      :key="a.key"
                      class="assessment-head"
                      :class="{ selected: selectedCols.includes(a.key) }"
                      @click="toggleColSelect(a.key)"
                    >
                      {{ a.label }}
                    </th>
                  </tr>
                  <tr>
                    <td
                      v-for="a in visibleAssessments"
                      :key="a.key"
                      class="max-cell"
                      :class="{ selected: selectedCols.includes(a.key) }"
                    >
                      {{ a.max }}
                    </td>
                  </tr>
                </thead>
                <tbody>
                  <tr v-if="filteredStudents.length === 0">
                    <td :colspan="3 + visibleAssessments.length" class="empty-row">No students found.</td>
                  </tr>
                  <tr v-for="(stu, si) in filteredStudents" :key="stu.id">
                    <td>{{ stu.id }}</td>
                    <td>{{ stu.name }}</td>
                    <td>{{ calcMidterm(stu.scores) }}</td>
                    <td v-for="a in visibleAssessments" :key="a.key">
                      <input
                        type="number"
                        :value="stu.scores[a.key] ?? ''"
                        :max="a.max"
                        min="0"
                        @input="updateScore(si, a.key, $event.target.value, a.max)"
                      />
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>
        </section>

        <aside class="midterm-column">
          <div class="midterm-box">
            <h2>MIDTERM</h2>
            <table>
              <thead>
                <tr>
                  <th>Items</th>
                  <th>Percentage</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="r in midtermTable" :key="r.item">
                  <td>{{ r.item }}</td>
                  <td>{{ selected ? r.percentage : '-' }}</td>
                </tr>
                <tr class="total">
                  <td>TOTAL</td>
                  <td>{{ selected ? '100%' : '-' }}</td>
                </tr>
              </tbody>
            </table>
          </div>
        </aside>
      </div>
    </main>
  </div>
</template>

<script setup>
import { ref, computed, reactive, onMounted, onUnmounted } from 'vue'
import colegioLogo from '../logo/The_Colegio_de_Montalban_Seal (1).png'

const isMobile = ref(window.innerWidth <= 768)
const handleResize = () => { isMobile.value = window.innerWidth <= 768 }
onMounted(() => {
  window.addEventListener('resize', handleResize)
  if (isMobile.value) sidebarCollapsed.value = true
})
onUnmounted(() => window.removeEventListener('resize', handleResize))

const sidebarCollapsed = ref(false)
const activeNav = ref('dashboard')

const subjects = [
  { code: 'ITRSRC1',  status: 'APPROVED', name: 'Capstone Project 1',   section: 'BSIT-3A', students: 60, dept: 'Institute of Computing Science' },
  { code: 'ITQUANM',  status: 'PENDING',  name: 'Quantitative Methods', section: 'BSIT-3B', students: 55, dept: 'Institute of Computing Science' },
  { code: 'ITELEC4',  status: 'APPROVED', name: 'IT Elective 4', subtitle: '(Platform Technologies)', section: 'BSIT-3A', students: 60, dept: 'Institute of Computing Science' },
  { code: 'ITELEC4B', status: 'APPROVED', name: 'IT Elective 4', subtitle: '(Platform Technologies)', section: 'BSIT-3B', students: 58, dept: 'Institute of Computing Science' },
]

const midtermTable = [
  { item: 'Quiz',       percentage: '15%' },
  { item: 'Activity',   percentage: '35%' },
  { item: 'Recitation', percentage: '10%' },
  { item: 'Major Exam', percentage: '40%' },
]

const DEFAULT_ASSESSMENTS = [
  { key: 'Q1',   label: 'Q1',   max: 20 },
  { key: 'Q2',   label: 'Q2',   max: 20 },
  { key: 'Q2b',  label: 'Q2',   max: 20 },
  { key: 'Act1', label: 'Act1', max: 50 },
  { key: 'Act2', label: 'Act2', max: 50 },
]

const allStudents = reactive({
  ITRSRC1:  [{ id: '23-00000', name: 'Dela Cruz, Juan A.',  scores: { Q1: 18, Q2: 20, Q2b: 18, Act1: 45, Act2: 40 } }],
  ITQUANM:  [{ id: '23-00001', name: 'Santos, Maria B.',    scores: { Q1: 15, Q2: 17, Q2b: 16, Act1: 40, Act2: 38 } }],
  ITELEC4:  [{ id: '23-00002', name: 'Reyes, Carlo M.',     scores: { Q1: 20, Q2: 19, Q2b: 20, Act1: 48, Act2: 47 } }],
  ITELEC4B: [{ id: '23-00003', name: 'Flores, Ana C.',      scores: { Q1: 17, Q2: 18, Q2b: 17, Act1: 42, Act2: 44 } }],
})

const allAssessments = reactive(
  Object.fromEntries(subjects.map(s => [s.code, DEFAULT_ASSESSMENTS.map(a => ({ ...a }))]))
)

const selected        = ref(null)
const searchSubject   = ref('')
const searchStudent   = ref('')
const showAddDropdown = ref(false)
const newAssLabel     = ref('')
const newAssMax       = ref(20)
const removedCols     = ref([])
const selectedCols    = ref([])
const submitSuccess   = ref(false)

const filteredSubjects = computed(() =>
  subjects.filter(s =>
    s.code.toLowerCase().includes(searchSubject.value.toLowerCase()) ||
    s.name.toLowerCase().includes(searchSubject.value.toLowerCase())
  )
)

const currentStudents = computed(() =>
  selected.value ? (allStudents[selected.value.code] || []) : []
)

const filteredStudents = computed(() =>
  currentStudents.value.filter(s =>
    s.name.toLowerCase().includes(searchStudent.value.toLowerCase()) ||
    s.id.includes(searchStudent.value)
  )
)

const currentAssessments = computed(() =>
  selected.value ? (allAssessments[selected.value.code] || []) : []
)

const visibleAssessments = computed(() =>
  currentAssessments.value.filter(a => !removedCols.value.includes(a.key))
)

function selectSubject(subj) {
  selected.value = subj
  removedCols.value  = []
  selectedCols.value = []
  showAddDropdown.value = false
  if (!allStudents[subj.code])    allStudents[subj.code]    = []
  if (!allAssessments[subj.code]) allAssessments[subj.code] = DEFAULT_ASSESSMENTS.map(a => ({ ...a }))
}

function calcMidterm(scores) {
  const quizPct = (((scores.Q1 || 0) + (scores.Q2 || 0) + (scores.Q2b || 0)) / 60) * 15
  const actPct  = (((scores.Act1 || 0) + (scores.Act2 || 0)) / 100) * 35
  return Math.round(quizPct + actPct)
}

function updateScore(studentIndex, key, val, max) {
  if (!selected.value) return
  const students = allStudents[selected.value.code]
  const stu      = filteredStudents.value[studentIndex]
  const realIdx  = students.findIndex(s => s.id === stu.id)
  if (realIdx === -1) return
  students[realIdx].scores[key] = val === '' ? '' : Math.min(Number(val), max)
}

function toggleAddDropdown() { showAddDropdown.value = !showAddDropdown.value }

function addAssessment() {
  if (!newAssLabel.value.trim() || !selected.value) return
  const key = newAssLabel.value.trim() + '_' + Date.now()
  allAssessments[selected.value.code].push({ key, label: newAssLabel.value.trim(), max: newAssMax.value })
  allStudents[selected.value.code].forEach(s => { s.scores[key] = '' })
  newAssLabel.value = ''
  newAssMax.value   = 20
  showAddDropdown.value = false
}

function toggleColSelect(key) {
  const idx = selectedCols.value.indexOf(key)
  if (idx === -1) selectedCols.value.push(key)
  else selectedCols.value.splice(idx, 1)
}

function removeSelected() {
  if (selectedCols.value.length === 0) { alert('Click a column header first to select it.'); return }
  removedCols.value.push(...selectedCols.value)
  selectedCols.value = []
}

function restoreAll() { removedCols.value = []; selectedCols.value = [] }

function handleSubmit() {
  submitSuccess.value = true
  setTimeout(() => { submitSuccess.value = false }, 2500)
}

function signOut() { window.location.reload() }
</script>

<style scoped>
*, *::before, *::after {
  box-sizing: border-box;
}

.prof-root {
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

.sidebar-overlay {
  display: none;
}

.prof-sidebar {
  width: 111px;
  flex: 0 0 111px;
  display: flex;
  flex-direction: column;
  align-items: center;
  padding: 52px 0 30px;
  color: #ffffff;
  background: linear-gradient(180deg, #124a29 0%, #124b27 58%, #72a900 100%);
  z-index: 20;
}

.seal-wrap img {
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

.prof-nav {
  width: 100%;
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 24px;
}

.nav-button,
.logout-button,
.mobile-hamburger {
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
.prof-profile svg,
.mobile-hamburger svg {
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

.prof-sidebar-footer {
  width: 100%;
  margin-top: auto;
  display: flex;
  flex-direction: column;
  align-items: center;
}

.footer-rule {
  margin: 0 0 20px;
}

.prof-profile {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 6px;
  margin-bottom: 36px;
  color: #ffffff;
  font-size: 13px;
  font-weight: 800;
}

.prof-profile svg {
  width: 38px;
  height: 38px;
  stroke-width: 1.8;
}

.logout-button svg {
  width: 31px;
  height: 31px;
}

.prof-page {
  flex: 1;
  min-width: 0;
  display: flex;
  flex-direction: column;
  overflow: hidden;
  background: #ffffff;
}

.prof-header {
  height: 80px;
  flex: 0 0 80px;
  display: flex;
  align-items: center;
  gap: 14px;
  padding: 0 31px;
  border-bottom: 1px solid #dadada;
  background: #ffffff;
  box-shadow: 0 2px 5px rgba(0, 0, 0, 0.2);
  z-index: 3;
}

.prof-header h1 {
  margin: 0;
  color: #000000;
  font-size: 31px;
  line-height: 1;
  font-weight: 900;
  letter-spacing: 0;
}

.mobile-hamburger {
  display: none;
  color: #111111;
  padding: 0;
}

.prof-content {
  flex: 1;
  display: grid;
  grid-template-columns: 235px minmax(520px, 1fr) 278px;
  gap: 31px;
  overflow: hidden;
  padding: 34px 30px 30px;
  background: #fbfbfb;
}

.subject-column {
  min-width: 0;
  overflow-y: auto;
  padding-right: 3px;
}

.subject-column h2 {
  margin: 0 0 27px 12px;
  color: #113d24;
  font-size: 31px;
  line-height: 1;
  font-weight: 900;
  letter-spacing: 0;
}

.subject-cards {
  display: flex;
  flex-direction: column;
  gap: 29px;
}

.subject-card {
  width: 233px;
  min-height: 166px;
  display: block;
  padding: 15px 12px 13px;
  border: 1px solid #bfbfbf;
  border-radius: 14px;
  background: #ffffff;
  box-shadow: 0 4px 2px rgba(0, 0, 0, 0.2);
  color: #000000;
  cursor: pointer;
  text-align: left;
}

.subject-card.active {
  border-color: #9f9f9f;
  box-shadow: 0 4px 2px rgba(0, 0, 0, 0.2);
}

.subject-top {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  gap: 8px;
}

.subject-top strong {
  color: #000000;
  font-size: 25px;
  line-height: 1;
  font-weight: 900;
}

.badge {
  display: inline-flex;
  align-items: center;
  min-height: 23px;
  padding: 4px 8px;
  border-radius: 999px;
  font-size: 12px;
  line-height: 1;
  font-weight: 800;
}

.badge.approved {
  background: #a9e4b9;
  color: #176637;
}

.badge.pending {
  background: #fbfb83;
  color: #7b6a00;
  border: 1px solid #d9db58;
}

.subject-name {
  margin: 16px 0 0;
  color: #606060;
  font-size: 19px;
  line-height: 1.05;
  font-weight: 700;
}

.subject-subtitle {
  margin: 3px 0 0;
  color: #606060;
  font-size: 12px;
  line-height: 1.1;
}

.subject-meta {
  margin: 17px 0 0;
  color: #a2a2a2;
  font-size: 20px;
  line-height: 1;
  font-weight: 700;
}

.subject-meta span {
  display: inline-block;
  width: 2px;
  height: 30px;
  margin: 0 7px -9px;
  background: #b6b6b6;
}

.subject-dept {
  display: inline-flex;
  align-items: center;
  min-height: 22px;
  margin: 17px 0 0;
  padding: 2px 11px;
  border-radius: 4px;
  background: #e9d8a8;
  color: #323232;
  font-size: 14px;
  line-height: 1;
}

.entry-column {
  min-width: 0;
  overflow-y: auto;
  padding-top: 162px;
}

.student-search {
  width: 346px;
  height: 38px;
  display: flex;
  align-items: center;
  gap: 11px;
  margin: 0 0 23px;
  padding: 0 15px;
  border: 1px solid #555555;
  border-radius: 7px;
  background: #ffffff;
}

.student-search svg {
  width: 26px;
  height: 26px;
  flex: 0 0 auto;
  fill: none;
  stroke: #666666;
  stroke-width: 2;
  stroke-linecap: round;
  stroke-linejoin: round;
}

.student-search input {
  width: 100%;
  border: 0;
  outline: 0;
  background: transparent;
  color: #555555;
  font-size: 13px;
}

.empty-card {
  min-height: 378px;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  padding: 30px;
  border: 1px solid #dedede;
  border-radius: 10px;
  background: #ffffff;
  text-align: center;
}

.empty-icon {
  width: 58px;
  height: 58px;
  display: grid;
  place-items: center;
  margin-bottom: 16px;
  border-radius: 50%;
  background: #e0f0e3;
  color: #184d2b;
}

.empty-icon svg {
  width: 28px;
  height: 28px;
  fill: none;
  stroke: currentColor;
  stroke-width: 1.8;
  stroke-linecap: round;
  stroke-linejoin: round;
}

.empty-card h3 {
  margin: 0 0 13px;
  color: #114b27;
  font-size: 15px;
  font-weight: 900;
}

.empty-card p {
  max-width: 360px;
  margin: 0;
  color: #28364c;
  font-size: 13px;
  line-height: 1.45;
}

.grade-sheet {
  min-height: 451px;
  overflow: hidden;
  border: 1px solid #9c9c9c;
  border-radius: 13px;
  background: #ffffff;
  box-shadow: 0 3px 2px rgba(0, 0, 0, 0.25);
}

.sheet-top {
  min-height: 82px;
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 20px;
  padding: 16px 22px 12px 20px;
}

.sheet-top h3 {
  margin: 0 0 5px;
  color: #000000;
  font-size: 25px;
  line-height: 1;
  font-weight: 900;
}

.sheet-top p {
  margin: 0;
  color: #666666;
  font-size: 18px;
  line-height: 1;
}

.sheet-top strong {
  color: #000000;
  font-size: 25px;
  line-height: 1;
  font-weight: 900;
  white-space: nowrap;
}

.action-strip {
  display: flex;
  align-items: center;
  gap: 8px;
  padding: 0 20px 8px;
}

.mini-action {
  min-height: 28px;
  padding: 5px 10px;
  border: 1px solid #d5d5d5;
  border-radius: 6px;
  background: #ffffff;
  color: #333333;
  font-size: 12px;
  cursor: pointer;
}

.mini-action.primary,
.mini-action.submit {
  border-color: #16713c;
  background: #16713c;
  color: #ffffff;
  font-weight: 800;
}

.mini-action.submit {
  margin-left: auto;
}

.mini-action.done {
  background: #4caf50;
}

.dropdown-wrap {
  position: relative;
}

.dropdown-panel {
  position: absolute;
  top: calc(100% + 6px);
  left: 0;
  z-index: 10;
  width: 210px;
  padding: 14px;
  border: 1px solid #d0d0d0;
  border-radius: 8px;
  background: #ffffff;
  box-shadow: 0 8px 18px rgba(0, 0, 0, 0.14);
}

.dropdown-panel label {
  display: block;
  margin-bottom: 8px;
  color: #333333;
  font-size: 12px;
  font-weight: 700;
}

.dropdown-panel input {
  display: block;
  width: 100%;
  margin-top: 4px;
  padding: 6px 8px;
  border: 1px solid #cfcfcf;
  border-radius: 5px;
}

.dropdown-actions {
  display: flex;
  gap: 8px;
}

.dropdown-actions button {
  flex: 1;
  min-height: 28px;
  border: 1px solid #d0d0d0;
  border-radius: 5px;
  background: #ffffff;
  cursor: pointer;
}

.table-wrap {
  overflow-x: auto;
}

.sheet-table {
  width: 100%;
  min-width: 720px;
  border-collapse: collapse;
  table-layout: fixed;
}

.sheet-table th,
.sheet-table td {
  border-top: 1px solid #92ca9f;
  border-right: 1px solid #78b989;
  color: #000000;
  text-align: center;
}

.sheet-table th {
  height: 32px;
  padding: 8px;
  background: #a9f3bf;
  font-size: 18px;
  line-height: 1.05;
  font-weight: 500;
}

.sheet-table th.student-id {
  width: 140px;
}

.sheet-table th.student-name {
  width: 194px;
}

.sheet-table .assessment-head {
  cursor: pointer;
}

.sheet-table .assessment-head.selected,
.sheet-table .max-cell.selected {
  background: #f8efaa;
}

.sheet-table .max-cell {
  height: 32px;
  padding: 4px;
  background: #a9f3bf;
  font-size: 18px;
}

.sheet-table tbody td {
  height: 61px;
  padding: 8px;
  border-top: 0;
  border-right: 0;
  background: #ffffff;
  font-size: 19px;
}

.sheet-table input {
  width: 54px;
  height: 31px;
  border: 0;
  background: transparent;
  color: #000000;
  font: inherit;
  text-align: center;
  outline: 0;
}

.empty-row {
  height: 80px !important;
  color: #777777;
}

.midterm-column {
  overflow-y: auto;
}

.midterm-box {
  width: 278px;
  overflow: hidden;
  border: 1px solid #000000;
  border-radius: 3px;
  background: #ffffff;
}

.midterm-box h2 {
  height: 32px;
  display: grid;
  place-items: center;
  margin: 0;
  background: #b6dfc3;
  color: #000000;
  font-size: 19px;
  line-height: 1;
  font-weight: 900;
}

.midterm-box table {
  width: 100%;
  border-collapse: collapse;
}

.midterm-box th,
.midterm-box td {
  height: 24px;
  padding: 3px 8px;
  border-top: 1px solid #000000;
  border-right: 1px solid #000000;
  color: #000000;
  font-size: 14px;
  line-height: 1;
  text-align: center;
}

.midterm-box th:last-child,
.midterm-box td:last-child {
  border-right: 0;
}

.midterm-box th {
  font-weight: 900;
}

.midterm-box .total td {
  background: #b6dfc3;
  font-weight: 900;
}

@media (max-width: 1120px) {
  .prof-content {
    grid-template-columns: 235px minmax(420px, 1fr);
  }

  .midterm-column {
    grid-column: 1 / -1;
  }

  .entry-column {
    padding-top: 90px;
  }
}

@media (max-width: 768px) {
  .sidebar-overlay {
    display: block;
    position: fixed;
    inset: 0;
    z-index: 15;
    background: rgba(0, 0, 0, 0.45);
  }

  .prof-sidebar {
    position: fixed;
    inset: 0 auto 0 0;
    transform: translateX(-100%);
    transition: transform 0.22s ease;
  }

  .prof-sidebar.open {
    transform: translateX(0);
  }

  .mobile-hamburger {
    display: grid;
    color: #000000;
  }

  .prof-content {
    grid-template-columns: 1fr;
    gap: 20px;
    overflow-y: auto;
    padding: 22px 16px;
  }

  .subject-card,
  .midterm-box,
  .student-search {
    width: 100%;
  }

  .entry-column {
    padding-top: 0;
  }

  .sheet-top {
    align-items: flex-start;
    flex-direction: column;
  }

  .action-strip {
    flex-wrap: wrap;
  }

  .mini-action.submit {
    margin-left: 0;
  }
}
</style>
