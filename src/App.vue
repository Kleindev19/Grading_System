<script setup lang="ts">
import HelloWorld from './widgets/LoginandRegistration.vue'
import ProfessorDashboard from './widgets/ProfessorDashboard.vue'
import StudentDashboard from './widgets/StudentDashboard.vue'
import RegistarDashboard from './widgets/RegistarDashboard.vue'
import { ref } from 'vue'
import { apiUrl, parseApiResponse } from './services/dataService'

const isDev = import.meta.env.DEV
const devRole = ref<'student' | 'professor' | 'registrar' | null>(null)
const devLoading = ref(false)
const devUser = {
  student: { name: 'Mock Student', role: 'student', username: '23-00001' },
  professor: { name: 'Mock Professor', role: 'professor', username: 'mock.professor' },
  registrar: { name: 'Mock Registrar', role: 'registrar', username: 'mock.registrar' },
}

async function openDevDashboard(role: 'student' | 'professor' | 'registrar') {
  devLoading.value = true
  try {
    const response = await fetch(apiUrl('/login'), {
      method: 'POST',
      headers: { 'Content-Type': 'application/json', Accept: 'application/json' },
      body: JSON.stringify({ username: devUser[role].username, password: 'Password123!' }),
    })
    const payload = await parseApiResponse<{ token: string; user: typeof devUser[typeof role]; message?: string }>(response)
    if (!response.ok) throw new Error(payload.message || 'Unable to open development dashboard.')
    localStorage.setItem('auth_token', payload.token)
    devRole.value = role
  } catch (error) {
    localStorage.removeItem('auth_token')
    devRole.value = role
  } finally {
    devLoading.value = false
  }
}

function closeDevDashboard() {
  devRole.value = null
}
</script>

<template>
  <StudentDashboard v-if="devRole === 'student'" :user="devUser.student" @signout="closeDevDashboard" />
  <ProfessorDashboard v-else-if="devRole === 'professor'" :user="devUser.professor" @signout="closeDevDashboard" />
  <RegistarDashboard v-else-if="devRole === 'registrar'" :user="devUser.registrar" @signout="closeDevDashboard" />
  <div v-else class="app-shell">
    <HelloWorld />
    <aside v-if="isDev" class="dev-access" aria-label="Mock role access">
      <strong>MOCK ACCESS</strong>
      <span>Open the system by role without manual login</span>
      <button type="button" :disabled="devLoading" @click="openDevDashboard('student')">Student</button>
      <button type="button" :disabled="devLoading" @click="openDevDashboard('professor')">Professor</button>
      <button type="button" :disabled="devLoading" @click="openDevDashboard('registrar')">Registrar</button>
    </aside>
  </div>
</template>

<style scoped>
.app-shell {
  min-height: 100vh;
}

.dev-access {
  position: fixed;
  right: 18px;
  bottom: 18px;
  z-index: 100;
  display: grid;
  gap: 8px;
  width: 220px;
  padding: 14px;
  border: 1px solid #e0b34d;
  border-radius: 10px;
  background: #fff9e8;
  box-shadow: 0 8px 24px #0002;
  color: #49380e;
  font: 13px Arial, sans-serif;
}

.dev-access strong {
  font-size: 12px;
  letter-spacing: 0.08em;
}

.dev-access span {
  font-size: 12px;
  line-height: 1.3;
}

.dev-access button {
  padding: 8px 10px;
  border: 1px solid #b88720;
  border-radius: 6px;
  background: #fff;
  color: #49380e;
  cursor: pointer;
  font-weight: 700;
}

.dev-access button:hover {
  background: #fff0bd;
}
</style>
