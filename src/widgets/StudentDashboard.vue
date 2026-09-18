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
        <button class="nav-item" type="button" aria-label="My subjects" @click="showSubjects">
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
          <span>{{ props.user?.name || 'STUDENT' }}</span>
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

        <section ref="publishedPanel" class="published-panel">
          <div class="grades-watermark" aria-hidden="true">COLEGIO DE MONTALBAN</div>
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
                  <th>MESSAGE</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="p in filteredPublished" :key="p.id || p.code">
                  <td class="code" data-label="SUBJECT CODE">{{ p.code }}</td>
                  <td data-label="DESCRIPTION">{{ p.subject }}</td>
                  <td data-label="PROFESSOR">{{ p.professor }}</td>
                  <td data-label="MIDTERM 40%">{{ p.student_records?.[0]?.midterm || '-' }}</td>
                  <td data-label="FINALS 60%">{{ p.student_records?.[0]?.finals || '-' }}</td>
                  <td data-label="FINAL AVERAGE">{{ p.student_records?.[0]?.finalGrade || '-' }}</td>
                  <td class="grade-point" data-label="GRADE POINT">{{ p.student_records?.[0]?.gradePoint || '-' }}</td>
                  <td data-label="REMARKS">
                    <span class="pill passed">{{ p.student_records?.[0]?.remarks || 'Published' }}</span>
                  </td>
                  <td data-label="MESSAGE"><button class="message-icon-button" type="button" :disabled="!p.professor_id" aria-label="Message professor" @click="openMessage(p)">&#128172;</button></td>
                </tr>
              </tbody>
            </table>
          </div>
        </section>
      </section>
      <div v-if="messageSheet" class="message-backdrop" @click.self="messageSheet = null">
        <section class="student-message" role="dialog" aria-modal="true" aria-label="Message professor">
          <header class="message-header">
            <div class="message-user">
              <div class="message-avatar" aria-hidden="true">
                <svg viewBox="0 0 24 24"><circle cx="12" cy="8" r="4" /><path d="M4 21a8 8 0 0 1 16 0" /></svg>
              </div>
              <div class="message-user-meta">
                <strong>{{ messageSheet.professor || 'Professor' }}</strong>
                <small>{{ messageSheet.code || 'Course' }} - {{ messageSheet.subject || 'Subject' }}</small>
              </div>
            </div>
            <button type="button" aria-label="Close" @click="messageSheet = null">&#10005;</button>
          </header>

          <div ref="messageHistory" class="message-history">
            <div class="message-divider">MON AT 12:15 PM</div>
            <article v-for="message in subjectMessages" :key="message.id" :class="{ mine: message.senderId === props.user?.id || message.senderId === message.studentId }">
              <div class="message-sender-avatar" v-if="message.senderId !== props.user?.id && message.senderId !== message.studentId" aria-hidden="true">
                <svg viewBox="0 0 24 24"><circle cx="12" cy="8" r="4" /><path d="M4 21a8 8 0 0 1 16 0" /></svg>
              </div>
              <div class="message-content">
                <p v-if="message.body">{{ message.body }}</p>
                <button v-if="message.attachmentUrl" type="button" class="message-image-button" @click.stop="openMessageImage(message.attachmentUrl)" aria-label="View photo">
                  <img :src="message.attachmentUrl" alt="Attached photo" />
                </button>
                <time>{{ formatMessageTime(message.createdAt) }}</time>
              </div>
              <div v-if="message.senderId === props.user?.id || message.senderId === message.studentId" class="message-action-wrap">
                <button type="button" class="message-menu-button" aria-label="Open message options" @click.stop="toggleMessageMenu(message.id)">⋯</button>
                <div v-if="openMessageMenuId === message.id" class="message-menu"><button type="button" class="message-menu-item" @click="unsendMessage(message.id)">Unsend</button></div>
              </div>
            </article>
            <p v-if="subjectMessages.length === 0" class="message-empty">Start a conversation about this subject.</p>
          </div>

          <div v-if="expandedImage" class="image-lightbox" role="dialog" aria-modal="true" aria-label="Expanded image" @click.self="closeMessageImage">
            <button type="button" class="image-lightbox-close" aria-label="Close image" @click="closeMessageImage">&#10005;</button>
            <img :src="expandedImage" alt="Expanded sent photo" />
          </div>

          <form class="message-composer" @submit.prevent="sendStudentMessage">
            <div v-if="messagePhotoPreview" class="message-photo-preview">
              <img :src="messagePhotoPreview" alt="Selected photo preview" />
              <button type="button" class="remove-photo-button" aria-label="Remove selected photo" @click="clearMessagePhoto">&#10005;</button>
            </div>
            <input ref="messageFileInput" type="file" accept="image/*" hidden @change="selectMessagePhoto" />
            <button type="button" class="gallery-button" aria-label="Insert photo" @click="messageFileInput?.click()">
              <svg viewBox="0 0 24 24" aria-hidden="true"><rect x="3" y="5" width="18" height="14" rx="2" /><circle cx="9" cy="10" r="2" /><path d="M7 17l4.5-4.5 3.5 3.5L17 14l4 3" /></svg>
            </button>
            <input v-model="messageText" placeholder="Message" />
            <button type="submit" class="send-button" aria-label="Send message">➤</button>
          </form>
        </section>
      </div>
      <button v-if="latestMessageSheet" class="student-chat-head" type="button" aria-label="Open professor messages" @click="openLatestMessage"><span>{{ latestMessageSheet.professor?.charAt(0) || 'P' }}</span><b v-if="unreadProfessorMessages">{{ unreadProfessorMessages }}</b></button>
    </main>
  </div>
</template>

<script setup>
import { ref, computed, nextTick, onMounted, onUnmounted, watch } from 'vue'
import { deleteMessage, getGradeSchedules, getMessages, getPublishedGrades, sendMessage } from '../services/dataService'
const sidebarOpen = ref(false)
function toggleSidebar(){ sidebarOpen.value = !sidebarOpen.value }
import colegioLogo from '../logo/The_Colegio_de_Montalban_Seal (1).png'

const props = defineProps({ user: { type: Object, default: null } })
const searchPublished = ref('')
const semester = ref('1st Semester 2025-2026')

const gwa = ref('-')
const enrolledSubjects = ref(0)
const studentStatus = ref('No records')
const totalUnits = ref(0)

const publishedGrades = ref([])
const publishedPanel = ref(null)
const messageSheet = ref(null)
const messages = ref([])
const messageText = ref('')
const messagePhoto = ref(null)
const messagePhotoPreview = ref('')
const messageFileInput = ref(null)
const seenMessageStorageKey = `seen_student_messages_${props.user?.username || 'student'}`
const seenMessageIds = ref(new Set(JSON.parse(localStorage.getItem(seenMessageStorageKey) || '[]')))
const messageHistory = ref(null)
const expandedImage = ref('')
const openMessageMenuId = ref(null)
const subjectMessages = computed(() => messageSheet.value ? [...messages.value.filter(message => message.gradeSheet?.id === messageSheet.value.id)].sort((left, right) => (left.createdAt || '').localeCompare(right.createdAt || '')) : [])
const latestProfessorMessage = computed(() => [...messages.value].filter(message => message.senderId !== props.user?.id && message.senderId !== message.studentId).sort((left, right) => (right.createdAt || '').localeCompare(left.createdAt || ''))[0])
const latestMessageSheet = computed(() => publishedGrades.value.find(sheet => sheet.id === latestProfessorMessage.value?.gradeSheet?.id) || (latestProfessorMessage.value ? publishedGrades.value.find(sheet => sheet.professor_id === latestProfessorMessage.value.professorId) : null))
const unreadProfessorMessages = computed(() => messages.value.filter(message => message.senderId !== props.user?.id && message.senderId !== message.studentId && !seenMessageIds.value.has(String(message.id))).length)

function markMessagesSeen(messageIds) {
  seenMessageIds.value = new Set([...seenMessageIds.value, ...messageIds.map(id => String(id))])
  localStorage.setItem(seenMessageStorageKey, JSON.stringify([...seenMessageIds.value]))
}

async function scrollStudentChatToBottom() {
  await nextTick()
  if (messageHistory.value) messageHistory.value.scrollTop = messageHistory.value.scrollHeight
}

watch(subjectMessages, scrollStudentChatToBottom)
let messageRefreshTimer

async function refreshMessages() {
  try {
    messages.value = await getMessages()
  } catch {
    // Keep the last saved conversation visible when the server is temporarily unavailable.
  }
}

onMounted(async () => {
  try {
    const [gradesResult, schedulesResult, messagesResult] = await Promise.allSettled([getPublishedGrades(), getGradeSchedules(), getMessages()])
    publishedGrades.value = gradesResult.status === 'fulfilled' ? gradesResult.value : []
    messages.value = messagesResult.status === 'fulfilled' ? messagesResult.value : []
    const schedules = schedulesResult.status === 'fulfilled' ? schedulesResult.value : []
    const records = publishedGrades.value.flatMap(sheet => sheet.student_records || [])
    const currentSemester = semester.value.split(' ')[0].toLowerCase()
    const activeSchedules = schedules.filter(schedule => schedule.semester.toLowerCase().includes(currentSemester) && schedule.yearLevel !== 'Grade Period')
    const expectedCourses = new Set(activeSchedules.flatMap(schedule => schedule.courses || []).map(course => course.replace(/^sheet:/, '').toLowerCase()))
    const completedGrades = publishedGrades.value.filter(sheet => {
      const record = sheet.student_records?.[0]
      return record && [record.midterm, record.finals, record.finalGrade, record.gradePoint].every(value => String(value || '').trim()) && Number.isFinite(Number(record.gradePoint))
    })
    const expectedSubjectCount = expectedCourses.size || publishedGrades.value.length
    const allGradesComplete = completedGrades.length === expectedSubjectCount && completedGrades.length > 0
    const gradePoints = completedGrades.map(sheet => Number(sheet.student_records?.[0]?.gradePoint)).filter(Number.isFinite)
    enrolledSubjects.value = expectedSubjectCount
    totalUnits.value = publishedGrades.value.reduce((total, sheet) => total + (Number(sheet.units) || 0), 0)
    studentStatus.value = expectedSubjectCount && !allGradesComplete ? 'Incomplete' : publishedGrades.value.length ? 'Active' : 'No records'
    gwa.value = allGradesComplete
      ? (gradePoints.reduce((total, point) => total + point, 0) / gradePoints.length).toFixed(2)
      : '-'
    messageRefreshTimer = window.setInterval(refreshMessages, 5000)
  } catch {
    publishedGrades.value = []
    enrolledSubjects.value = 0
    studentStatus.value = 'No records'
    totalUnits.value = 0
    gwa.value = '-'
  }
})

onUnmounted(() => {
  if (messageRefreshTimer) window.clearInterval(messageRefreshTimer)
})

const filteredPublished = computed(() => publishedGrades.value.filter(p => {
  const matchesSemester = !semester.value || p.semester.toLowerCase().includes(semester.value.split(' ')[0].toLowerCase())
  const query = searchPublished.value.toLowerCase()
  return matchesSemester && (!query || p.code.toLowerCase().includes(query) || p.subject.toLowerCase().includes(query))
}))

function showSubjects() {
  publishedPanel.value?.scrollIntoView({ behavior: 'smooth', block: 'start' })
}

function openMessage(sheet) {
  messageSheet.value = sheet
  messageText.value = ''
  messagePhoto.value = null
  messagePhotoPreview.value = ''
  const messageIds = messages.value.filter(message => (message.gradeSheet?.id === sheet.id || !message.gradeSheet && message.professorId === sheet.professor_id) && message.senderId !== props.user?.id && message.senderId !== message.studentId).map(message => message.id)
  markMessagesSeen(messageIds)
  openMessageMenuId.value = null
  scrollStudentChatToBottom()
}

function clearMessagePhoto() {
  messagePhoto.value = null
  messagePhotoPreview.value = ''
  if (messageFileInput.value) messageFileInput.value.value = ''
}

function toggleMessageMenu(messageId) {
  openMessageMenuId.value = openMessageMenuId.value === messageId ? null : messageId
}

function openMessageImage(url) {
  if (!url) return
  expandedImage.value = url
}

function closeMessageImage() {
  expandedImage.value = ''
}

function formatMessageTime(value) {
  if (!value) return ''
  const date = new Date(value)
  if (Number.isNaN(date.getTime())) return ''
  return date.toLocaleTimeString('en-US', { hour: 'numeric', minute: '2-digit' }).toUpperCase()
}

function openLatestMessage() {
  if (!latestMessageSheet.value) return
  openMessage(latestMessageSheet.value)
}

function selectMessagePhoto(event) {
  const file = event.target.files?.[0] || null
  messagePhoto.value = file
  messagePhotoPreview.value = file ? URL.createObjectURL(file) : ''
}

async function sendStudentMessage() {
  if (!messageSheet.value?.professor_id || !messageText.value.trim() && !messagePhoto.value) return
  try {
    const message = await sendMessage({ professorId: messageSheet.value.professor_id, gradeSheetId: messageSheet.value.id, body: messageText.value.trim(), attachment: messagePhoto.value || undefined })
    messages.value.unshift(message)
    messageText.value = ''
    messagePhoto.value = null
    messagePhotoPreview.value = ''
    if (messageFileInput.value) messageFileInput.value.value = ''
    scrollStudentChatToBottom()
  } catch (error) {
    window.alert(error instanceof Error ? error.message : 'Unable to send message.')
  }
}

async function unsendMessage(messageId) {
  if (!messageId) return
  try {
    await deleteMessage(Number(messageId))
    messages.value = messages.value.filter(message => message.id !== Number(messageId))
  } catch (error) {
    window.alert(error instanceof Error ? error.message : 'Unable to unsend this message.')
  }
}

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
  position: relative;
  overflow: hidden;
  border: 1px solid #1c5a34;
  border-radius: 9px 9px 0 0;
  background: #ffffff;
}

.grades-watermark {
  position: absolute;
  top: 52%;
  left: 50%;
  z-index: 0;
  color: #174b2a;
  font-size: clamp(28px, 5vw, 72px);
  font-weight: 900;
  letter-spacing: 2px;
  opacity: 0.07;
  pointer-events: none;
  transform: translate(-50%, -50%) rotate(-18deg);
  white-space: nowrap;
}

.panel-head {
  position: relative;
  z-index: 1;
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
  position: relative;
  z-index: 1;
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
.message-icon-button{border:0;background:transparent;color:#176337;cursor:pointer;font-size:16px}.message-backdrop{position:fixed;right:24px;bottom:24px;left:auto;top:auto;z-index:40;display:block;background:transparent}.student-message{width:min(420px,calc(100vw - 32px));overflow:hidden;border-radius:12px;background:#fff;box-shadow:0 8px 30px #0004}.message-header{display:flex;align-items:center;justify-content:space-between;padding:16px 18px;background:linear-gradient(180deg,#2f8f52 0%,#2d8b4f 100%);color:#fff}.message-user{display:flex;align-items:center;gap:10px;min-width:0}.message-avatar,.message-sender-avatar{width:34px;height:34px;border-radius:50%;display:grid;place-items:center;background:#fff;color:#2d8b4f;overflow:hidden;box-shadow:inset 0 0 0 1px rgba(0,0,0,.08)}.message-avatar svg,.message-sender-avatar svg{width:18px;height:18px;fill:none;stroke:currentColor;stroke-width:1.8;stroke-linecap:round;stroke-linejoin:round}.message-user-meta{display:flex;flex-direction:column;gap:2px;min-width:0}.message-user-meta strong{font-size:16px;line-height:1.1}.message-user-meta small{font-size:11px;opacity:.9}.message-header button{width:28px;height:28px;border:0;border-radius:50%;background:transparent;color:#fff;cursor:pointer;font-size:24px;line-height:1;display:grid;place-items:center}.message-context{display:none}.message-history{height:320px;overflow:auto;padding:12px 12px 8px;background:#f4f4f4}.message-divider{margin:10px 0 12px;text-align:center;color:#6a6a6a;font-size:10px;font-weight:700;letter-spacing:.12em}.message-history article{display:flex;align-items:flex-end;gap:8px;width:fit-content;max-width:calc(100% - 24px);margin:0 0 12px;padding:0}.message-history article.mine{margin-left:auto;justify-content:flex-end}.message-history .message-content{display:flex;flex-direction:column;align-items:flex-start;gap:5px;max-width:78%;min-width:0;padding:10px 12px;border-radius:12px;background:#fff;box-shadow:0 1px 0 rgba(0,0,0,.04);border:1px solid rgba(0,0,0,.04);overflow:hidden}.message-history article.mine .message-content{background:#cfeecf;border-color:rgba(45,139,79,.15);align-items:flex-end}.message-history article p{margin:0;color:#1d1d1d;font-size:14px;line-height:1.4;word-break:break-word}.message-history article time{font-size:10px;letter-spacing:.04em;color:#6d6d6d}.message-history article.mine time{color:#3a7f45}.message-history img{display:block;max-width:220px;max-height:220px;width:100%;height:auto;object-fit:contain;border-radius:10px;background:#e8eaed}.message-history .message-action-wrap{position:relative;display:flex;align-items:center;justify-content:center;padding-bottom:8px}.message-history .message-menu-button{border:0;background:transparent;color:#3f5a47;cursor:pointer;border-radius:6px;padding:2px 4px;font-size:18px;line-height:1}.message-history .message-menu{position:absolute;top:calc(100% + 4px);right:0;z-index:2;padding:5px;border:1px solid rgba(0,0,0,.08);border-radius:8px;background:#fff;box-shadow:0 6px 18px rgba(0,0,0,.08)}.message-history .message-menu-item{display:block;padding:6px 10px;border:0;border-radius:6px;background:#eef8f0;color:#145b2f;font-size:12px;font-weight:700;cursor:pointer}.message-empty{color:#777;text-align:center}.message-photo-preview{position:relative;display:flex;align-items:center;justify-content:center;max-height:120px;padding:8px 8px 0}.message-photo-preview img{display:block;max-width:120px;max-height:120px;border-radius:8px;border:1px solid #bfd9c6;background:#f1f9f3;object-fit:cover}.message-photo-preview .remove-photo-button{position:absolute;top:10px;right:10px;border:0;border-radius:50%;background:rgba(19,62,39,.8);color:#fff;cursor:pointer;width:20px;height:20px;display:grid;place-items:center;font-size:12px}.image-lightbox{position:fixed;inset:0;display:grid;place-items:center;background:rgba(0,0,0,.76);z-index:80}.image-lightbox img{display:block;max-width:min(90vw,900px);max-height:90vh;width:auto;height:auto;border-radius:12px;box-shadow:0 18px 60px rgba(0,0,0,.4)}.image-lightbox-close{position:absolute;top:24px;right:24px;width:42px;height:42px;border:0;border-radius:50%;background:rgba(255,255,255,.18);color:#fff;font-size:28px;cursor:pointer}.message-composer{display:flex;gap:7px;padding:10px;border-top:1px solid #ddd;align-items:flex-end;flex-wrap:wrap}.message-composer button{border:0;background:#bdecca;color:#145b2f;cursor:pointer;font-size:18px}.message-composer .gallery-button{display:grid;place-items:center;width:40px;height:36px;border-radius:8px;background:#dff5e6}.message-composer .gallery-button svg{width:18px;height:18px;stroke:currentColor;fill:none;stroke-width:1.8;stroke-linecap:round;stroke-linejoin:round}.message-composer input[type=text],.message-composer input:not([type]){flex:1;min-width:0;padding:8px;border:1px solid #bbb;border-radius:8px}
.student-chat-head{position:fixed;right:24px;bottom:24px;z-index:35;width:56px;height:56px;border:3px solid #fff;border-radius:50%;background:#2d8b4f;color:#fff;box-shadow:0 4px 14px #0004;cursor:pointer;font-size:20px;font-weight:800}.student-chat-head span{display:grid;place-items:center;width:100%;height:100%;border-radius:50%}.student-chat-head b{position:absolute;top:-6px;right:-6px;min-width:20px;padding:3px 5px;border-radius:12px;background:#d84b4b;color:#fff;font-size:11px}.student-chat-head:hover{background:#246f3e}
</style>
