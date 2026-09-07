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
          <span>REGISTRAR</span>
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
            <button class="tab-button active" type="button">
              <svg viewBox="0 0 24 24" aria-hidden="true">
                <path d="m9 12 2 2 4-5" />
                <circle cx="12" cy="12" r="9" />
              </svg>
              Grade Approval
            </button>
            <button class="tab-button" type="button">
              <svg viewBox="0 0 24 24" aria-hidden="true">
                <rect x="4" y="5" width="16" height="15" rx="2" />
                <path d="M8 3v4M16 3v4M4 10h16" />
              </svg>
              Release Schedule
            </button>
            <button class="tab-button" type="button">
              <svg viewBox="0 0 24 24" aria-hidden="true">
                <path d="M6 3h9l3 3v15H6Z" />
                <path d="M14 3v4h4M9 12h6M9 16h6" />
              </svg>
              Grade Repository
            </button>
          </div>

          <div class="status-grid">
            <article v-for="card in summaryCards" :key="card.label" class="status-card">
              <div>
                <h3>{{ card.label }}</h3>
                <strong>{{ card.count }}</strong>
                <p>{{ card.note }}</p>
              </div>
              <div class="status-icon" v-html="card.icon"></div>
            </article>
          </div>

          <div class="approval-table-box">
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

            <div class="table-scroll">
              <table class="approval-table">
                <thead>
                  <tr>
                    <th>CLASS</th>
                    <th>PROFESSOR</th>
                    <th>SEMESTER</th>
                    <th>STUDENTS</th>
                    <th>DATE<br />SUBMITTED</th>
                    <th>STATUS</th>
                    <th>ACTIONS</th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-for="sheet in filteredSheets" :key="sheet.code">
                    <td>
                      <strong>{{ sheet.code }}</strong>
                      <span>{{ sheet.subject }}</span>
                      <small>{{ sheet.section }}</small>
                    </td>
                    <td>{{ sheet.professor }}</td>
                    <td>{{ sheet.semester }}</td>
                    <td>{{ sheet.students }}</td>
                    <td>{{ sheet.submitted }}</td>
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
                  <tr v-if="filteredSheets.length === 0">
                    <td colspan="7" class="empty-cell">No grade sheets found.</td>
                  </tr>
                </tbody>
              </table>
            </div>
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
              <button class="approve-button" type="button">
                <svg viewBox="0 0 24 24" aria-hidden="true">
                  <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2v-8" />
                  <path d="m14 15 2 2 5-6" />
                  <path d="M14 2v6h6" />
                </svg>
                Approve Grades
              </button>
              <button class="reject-button" type="button">
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
import { computed, ref } from 'vue'
import { getGradeSheets, getReviewStudents, getInstitutes } from '../services/dataService'
import colegioLogo from '../logo/The_Colegio_de_Montalban_Seal (1).png'

defineEmits(['signout'])

const currentView = ref('institute')
const selectedInstitute = ref(null)
const search = ref('')
const statusFilter = ref('All Status')
const reviewSheet = ref(null)

const institutes = getInstitutes()

const gradeSheets = getGradeSheets()
const reviewStudents = getReviewStudents()

const summaryCards = computed(() => {
  const pending = gradeSheets.filter(s => s.status === 'Submitted').length
  const approved = gradeSheets.filter(s => s.status === 'Approved').length
  const published = gradeSheets.filter(s => s.status === 'Published').length
  return [
    {
      label: 'Pending Review',
      count: pending,
      note: 'Awaiting your approval',
      icon: `<svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="9"></circle><path d="M12 7v6l4 3"></path></svg>`,
    },
    {
      label: 'Approved',
      count: approved,
      note: 'Ready to publish',
      icon: `<svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="9"></circle><path d="m8 12 3 3 5-6"></path></svg>`,
    },
    {
      label: 'Published',
      count: published,
      note: 'Visible to students',
      icon: `<svg viewBox="0 0 24 24"><path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"></path><path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2Z"></path><circle cx="17" cy="13" r="3"></circle><path d="m21 21-2.3-2.3"></path></svg>`,
    },
  ]
})

const filteredSheets = computed(() => {
  const query = search.value.trim().toLowerCase()
  return gradeSheets.filter((sheet) => {
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

function selectInstitute(institute) {
  selectedInstitute.value = institute
  currentView.value = 'management'
}

function openReview(sheet) {
  reviewSheet.value = sheet
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
  min-width: 950px;
  border-collapse: collapse;
}

.approval-table th {
  height: 65px;
  padding: 8px 16px;
  background: #a9f3bf;
  color: #102516;
  font-size: 18px;
  line-height: 1.2;
  font-weight: 900;
  text-align: center;
}

.approval-table td {
  height: 79px;
  padding: 10px 16px;
  border-top: 1px solid #bdbdbd;
  color: #000000;
  font-size: 16px;
  text-align: center;
  vertical-align: middle;
}

.approval-table td:first-child {
  line-height: 1.1;
}

.approval-table td:first-child strong,
.approval-table td:first-child span,
.approval-table td:first-child small {
  display: block;
}

.approval-table td:first-child small {
  margin-top: 3px;
  color: #285231;
  font-size: 13px;
  font-weight: 700;
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
  font-size: 16px;
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
  font-size: 16px;
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
