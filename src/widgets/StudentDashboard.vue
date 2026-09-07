<template>
  <div class="student-root">
    <div v-if="sidebarOpen" class="sidebar-overlay" @click="toggleSidebar"></div>

    <aside class="student-sidebar" :class="{ open: sidebarOpen }">
      <div class="seal-wrap">
        <img :src="colegioLogo" alt="Colegio de Montalban" />
      </div>

      <div class="sidebar-rule"></div>

      <nav class="student-nav" aria-label="Student navigation">
        <button class="nav-item active" type="button" aria-label="Dashboard">
          <span class="active-mark"></span>
          <svg viewBox="0 0 24 24" aria-hidden="true">
            <rect x="5" y="5" width="5" height="5" />
            <rect x="14" y="5" width="5" height="5" />
            <rect x="5" y="14" width="5" height="5" />
            <rect x="14" y="14" width="5" height="5" />
          </svg>
        </button>
        <button class="nav-item" type="button" aria-label="My subjects">
          <svg viewBox="0 0 24 24" aria-hidden="true">
            <path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20" />
            <path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2Z" />
          </svg>
        </button>
      </nav>

      <div class="student-sidebar-footer">
        <div class="sidebar-rule footer-rule"></div>
        <div class="student-profile">
          <svg viewBox="0 0 24 24" aria-hidden="true">
            <circle cx="12" cy="8" r="4" />
            <path d="M4 21a8 8 0 0 1 16 0" />
          </svg>
          <span>STUDENT</span>
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

    <main class="student-page">
      <header class="student-header">
        <button class="hamburger" type="button" aria-label="Open menu" @click="toggleSidebar">
          <svg viewBox="0 0 24 24" aria-hidden="true">
            <path d="M4 6h16M4 12h16M4 18h16" />
          </svg>
        </button>
        <h1>GRADES</h1>
      </header>

      <section class="grades-body">
        <div class="intro-row">
          <div>
            <h2>My Grades</h2>
            <p>View your officially published academic grades.</p>
          </div>
        </div>

        <div class="summary-cards">
          <article class="gwa-card">
            <div>
              <p>General Weighted Average</p>
              <strong>{{ gwa }}</strong>
            </div>
            <svg viewBox="0 0 80 64" aria-hidden="true">
              <path d="M16 44 32 17l14 24 8-12 11 18" />
              <path d="M16 48h48" />
              <circle cx="32" cy="17" r="5" />
            </svg>
          </article>

          <article class="metric-card">
            <div class="metric-icon">
              <svg viewBox="0 0 24 24" aria-hidden="true">
                <path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20" />
                <path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2Z" />
              </svg>
            </div>
            <div>
              <p>Enrolled Subjects</p>
              <strong>{{ enrolledSubjects }}</strong>
            </div>
          </article>

          <article class="metric-card">
            <div class="metric-icon">
              <svg viewBox="0 0 24 24" aria-hidden="true">
                <rect x="4" y="6" width="16" height="12" rx="2" />
                <path d="M8 10h4M8 14h3M15 11.5h1M15 14.5h1" />
              </svg>
            </div>
            <div>
              <p>Student Status</p>
              <strong>{{ studentStatus }}</strong>
            </div>
          </article>

          <article class="metric-card">
            <div class="metric-icon">
              <svg viewBox="0 0 24 24" aria-hidden="true">
                <path d="M9 6h10v13H5V6h4Z" />
                <path d="M9 6V4h6v2M9 12h6M9 16h4" />
              </svg>
            </div>
            <div>
              <p>Total Units</p>
              <strong>{{ totalUnits }}</strong>
            </div>
          </article>
        </div>

        <section class="published-panel">
          <div class="panel-head">
            <h2>PUBLISHED GRADES</h2>
            <select v-model="semester" aria-label="Semester">
              <option>1st Semester 2025-2026</option>
              <option>2nd Semester 2025-2026</option>
            </select>
          </div>

          <div class="table-responsive">
            <table class="published-table">
              <thead>
                <tr>
                  <th>SUBJECT<br />CODE</th>
                  <th>DESCRIPTION</th>
                  <th>PROFESSOR</th>
                  <th>MIDTERM<br />40%</th>
                  <th>FINALS<br />60%</th>
                  <th>FINAL<br />AVERAGE</th>
                  <th>GRADE<br />POINT</th>
                  <th>REMARKS</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="p in filteredPublished" :key="p.code">
                  <td class="code" data-label="SUBJECT CODE">{{ p.code }}</td>
                  <td data-label="DESCRIPTION">{{ p.description }}</td>
                  <td data-label="PROFESSOR">{{ p.professor }}</td>
                  <td data-label="MIDTERM 40%">{{ p.midterm }}</td>
                  <td data-label="FINALS 60%">{{ p.finals }}</td>
                  <td data-label="FINAL AVERAGE">{{ p.average }}</td>
                  <td class="grade-point" data-label="GRADE POINT">{{ p.grade }}</td>
                  <td data-label="REMARKS">
                    <span :class="['pill', p.remarks.toLowerCase() === 'passed' ? 'passed' : 'failed']">{{ p.remarks }}</span>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </section>
      </section>
    </main>
  </div>
</template>

<script setup>
import { ref, computed } from 'vue'
import { getPublishedGrades } from '../services/dataService'
const sidebarOpen = ref(false)
function toggleSidebar(){ sidebarOpen.value = !sidebarOpen.value }
import colegioLogo from '../logo/The_Colegio_de_Montalban_Seal (1).png'

const searchPublished = ref('')
const semester = ref('1st Semester 2025-2026')

const gwa = ref('1.59')
const enrolledSubjects = ref(8)
const studentStatus = ref('Regular')
const totalUnits = ref(24)

const publishedGrades = getPublishedGrades()

const filteredPublished = computed(()=> publishedGrades.filter(p =>
	!searchPublished.value || p.code.toLowerCase().includes(searchPublished.value.toLowerCase()) || p.description.toLowerCase().includes(searchPublished.value.toLowerCase())
))

function signOut(){
	window.location.replace(window.location.pathname)
}
</script>

<style scoped>
*, *::before, *::after {
  box-sizing: border-box;
}

.student-root {
  position: absolute;
  inset: 0;
  display: flex;
  width: 100vw;
  height: 100vh;
  overflow: hidden;
  background: #ffffff;
  color: #0a0a0a;
  font-family: Arial, Helvetica, sans-serif;
  text-align: left;
}

.sidebar-overlay {
  display: none;
}

.student-sidebar {
  width: 70px;
  flex: 0 0 70px;
  display: flex;
  flex-direction: column;
  align-items: center;
  padding: 31px 0 19px;
  color: #ffffff;
  background: linear-gradient(180deg, #104426 0%, #145426 59%, #71a200 100%);
  z-index: 20;
}

.seal-wrap img {
  width: 46px;
  height: 46px;
  display: block;
  object-fit: contain;
}

.sidebar-rule {
  width: 48px;
  height: 1px;
  margin: 14px 0 41px;
  background: rgba(255, 255, 255, 0.72);
}

.student-nav {
  width: 100%;
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 17px;
}

.nav-item,
.logout-button,
.hamburger {
  appearance: none;
  border: 0;
  background: transparent;
  color: inherit;
  cursor: pointer;
}

.nav-item {
  position: relative;
  width: 56px;
  height: 31px;
  display: grid;
  place-items: center;
  border-radius: 7px;
}

.nav-item.active {
  background: rgba(63, 149, 82, 0.72);
}

.active-mark {
  position: absolute;
  left: -1px;
  width: 5px;
  height: 28px;
  border-radius: 0 6px 6px 0;
  background: #ffda43;
}

.nav-item svg,
.logout-button svg,
.student-profile svg,
.hamburger svg {
  width: 19px;
  height: 19px;
  fill: none;
  stroke: currentColor;
  stroke-width: 2;
  stroke-linecap: round;
  stroke-linejoin: round;
}

.nav-item.active svg {
  color: #ffe55d;
}

.student-sidebar-footer {
  width: 100%;
  margin-top: auto;
  display: flex;
  flex-direction: column;
  align-items: center;
}

.footer-rule {
  margin: 0 0 13px;
}

.student-profile {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 4px;
  margin-bottom: 27px;
  color: #ffffff;
  font-size: 11px;
  font-weight: 800;
}

.student-profile svg {
  width: 28px;
  height: 28px;
  stroke-width: 1.8;
}

.logout-button svg {
  width: 22px;
  height: 22px;
}

.student-page {
  flex: 1;
  min-width: 0;
  display: flex;
  flex-direction: column;
  background: #ffffff;
  overflow: hidden;
}

.student-header {
  height: 50px;
  flex: 0 0 50px;
  display: flex;
  align-items: center;
  gap: 12px;
  padding: 0 18px;
  border-bottom: 2px solid #cfcfcf;
  background: #ffffff;
  box-shadow: 0 1px 2px rgba(0, 0, 0, 0.12);
  z-index: 3;
}

.hamburger {
  display: none;
  color: #111111;
  padding: 0;
}

.hamburger svg {
  width: 25px;
  height: 25px;
}

.student-header h1 {
  margin: 0;
  color: #0a0a0a;
  font-size: 21px;
  line-height: 1;
  font-weight: 900;
  letter-spacing: 0;
}

.grades-body {
  flex: 1;
  overflow: auto;
  padding: 16px 17px 30px;
  background: #ffffff;
}

.intro-row h2 {
  margin: 0;
  color: #143b25;
  font-size: 19px;
  line-height: 1.05;
  font-weight: 900;
  letter-spacing: 0;
}

.intro-row p {
  margin: 4px 0 13px;
  color: #4f4f4f;
  font-size: 11px;
  font-weight: 700;
}

.summary-cards {
  display: grid;
  grid-template-columns: 204px repeat(3, minmax(145px, 1fr));
  gap: 11px;
  margin-bottom: 18px;
}

.gwa-card,
.metric-card {
  min-height: 72px;
  border: 1px solid #d0d0d0;
  border-radius: 8px;
  background: #fbfafa;
}

.gwa-card {
  position: relative;
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 10px 26px 10px 14px;
  overflow: hidden;
  border-color: transparent;
  background: linear-gradient(180deg, #62c275 0%, #39aa5d 100%);
  color: #ffffff;
}

.gwa-card p,
.metric-card p {
  margin: 0;
  color: inherit;
  font-size: 14px;
  line-height: 1.1;
  font-weight: 800;
}

.gwa-card p {
  opacity: 0.9;
}

.gwa-card strong {
  display: block;
  margin-top: 7px;
  color: #fff34a;
  font-size: 32px;
  line-height: 1;
  font-weight: 900;
  text-align: center;
}

.gwa-card svg {
  position: absolute;
  right: 19px;
  bottom: 11px;
  width: 53px;
  height: 43px;
  fill: none;
  stroke: rgba(20, 60, 34, 0.46);
  stroke-width: 6;
  stroke-linecap: round;
  stroke-linejoin: round;
}

.metric-card {
  display: flex;
  align-items: center;
  gap: 9px;
  padding: 13px 11px;
  color: #4d4d4d;
}

.metric-icon {
  width: 30px;
  height: 30px;
  flex: 0 0 30px;
  display: grid;
  place-items: center;
  border-radius: 5px;
  background: #9cd5ae;
  color: #285d3b;
}

.metric-icon svg {
  width: 17px;
  height: 17px;
  fill: none;
  stroke: currentColor;
  stroke-width: 1.8;
  stroke-linecap: round;
  stroke-linejoin: round;
}

.metric-card strong {
  display: block;
  margin-top: 5px;
  color: #164b27;
  font-size: 24px;
  line-height: 1;
  font-weight: 900;
  text-align: center;
}

.published-panel {
  overflow: hidden;
  border: 1px solid #1c5a34;
  border-radius: 9px 9px 0 0;
  background: #ffffff;
}

.panel-head {
  min-height: 39px;
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 12px;
  padding: 6px 13px 6px 12px;
  border-bottom: 1px solid #6e6e6e;
}

.panel-head h2 {
  margin: 0;
  color: #174b2a;
  font-size: 20px;
  line-height: 1;
  font-weight: 900;
  letter-spacing: 0;
}

.panel-head select {
  width: 131px;
  height: 28px;
  padding: 0 8px;
  border: 1px solid #5e5e5e;
  border-radius: 5px;
  background: #ffffff;
  color: #000000;
  font-size: 8px;
  font-weight: 800;
}

.table-responsive {
  width: 100%;
  overflow: auto;
  max-height: calc(100vh - 249px);
}

.published-table {
  width: 100%;
  min-width: 680px;
  border-collapse: collapse;
  table-layout: fixed;
}

.published-table th {
  height: 38px;
  padding: 5px 8px;
  background: #a9edbd;
  color: #114b27;
  font-size: 11px;
  line-height: 1.03;
  font-weight: 900;
  text-align: center;
}

.published-table th:nth-child(1) {
  width: 80px;
}

.published-table th:nth-child(2) {
  width: 120px;
}

.published-table th:nth-child(3) {
  width: 128px;
}

.published-table th:nth-child(8) {
  width: 82px;
}

.published-table td {
  height: 28px;
  padding: 5px 8px;
  border-bottom: 1px solid #8c8c8c;
  color: #111111;
  font-size: 10px;
  line-height: 1.05;
  text-align: center;
  vertical-align: middle;
}

.published-table td:nth-child(2),
.published-table td:nth-child(3) {
  text-align: left;
}

.published-table .code,
.grade-point {
  font-weight: 900;
}

.pill {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  min-width: 45px;
  min-height: 14px;
  padding: 2px 7px;
  border-radius: 999px;
  font-size: 8px;
  line-height: 1;
  font-weight: 900;
}

.pill.passed {
  background: #c2eccf;
  color: #1a6e38;
}

.pill.failed {
  background: #f8c8c8;
  color: #a32626;
}

@media (max-width: 860px) {
  .summary-cards {
    grid-template-columns: 1fr 1fr;
  }

  .gwa-card {
    grid-column: 1 / -1;
  }
}

@media (max-width: 720px) {
  .student-sidebar {
    position: fixed;
    inset: 0 auto 0 0;
    width: 70px;
    transform: translateX(-100%);
    transition: transform 0.2s ease;
  }

  .student-sidebar.open {
    transform: translateX(0);
  }

  .sidebar-overlay {
    display: block;
    position: fixed;
    inset: 0;
    background: rgba(0, 0, 0, 0.42);
    z-index: 15;
  }

  .hamburger {
    display: grid;
  }

  .student-header {
    padding-inline: 14px;
  }

  .grades-body {
    padding: 14px;
  }

  .summary-cards {
    grid-template-columns: 1fr;
  }

  .panel-head {
    align-items: flex-start;
    flex-direction: column;
  }

  .panel-head select {
    width: 100%;
    font-size: 11px;
  }
}
</style>
