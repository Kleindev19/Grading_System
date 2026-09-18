.message-badge{position:absolute;top:2px;right:2px;min-width:19px;height:19px;padding:0 5px;border-radius:10px;background:#e44747;color:#fff;font-size:11px;line-height:19px;text-align:center}
<template>
  <div class="prof-root">
    <div v-if="submissionNotice" class="submission-notice" role="status">Grades submitted successfully. Opening Registrar...</div>
    <div v-if="isMobile && !sidebarCollapsed" class="sidebar-overlay" @click="sidebarCollapsed = true"></div>
    <aside class="prof-sidebar" :class="{ open: !sidebarCollapsed && isMobile }">
      <div class="seal-wrap"><img :src="colegioLogo" alt="Colegio de Montalban" /></div>
      <div class="sidebar-rule"></div>
      <nav class="prof-nav" aria-label="Professor navigation">
        <button class="nav-button" :class="{ active: view === 'lists' || view === 'grades' }" type="button" aria-label="Class lists" @click="goToLists"><span v-if="view === 'lists' || view === 'grades'" class="active-bar"></span><svg viewBox="0 0 24 24"><rect x="4" y="4" width="5" height="5" /><rect x="15" y="4" width="5" height="5" /><rect x="4" y="15" width="5" height="5" /><rect x="15" y="15" width="5" height="5" /></svg></button>
        <button class="nav-button" :class="{ active: view === 'messages' }" type="button" aria-label="Messages" @click="openMessages"><span v-if="view === 'messages'" class="active-bar"></span><svg viewBox="0 0 24 24"><path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20" /><path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2Z" /></svg><b v-if="unreadMessageCount" class="message-badge">{{ unreadMessageCount > 9 ? '9+' : unreadMessageCount }}</b></button>
      </nav>
      <div class="prof-sidebar-footer"><div class="sidebar-rule footer-rule"></div><div class="prof-profile"><svg viewBox="0 0 24 24"><circle cx="12" cy="8" r="4" /><path d="M4 21a8 8 0 0 1 16 0" /></svg><span>{{ props.user?.name || 'PROFESSOR' }}</span></div><button class="logout-button" type="button" aria-label="Sign out" @click.stop="signOut"><svg viewBox="0 0 24 24"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4" /><path d="m16 17 5-5-5-5" /><path d="M21 12H9" /></svg></button></div>
    </aside>
    <main class="prof-page">
      <header class="prof-header"><button v-if="isMobile" class="mobile-hamburger" type="button" aria-label="Open menu" @click="sidebarCollapsed = false"><svg viewBox="0 0 24 24"><path d="M4 6h16M4 12h16M4 18h16" /></svg></button><h1>GRADE ENTRY</h1></header>
      <div v-if="view === 'lists'" class="dashboard-content"><div class="toolbar"><label class="search-box"><svg viewBox="0 0 24 24"><circle cx="11" cy="11" r="7" /><path d="m21 21-5-5" /></svg><input v-model="searchStudent" placeholder="Search student name or ID..." /></label><select v-model="selectedSection"><option value="all">Section &amp; Year</option><option v-for="section in sections" :key="section" :value="section">{{ section }}</option></select></div><div class="tabs"><button :class="{ selected: tab === 'lists' }" type="button" @click="tab = 'lists'">Class Lists</button><button :class="{ selected: tab === 'messages' }" type="button" @click="openMessages">Messages</button></div><section class="roster-panel"><div class="roster-heading"><h2>Student Rosters by Institute</h2><p>Browse enrolled students across all your assigned courses.</p></div><button v-for="institute in institutes" :key="institute.id" class="institute-line" :class="{ expanded: expandedInstitute === institute.id }" type="button" @click="toggleInstitute(institute.id)"><span>{{ expandedInstitute === institute.id ? '&#8964;' : '&#8250;' }}</span>{{ institute.name }}</button><template v-if="expandedInstitute"><div class="year-line"><strong>YEAR LEVEL:</strong><button v-for="year in years" :key="year" :class="{ chosen: selectedYear === year }" type="button" @click="selectedYear = year">{{ year }}</button></div><div v-for="group in visibleGroups" :key="group.name" class="course-group"><h3>{{ group.name }}</h3><div class="course-grid"><button v-for="course in group.courses" :key="course.code + course.section" class="course-card" type="button" @click="openGrades(course)"><small>{{ course.code }}</small><strong>{{ course.section }}</strong><span>1st Sem, A.Y 2025-26 | &#9673; {{ course.students }} Students</span></button></div></div></template></section></div>
      <div v-else-if="view === 'messages'" class="messages-content"><div class="toolbar"><label class="search-box"><svg viewBox="0 0 24 24"><circle cx="11" cy="11" r="7" /><path d="m21 21-5-5" /></svg><input v-model="searchStudent" placeholder="Search student name or ID..." /></label><select v-model="selectedSection"><option value="all">Section &amp; Year</option><option v-for="section in sections" :key="section" :value="section">{{ section }}</option></select></div><div class="tabs"><button type="button" @click="goToLists">Class Lists</button><button class="selected" type="button">Messages</button></div><div class="message-layout"><section class="conversation-list"><h2>Student Conversations</h2><button v-for="message in filteredMessages" :key="message.id" class="message-preview" :class="{ active: activeMessage.id === message.id }" type="button" @click="openConversation(message)"><strong>{{ message.name }}</strong><span>{{ message.preview }}</span><small>{{ message.id }} &bull; {{ message.section }}</small></button></section><section class="conversation"><div class="conversation-head"><div class="avatar">{{ activeMessage.name.charAt(0) }}</div><div><strong>{{ activeMessage.name }}</strong><small>{{ activeMessage.id }} &bull; {{ activeMessage.section }}</small></div><select v-model="messageAssessment" @change="openStudentGrade"><option>Midterm</option><option>Finals</option><option>Final Grade</option></select></div><div class="chat"><span class="chat-time">CONVERSATION</span><template v-if="activeMessage.messages?.length"><article v-for="message in activeMessage.messages" :key="message.id" class="message-bubble" :class="{ outgoing: message.senderId !== message.studentId }"><img v-if="message.attachmentUrl" :src="message.attachmentUrl" alt="Student attachment" /><p v-if="message.body">{{ message.body }}</p><button v-if="message.senderId === props.user?.id" type="button" class="unsend-button" aria-label="Unsend message" @click="unsendMessage(message.id)">Unsend</button><small>{{ message.createdAt ? new Date(message.createdAt).toLocaleString() : "" }}</small></article></template><p v-else class="message-empty">No messages yet.</p></div><div class="reply"><input v-model="replyText" placeholder="Type your reply..." /><button type="button" @click="sendProfessorReply">Send Reply</button></div></section></div></div>
      <div v-else class="grades-content"><button class="back-link" type="button" @click="goToLists">&#8592; Return to Dashboard</button><div class="grade-toolbar"><label class="search-box"><svg viewBox="0 0 24 24"><circle cx="11" cy="11" r="7" /><path d="m21 21-5-5" /></svg><input v-model="searchStudent" placeholder="Search student name or ID..." /></label><div class="grade-actions"><div class="choice-action"><button type="button" class="add-button" @click="toggleAddChoices">+ Add Assessment</button><div v-if="addChoiceOpen" class="assessment-choices" aria-label="Choose assessment type"><button type="button" @click="addAssessment('Quiz')">Quiz</button><button type="button" @click="addAssessment('Activity')">Activity</button><button type="button" @click="addAssessment('Recitation')">Recitation</button><button type="button" @click="addAssessment('Major Exam')">Major Exam</button></div></div><div class="choice-action"><button type="button" class="remove-button" :disabled="gradeHeaders.length <= 1" @click="toggleRemoveChoices">&#9632; Remove</button><div v-if="removeChoiceOpen" class="assessment-choices" aria-label="Choose assessment to remove"><button v-for="assessment in gradeHeaders" :key="assessment" type="button" @click="selectedAssessment = assessment; removeSelectedAssessment()">{{ assessment }}</button></div></div><div class="choice-action"><button type="button" class="restore-button" @click="toggleRestoreChoices">&#8634; Restore</button><div v-if="restoreChoiceOpen" class="assessment-choices" aria-label="Choose assessment to restore"><button v-for="assessment in defaultGradeHeaders" :key="assessment" type="button" :disabled="gradeHeaders.includes(assessment)" @click="selectedAssessment = assessment; restoreSelectedAssessment()">{{ assessment }}</button></div></div></div></div><div class="grade-columns"><section class="grade-table-card"><h2>{{ selectedCourse.name }} ({{ selectedCourse.section }})</h2><div class="period-tabs"><button :class="{ chosen: activePeriod === 'midterm' }" type="button" :disabled="isGradeLocked('midterm')" @click="activePeriod = 'midterm'"><span v-if="isGradeLocked('midterm')" aria-hidden="true">&#128274;</span> Midterm</button><button :class="{ chosen: activePeriod === 'finals' }" type="button" :disabled="isGradeLocked('finals')" @click="openFinalsByDeadline"><span v-if="isGradeLocked('finals')" aria-hidden="true">&#128274;</span> Finals</button><button :class="{ chosen: activePeriod === 'final-grade' }" type="button" @click="activePeriod = 'final-grade'">Final Grade</button></div><div class="table-scroll"><table class="grade-table"><thead><tr><th rowspan="2">#</th><th rowspan="2">STUDENT ID</th><th rowspan="2" class="wide">STUDENT NAME</th><th rowspan="2">MIDTERM</th><th colspan="5">QUIZ</th><th colspan="4">ACTIVITY</th><th>RECI</th><th>M. Exam</th></tr><tr><th v-for="label in gradeHeaders" :key="label">{{ label }}</th></tr></thead><tbody><tr v-for="(student, index) in gradeStudents" :key="student.id"><td>{{ index + 1 }}</td><td>{{ student.id }}</td><td class="name-cell">{{ student.name }}</td><td>{{ student.midterm }}</td><td v-for="label in gradeHeaders" :key="label"><input v-model="student.scores[label]" type="number" min="0" max="20" :disabled="isGradeLocked(activePeriod)" /></td></tr></tbody></table></div></section><aside class="summary-box"><h2>MIDTERM</h2><table><thead><tr><th>Items</th><th>Max Score</th><th>Percentage</th></tr></thead><tbody><tr v-for="item in midtermItems" :key="item.name"><td>{{ item.name }}</td><td>-</td><td>-</td></tr><tr class="total"><td>TOTAL</td><td>-</td><td>-</td></tr></tbody></table></aside></div><button class="submit-action-btn" type="button" @click="submitToRegistrar">Submit to Registrar</button><div v-if="showFinalsNotice" class="modal-backdrop" @click.self="showFinalsNotice = false"><section class="finals-notice" role="dialog" aria-modal="true" aria-labelledby="finals-title"><div class="notice-icon">&#128197;</div><h2 id="finals-title">Hang tight - finals aren't ready yet</h2><p>The registrar sets when grading starts, so there's nothing to do here for now. We'll let you know the moment it's ready.</p><strong>Opens on {{ formatPeriodDate('finals') }}</strong><button type="button" @click="showFinalsNotice = false">Close</button></section></div></div>
    </main>
  </div>
</template>

<script setup>
import { computed, nextTick, onMounted, onUnmounted, reactive, ref, watch } from 'vue'
import colegioLogo from '../logo/The_Colegio_de_Montalban_Seal (1).png'
import { deleteMessage, getGradeSchedules, getGradeSheets, getMessages, getStudents, groupMessages, sendMessage, submitGradeSheet } from '../services/dataService'
const activeRoster = ref([])
const props = defineProps({ user: { type: Object, default: null } })
const emit = defineEmits(['signout', 'submitted'])
const isMobile = ref(window.innerWidth <= 768); const sidebarCollapsed = ref(false); const view = ref('lists'); const tab = ref('lists'); const searchStudent = ref(''); const selectedSection = ref('all'); const selectedYear = ref('1st Year'); const expandedInstitute = ref(''); const messageAssessment = ref('Midterm'); const activePeriod = ref('midterm'); const selectedAssessment = ref('Q1'); const addChoiceOpen = ref(false); const removeChoiceOpen = ref(false); const restoreChoiceOpen = ref(false); const showFinalsNotice = ref(false); const submissionNotice = ref(false); const submissionSent = ref(false); const replyText = ref(''); const years = ['1st Year', '2nd Year', '3rd Year', '4th Year']; const defaultGradeHeaders = ['Q1', 'Q2', 'Q3', 'Q4', 'Q5', 'A1', 'A2', 'A3', 'A4', 'R1', 'Mid Ex']; const gradeHeaders = reactive([...defaultGradeHeaders]); const midtermItems = [{ name: 'Quiz' }, { name: 'Activity' }, { name: 'Recitation' }, { name: 'Major Exam' }]
const gradePeriods = ref([])
const gradeSheets = ref([])
const currentClock = ref(Date.now())
const unreadMessageIds = ref(new Set())
const unreadMessageCount = computed(() => unreadMessageIds.value.size)

function resolveDeadline(dateValue) {
  if (!dateValue) return null
  const deadline = new Date(`${dateValue}T00:00:00`)
  return Number.isNaN(deadline.getTime()) ? null : deadline
}

function getGradePeriod(type) {
  const period = gradePeriods.value[0]
  if (!period) return null
  return {
    opens: resolveDeadline(type === 'midterm' ? period.midtermOpens : period.finalsOpens),
    deadline: resolveDeadline(type === 'midterm' ? period.midtermDeadline : period.finalsDeadline),
  }
}

function isGradeLocked(type) {
  const period = getGradePeriod(type)
  if (!period?.opens || !period?.deadline) return true
  const today = new Date(currentClock.value)
  today.setHours(0, 0, 0, 0)
  return today.getTime() < period.opens.getTime() || today.getTime() >= period.deadline.getTime()
}

function formatPeriodDate(type) {
  const period = getGradePeriod(type)
  if (!period?.opens) return 'the configured date'
  return period.opens.toLocaleDateString('en-US', { month: 'long', day: 'numeric', year: 'numeric' })
}

function openFinalsByDeadline() {
  if (isGradeLocked('finals')) {
    showFinalsNotice.value = true
    return
  }
  activePeriod.value = 'finals'
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
  gradeSheets.value.forEach(sheet => {
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
      student_records: sheet.student_records || [],
    })
  })
  return [...instituteMap.values()]
})

const selectedInstitute = computed(() => institutes.value.find(institute => institute.id === expandedInstitute.value) || institutes.value[0] || { groups: [] }); const visibleGroups = computed(() => (selectedInstitute.value.groups || []).map(group => ({ ...group, courses: group.courses.filter(course => selectedSection.value === 'all' || course.section.includes(selectedSection.value)) }))); const sections = computed(() => institutes.value.flatMap(institute => institute.groups.flatMap(group => group.courses.map(course => course.section)))); const messages = reactive([]); const openMessageMenuId = ref(null); const chatContainer = ref(null); const activeMessage = ref({ id: '', name: '', section: '', preview: '', course: { name: '', section: '' } }); const selectedCourse = ref({ name: '', section: '' }); const gradeStudents = reactive([]); const hasIncompleteGrades = computed(() => gradeStudents.length === 0 || gradeStudents.some(student => !String(student.id || '').trim() || !String(student.name || '').trim() || !String(student.midterm || '').trim() || !String(student.finals || '').trim() || !String(student.finalGrade || '').trim() || !String(student.gradePoint || '').trim())); const filteredMessages = computed(() => groupMessages(messages).map(thread => ({ id: thread.studentNumber || thread.key, name: thread.studentName || 'Student', section: thread.course.section, preview: thread.messages.at(-1)?.body || 'Photo attachment', course: thread.course, studentId: thread.studentId, studentNumber: thread.studentNumber, gradeSheetId: thread.gradeSheetId, senderId: thread.messages.at(-1)?.senderId, unreadCount: thread.unreadCount, messages: [...thread.messages].sort((left, right) => (left.createdAt || '').localeCompare(right.createdAt || '')) })).filter(message => `${message.name} ${message.id}`.toLowerCase().includes(searchStudent.value.toLowerCase())).sort((left, right) => { const leftTime = left.messages.at(-1)?.createdAt || ''; const rightTime = right.messages.at(-1)?.createdAt || ''; return leftTime.localeCompare(rightTime) }));
function scrollChatToBottom() {
  nextTick(() => {
    const container = chatContainer.value || document.querySelector('.conversation .chat')
    if (!container) return
    container.scrollTop = container.scrollHeight
  })
}
function openConversation(message) {
  activeMessage.value = message
  unreadMessageIds.value = new Set([...unreadMessageIds.value].filter(id => !message.messages.some(item => String(item.id) === id)))
  openMessageMenuId.value = null
  scrollChatToBottom()
}
function toggleInstitute(id) {
  expandedInstitute.value = expandedInstitute.value === id ? '' : id
}

function toggleMessageMenu(messageId) {
  openMessageMenuId.value = openMessageMenuId.value === messageId ? null : messageId
}

function openGrades(course) {
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
  const loadedRoster = roster.map(student => ({ ...student, ...(savedRecords.get(student.id) || {}), scores: { ...(student.scores || {}), ...(savedRecords.get(student.id)?.scores || {}) } }))
  activeRoster.value = loadedRoster
  originalGradeStudents.splice(0, originalGradeStudents.length, ...loadedRoster.map(student => ({ ...student, scores: { ...(student.scores || {}) } })))
  gradeStudents.splice(0, gradeStudents.length, ...loadedRoster)
  activePeriod.value = 'midterm'
  view.value = 'grades'
}

function openFinals() {
  openFinalsByDeadline()
}

async function submitToRegistrar() {
  const records = gradeStudents
    .filter(student => student.id.trim() && student.name.trim())
    .map(student => ({
      id: student.id.trim(),
      name: student.name.trim(),
      midterm: student.midterm || '',
      finals: student.finals || '',
      finalGrade: student.finalGrade || '',
      gradePoint: student.gradePoint || '',
      remarks: student.remarks || '',
      scores: student.scores || {},
    }))

  if (!records.length || records.length !== gradeStudents.length) {
    window.alert('Enter a student ID and name for every row before submitting.')
    return
  }
  if (records.some(student => !student.midterm.trim() || !student.finals.trim() || !student.finalGrade.trim() || !student.gradePoint.trim())) {
    window.alert('Complete the midterm, finals, final grade, and grade point for every student before submitting.')
    return
  }
  if (!selectedCourse.value.code || !selectedCourse.value.semester) {
    window.alert('Select a valid course before submitting grades.')
    return
  }
  if (!Number(selectedCourse.value.units)) {
    window.alert('Enter the actual units for this subject before submitting.')
    return
  }

  try {
    await submitGradeSheet({
      code: selectedCourse.value.code,
      subject: selectedCourse.value.name,
      section: selectedCourse.value.section,
      professor: props.user?.name || 'Professor',
      semester: selectedCourse.value.semester,
      students: records.length,
      units: Number(selectedCourse.value.units),
      submitted: new Date().toLocaleDateString('en-US'),
      status: 'Submitted',
    }, records)
    submissionSent.value = true
    emit('submitted')
  } catch (error) {
    window.alert(error instanceof Error ? error.message : 'Unable to submit grades.')
  }
}

function openMessages() {
  tab.value = 'messages'
  view.value = 'messages'
  scrollChatToBottom()
}

function goToLists() {
  view.value = 'lists'
  tab.value = 'lists'
}

function signOut() {
  emit('signout')
}

function handleResize() {
  isMobile.value = window.innerWidth <= 768
}

onMounted(() => {
  window.addEventListener('resize', handleResize)
})

onUnmounted(() => window.removeEventListener('resize', handleResize))
  messages.splice(0)
  gradeStudents.splice(0)
  activeMessage.value = { id: '', name: '', section: '', preview: '', course: { name: '', section: '' } }
  selectedCourse.value = { name: '', section: '' }
function openStudentGrade() {
  const sheet = gradeSheets.value.find(item => item.id === activeMessage.value.gradeSheetId)
  const record = sheet?.student_records?.find(item => item.id === activeMessage.value.studentNumber)
  if (!record || !sheet) return
  gradeStudents.splice(0, gradeStudents.length, { id: record.id, name: record.name, midterm: record.midterm || '', finals: record.finals || '', finalGrade: record.finalGrade || '', gradePoint: record.gradePoint || '', remarks: record.remarks || '', scores: record.scores || {} })
  allGradeStudents.splice(0, allGradeStudents.length, ...gradeStudents)
  selectedCourse.value = { code: sheet.code, name: sheet.subject, section: sheet.section, semester: sheet.semester, units: sheet.units }
  activePeriod.value = messageAssessment.value === 'Finals' ? 'finals' : messageAssessment.value === 'Final Grade' ? 'final-grade' : 'midterm'
  view.value = 'grades'
}

async function sendProfessorReply() {
  if (!replyText.value.trim() || !activeMessage.value.studentId) return
  try {
    const message = await sendMessage({ studentId: activeMessage.value.studentId, gradeSheetId: activeMessage.value.gradeSheetId, body: replyText.value.trim() })
    const replyMessage = { ...message, id: String(message.id), name: message.studentName || activeMessage.value.name, section: message.gradeSheet?.section || activeMessage.value.section, preview: message.body || '', course: { name: message.gradeSheet?.subject || activeMessage.value.course.name, section: message.gradeSheet?.section || activeMessage.value.course.section }, studentId: message.studentId, studentNumber: message.studentNumber, gradeSheetId: message.gradeSheet?.id, senderId: message.senderId }
    messages.push(replyMessage)
    activeMessage.value.messages = [...(activeMessage.value.messages || []), replyMessage].sort((left, right) => (left.createdAt || '').localeCompare(right.createdAt || ''))
    replyText.value = ''
    scrollChatToBottom()
  } catch (error) {
    window.alert(error instanceof Error ? error.message : 'Unable to send reply.')
  }
}

async function unsendMessage(messageId) {
  if (!messageId) return
  try {
    await deleteMessage(Number(messageId))
    messages.splice(0, messages.length, ...messages.filter(message => message.id !== Number(messageId)))
    if (activeMessage.value && activeMessage.value.messages) {
      activeMessage.value.messages = activeMessage.value.messages.filter(message => message.id !== Number(messageId))
      if (activeMessage.value.messages.length === 0) {
        activeMessage.value.preview = 'No messages yet.'
      }
    }
  } catch (error) {
    window.alert(error instanceof Error ? error.message : 'Unable to unsend this message.')
  }
}

const allGradeStudents = reactive([])
const originalGradeStudents = reactive([])

watch(submissionSent, (submitted) => {
  if (submitted) {
    submissionNotice.value = true
    window.setTimeout(() => {
      submissionNotice.value = false
    }, 2500)
  }
})

watch(() => activeMessage.value.id, () => {
  scrollChatToBottom()
})

watch(() => activeMessage.value.messages, () => {
  scrollChatToBottom()
}, { deep: true })

watch(() => view.value, (nextView) => {
  if (nextView === 'messages') {
    scrollChatToBottom()
  }
})

function addAssessment(category = 'Quiz') {
  const prefixes = { Quiz: 'Q', Activity: 'A', Recitation: 'R', 'Major Exam': 'Mid Ex' }
  const prefix = prefixes[category]
  const matchingHeaders = gradeHeaders.filter(header => header.startsWith(prefix))
  const label = prefix === 'Mid Ex' ? `${prefix}${matchingHeaders.length + 1}` : `${prefix}${matchingHeaders.length + 1}`
  gradeHeaders.push(label)
  gradeStudents.forEach(student => { student.scores[label] = '' })
  selectedAssessment.value = label
  addChoiceOpen.value = false
}

function toggleAddChoices() {
  addChoiceOpen.value = !addChoiceOpen.value
  removeChoiceOpen.value = false
  restoreChoiceOpen.value = false
}

function removeAssessment() {
  const index = gradeHeaders.indexOf(selectedAssessment.value)
  if (index === -1 || gradeHeaders.length <= 1) return
  const [label] = gradeHeaders.splice(index, 1)
  gradeStudents.forEach(student => { delete student.scores[label] })
  selectedAssessment.value = gradeHeaders[Math.max(0, index - 1)]
}

function restoreAssessment() {
  if (gradeHeaders.includes(selectedAssessment.value)) return
  const originalIndex = defaultGradeHeaders.indexOf(selectedAssessment.value)
  if (originalIndex === -1) return
  gradeHeaders.splice(Math.min(originalIndex, gradeHeaders.length), 0, selectedAssessment.value)
  const originalScores = new Map(originalGradeStudents.map(student => [student.id, student.scores?.[selectedAssessment.value] || '']))
  allGradeStudents.forEach(student => { student.scores[selectedAssessment.value] = originalScores.get(student.id) || '' })
  gradeStudents.forEach(student => { student.scores[selectedAssessment.value] = originalScores.get(student.id) || '' })
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
  gradeHeaders.splice(0, gradeHeaders.length, ...defaultGradeHeaders)
  const restoredStudents = originalGradeStudents.map(student => ({ ...student, scores: { ...student.scores } }))
  allGradeStudents.splice(0, allGradeStudents.length, ...restoredStudents.map(student => ({ ...student, scores: { ...student.scores } })))
  gradeStudents.splice(0, gradeStudents.length, ...restoredStudents)
  selectedAssessment.value = defaultGradeHeaders[0]
}

watch(searchStudent, (query) => {
  const normalizedQuery = query.trim().toLowerCase()
  const visibleStudents = activeRoster.value.filter(student => !normalizedQuery || `${student.id} ${student.name}`.toLowerCase().includes(normalizedQuery))
  gradeStudents.splice(0, gradeStudents.length, ...visibleStudents)
})

let gradeClockTimer
let messageRefreshTimer

async function refreshProfessorMessages() {
  try {
    const loadedMessages = await getMessages()
    messages.splice(0, messages.length, ...loadedMessages.map(message => ({ ...message, id: String(message.id), name: message.studentName || '', section: message.gradeSheet?.section || '', preview: message.body || 'Photo attachment', course: { name: message.gradeSheet?.subject || '', section: message.gradeSheet?.section || '' }, studentId: message.studentId, studentNumber: message.studentNumber, gradeSheetId: message.gradeSheet?.id, senderId: message.senderId })))
    const activeKey = activeMessage.value.id
    const updatedThread = filteredMessages.value.find(message => message.id === activeKey || message.studentNumber === activeKey)
    if (updatedThread) activeMessage.value = updatedThread
    unreadMessageIds.value = new Set(loadedMessages
      .filter(message => message.senderId !== props.user?.id && !message.readAt && message.studentId !== activeMessage.value.studentId)
      .map(message => String(message.id)))
    if (updatedThread) scrollChatToBottom()
  } catch {
    // Keep the last saved conversation visible when the server is temporarily unavailable.
  }
}

onMounted(async () => {
  gradeClockTimer = window.setInterval(() => {
    currentClock.value = Date.now()
  }, 60000)

  try {
    const [studentsResult, schedulesResult, sheetsResult, messagesResult] = await Promise.allSettled([getStudents(), getGradeSchedules(), getGradeSheets(), getMessages()])
    if (studentsResult.status !== 'fulfilled' || schedulesResult.status !== 'fulfilled' || sheetsResult.status !== 'fulfilled') throw new Error('Unable to load grade data.')
    const students = studentsResult.value
    const schedules = schedulesResult.value
    const sheets = sheetsResult.value
    const loadedMessages = messagesResult.status === 'fulfilled' ? messagesResult.value : []
    const records = students.map(student => ({ id: student.student_id, name: student.name, institute: student.institute, course: student.course, year_level: student.year_level, section: student.section, midterm: '', finals: '', finalGrade: '', gradePoint: '', remarks: '', scores: {} }))
    originalGradeStudents.splice(0, originalGradeStudents.length, ...records.map(student => ({ ...student, scores: { ...student.scores } })))
    allGradeStudents.splice(0, allGradeStudents.length, ...records)
    activeRoster.value = records
    gradeStudents.splice(0, gradeStudents.length, ...records)
    gradePeriods.value = schedules.filter(schedule => schedule.yearLevel === 'Grade Period')
    gradeSheets.value = sheets
    messages.splice(0, messages.length, ...loadedMessages.map(message => ({ ...message, id: String(message.id), name: message.studentName || '', section: message.gradeSheet?.section || '', preview: message.body || 'Photo attachment', course: { name: message.gradeSheet?.subject || '', section: message.gradeSheet?.section || '' }, studentId: message.studentId, studentNumber: message.studentNumber, gradeSheetId: message.gradeSheet?.id, senderId: message.senderId })))
    unreadMessageIds.value = new Set(loadedMessages
      .filter(message => message.senderId !== props.user?.id && !message.readAt)
      .map(message => String(message.id)))
    messageRefreshTimer = window.setInterval(refreshProfessorMessages, 5000)
  } catch (error) {
    window.alert(error instanceof Error ? error.message : 'Unable to load students.')
  }
})

onUnmounted(() => {
  if (gradeClockTimer) window.clearInterval(gradeClockTimer)
  if (messageRefreshTimer) window.clearInterval(messageRefreshTimer)
})

</script>

<style scoped>
*,*::before,*::after{box-sizing:border-box}.prof-root{position:absolute;inset:0;display:flex;overflow:hidden;background:#fff;color:#111;font-family:Arial,Helvetica,sans-serif}.prof-sidebar{width:108px;flex:0 0 108px;display:flex;flex-direction:column;align-items:center;padding:50px 0 28px;color:#fff;background:linear-gradient(#124a29 0%,#17552c 58%,#72a900 100%);z-index:20}.seal-wrap img{width:68px;height:68px;object-fit:contain}.sidebar-rule{width:70px;height:1px;margin:34px 0 56px;background:#ffffffb3}.prof-nav{display:flex;flex-direction:column;gap:24px;align-items:center;width:100%}.nav-button,.logout-button,.mobile-hamburger{border:0;background:transparent;color:inherit;cursor:pointer}.nav-button{position:relative;width:70px;height:54px;border-radius:9px}.nav-button.active{background:#54a569a6}.active-bar{position:absolute;left:-9px;top:8px;width:6px;height:38px;border-radius:0 7px 7px 0;background:#ffdf4f}.nav-button svg,.logout-button svg,.prof-profile svg,.mobile-hamburger svg{width:28px;height:28px;fill:none;stroke:currentColor;stroke-width:2;stroke-linecap:round;stroke-linejoin:round}.nav-button.active svg{color:#ffe66b}.prof-sidebar-footer{width:100%;margin-top:auto;display:flex;flex-direction:column;align-items:center}.footer-rule{margin:0 0 20px}.prof-profile{display:flex;flex-direction:column;align-items:center;gap:6px;margin-bottom:32px;font-size:12px;font-weight:800}.prof-profile svg{width:38px;height:38px}.prof-page{min-width:0;flex:1;overflow:hidden;background:#fff}.prof-header{height:80px;display:flex;align-items:center;padding:0 29px;border-bottom:1px solid #ccc;box-shadow:0 2px 4px #0002}.prof-header h1{margin:0;font-family:Georgia,serif;font-size:30px}.bell{margin-left:auto;font-size:25px}.dashboard-content,.messages-content,.grades-content{height:calc(100% - 80px);overflow:auto;padding:31px 29px}.toolbar,.grade-toolbar{display:flex;align-items:center;gap:32px;margin-bottom:26px}.grades-content{display:flex;flex-direction:column;align-items:flex-start}.search-box{width:320px;height:36px;display:flex;align-items:center;gap:10px;padding:0 12px;border:1px solid #777;border-radius:7px}.search-box svg{width:22px;height:22px;fill:none;stroke:#666;stroke-width:2}.search-box input{width:100%;border:0;outline:0;font-size:12px}.toolbar select{width:168px;height:36px;padding:0 10px;border:1px solid #777;border-radius:7px;background:#fff}.deadlines{margin-left:auto;align-self:flex-start;font-size:19px;font-weight:900}.tabs{width:392px;display:flex;margin-bottom:23px;border:1px solid #c5c5c5;border-radius:13px;overflow:hidden}.tabs button{flex:1;height:48px;border:0;background:#fff;font-size:17px;cursor:pointer}.tabs button.selected{background:#e0ffeb;color:#145b2f;border-bottom:2px solid #145b2f}.roster-panel{max-width:1010px;border:1px solid #bbb;border-radius:9px;overflow:hidden}.roster-heading{padding:15px 18px 12px;border-bottom:1px solid #bbb}.roster-heading h2{margin:0;font-size:27px}.roster-heading p{margin:2px 0 0;font-size:17px}.institute-line{height:55px;display:flex;align-items:center;gap:13px;padding:0 14px;border-bottom:1px solid #bbb;font-size:18px;font-weight:800}.institute-line span{font-size:24px}.year-line{display:flex;align-items:center;gap:16px;padding:14px 16px;background:#effff4;border-bottom:1px solid #bbb}.year-line strong{font-size:17px}.year-line button{padding:4px 18px;border:1px solid #bbb;border-radius:8px;background:#f2f2f2;cursor:pointer}.year-line button.chosen{background:#ffffbd;border-color:#b6b66e}.course-group{padding:15px 16px 27px;border-bottom:1px solid #bbb}.course-group h3{margin:0 0 17px;font-size:18px}.course-grid{display:flex;flex-wrap:wrap;gap:22px}.course-card{width:164px;min-height:68px;padding:5px;text-align:left;border:1px solid #c6c47b;border-radius:8px;background:#ffffcc;box-shadow:0 2px 3px #0003;cursor:pointer}.course-card small,.course-card strong,.course-card span{display:block}.course-card small{width:max-content;padding:1px 7px;border-radius:7px;background:#e3e3e3;font-size:9px}.course-card strong{margin:7px 0 4px;text-align:center;font-size:16px}.course-card span{font-size:8px;white-space:nowrap}.message-layout{display:grid;grid-template-columns:318px minmax(450px,1fr);gap:20px;max-width:1015px}.conversation-list,.conversation{min-height:325px;border:1px solid #ccc;border-radius:13px;box-shadow:0 2px 3px #0003;overflow:hidden}.conversation-list h2{margin:0;padding:14px 40px;border-bottom:1px solid #bbb;font-family:Georgia,serif;font-size:23px}.message-preview{display:block;width:calc(100% - 38px);margin:17px 19px;padding:10px 14px;text-align:left;border:1px solid #ddd;border-radius:8px;background:#fff;cursor:pointer}.message-preview.active{border-left:6px solid #27683f}.message-preview strong,.message-preview span,.message-preview small{display:block}.message-preview span{margin:3px 0;font-size:12px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}.message-preview small{color:#ef8e24;font-weight:800}.conversation-head{display:flex;align-items:center;gap:10px;padding:10px;border-bottom:1px solid #aaa}.avatar{width:36px;height:36px;display:grid;place-items:center;border-radius:50%;background:#264f39;color:#fff;font-weight:900}.conversation-head strong,.conversation-head small{display:block}.conversation-head select{margin-left:auto;width:108px}.chat{height:210px;padding:13px 16px}.chat-time{display:block;margin-bottom:10px;text-align:center;color:#777;font-size:10px}.chat p{max-width:315px;padding:9px 11px;border-radius:12px;font-size:14px}.incoming{border:1px solid #98af9d}.outgoing{margin:4px 0 0 auto;background:#506d5b;color:#fff}.reply{display:flex;gap:14px;padding:10px 16px}.reply input{flex:1;padding:10px;border:1px solid #aaa;border-radius:8px}.reply button,.grade-actions button{padding:8px 18px;border:1px solid #bbb;border-radius:7px;background:#fff;cursor:pointer}.reply button{border:0;background:#295d3e;color:#fff;font-weight:800}.back-link{padding:7px 13px;border:0;border-radius:14px;background:#d3f6df;color:#175d33;font-weight:800;cursor:pointer}.grade-toolbar{margin-top:56px}.grade-actions{display:flex;gap:8px}.add-button{background:#c4f0d1!important;border-color:#176337!important}.remove-button{background:#ff5c61!important;color:#fff}.grade-columns{display:grid;grid-template-columns:minmax(650px,1fr) 250px;gap:22px}.grade-table-card h2{margin:0 0 10px;font-family:Georgia,serif}.period-tabs{display:flex;width:386px;margin:0 0 10px 25px;padding:5px;border:1px solid #777;border-radius:20px}.period-tabs button{flex:1;border:0;border-radius:16px;background:#fff;padding:4px;cursor:pointer}.period-tabs button.chosen{background:#58c77e;color:#fff}.table-scroll{overflow:auto}.grade-table{width:100%;min-width:970px;border-collapse:collapse}.grade-table th,.grade-table td{border:1px solid #6b9875;text-align:center;font-size:12px}.grade-table th{height:29px;background:#cbf5d5}.grade-table td{height:30px}.grade-table th.wide{min-width:270px}.grade-table .name-cell{text-align:left;padding-left:7px}.grade-table input{width:35px;border:0;text-align:center;background:transparent}.summary-box{height:max-content;border:1px solid #333}.summary-box h2{margin:0;padding:4px;background:#b8e4c4;text-align:center;font-size:17px}.summary-box table{width:100%;border-collapse:collapse}.summary-box th,.summary-box td{padding:4px;border:1px solid #333;text-align:center;font-size:12px}.summary-box .total td{background:#b8e4c4;font-weight:900}@media(max-width:900px){.prof-sidebar{position:fixed;inset:0 auto 0 0;transform:translateX(-100%);transition:transform .2s}.prof-sidebar.open{transform:translateX(0)}.sidebar-overlay{position:fixed;inset:0;display:block;background:#0005;z-index:10}.mobile-hamburger{display:block;margin-right:12px}.toolbar{flex-wrap:wrap}.deadlines{display:none}.message-layout,.grade-columns{grid-template-columns:1fr}.summary-box{width:250px}.dashboard-content,.messages-content,.grades-content{padding:22px 16px}}@media(min-width:901px){.mobile-hamburger{display:none}}
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
  grid-template-columns: repeat(3, minmax(42px, 1fr));
  gap: 4px;
  min-width: 150px;
  padding: 5px;
  border: 1px solid #bbb;
  border-radius: 7px;
  background: #fff;
  box-shadow: 0 4px 10px #0002;
}

.grade-actions .assessment-choices button {
  padding: 5px 6px;
  border: 1px solid #bbb;
  border-radius: 4px;
  background: #f7f7f7;
  color: #222;
  cursor: pointer;
  font-size: 12px;
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

.search-box {
  border-color: #b8cfbf;
  background: #ffffff;
  color: #245638;
}

.search-box input {
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
</style>
