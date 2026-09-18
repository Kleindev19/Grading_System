<template>
  <StudentDashboard v-if="user?.role === 'student'" :user="user" @signout="logout" />
  <ProfessorDashboard v-else-if="user?.role === 'professor'" :user="user" @signout="logout" />
  <RegistarDashboard v-else-if="user?.role === 'registrar'" :user="user" @signout="logout" />

  <main v-else class="auth-page">
    <div class="auth-background-shape auth-background-shape-one" aria-hidden="true"></div>
    <div class="auth-background-shape auth-background-shape-two" aria-hidden="true"></div>
    <section class="auth-card">
      <div class="auth-brand">
        <div class="auth-mark" aria-hidden="true">
          <img :src="colegioLogo" alt="" />
        </div>
        <p class="auth-eyebrow">COLEGIO DE MONTALBAN</p>
        <h1>Academic Management Portal</h1>
        <p class="auth-brand-caption">A secure space for students, professors, and registrar staff.</p>
      </div>

      <div class="auth-tabs" role="tablist" aria-label="Authentication">
        <button type="button" role="tab" :aria-selected="mode === 'login'" :class="{ active: mode === 'login' }" @click="switchMode('login')">Login</button>
        <button type="button" role="tab" :aria-selected="mode === 'register'" :class="{ active: mode === 'register' }" @click="switchMode('register')">Registration</button>
      </div>

      <form v-if="verificationRequired" class="auth-form" @submit.prevent="verifyEmail">
        <h2>Verify your email</h2>
        <p class="auth-caption">Enter the 6-digit code sent to {{ verificationEmail }}.</p>
        <label>
          Verification code
          <input v-model.trim="verificationCode" type="text" inputmode="numeric" pattern="[0-9]{6}" maxlength="6" autocomplete="one-time-code" placeholder="000000" required />
        </label>
        <p v-if="errorMessage" class="auth-error" role="alert">{{ errorMessage }}</p>
        <button class="auth-submit" type="submit" :disabled="loading">{{ loading ? 'Please wait...' : 'Verify email' }}</button>
        <p class="auth-switch"><button type="button" @click="cancelVerification">Back to login</button></p>
      </form>

      <form v-else class="auth-form" @submit.prevent="submitAuth">
        <h2>{{ mode === 'login' ? 'Welcome back' : 'Create an account' }}</h2>
        <p class="auth-caption">
          {{ mode === 'login' ? 'Sign in to access your academic dashboard.' : 'Register an account to get started.' }}
        </p>

        <label v-if="mode === 'register'">
          Full name
          <input v-model.trim="form.name" type="text" autocomplete="name" required />
        </label>
        <label>
          Username
          <span class="input-wrap">
            <svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="8" r="4" /><path d="M4 21a8 8 0 0 1 16 0" /></svg>
            <input v-model.trim="form.username" type="text" autocomplete="username" placeholder="Enter your username" required />
          </span>
        </label>
        <label v-if="mode === 'register'">
          Email address
          <input v-model.trim="form.email" type="email" autocomplete="email" required />
        </label>
        <label v-if="mode === 'register'">
          Account role
          <select v-model="form.role" required>
            <option value="student">Student</option>
            <option value="professor">Professor</option>
            <option value="registrar">Registrar</option>
          </select>
        </label>
        <label v-if="mode === 'register' && form.role === 'student'">
          Student ID
          <input v-model.trim="form.student_id" type="text" required />
        </label>
        <label v-if="mode === 'register' && form.role === 'student'">
          Institute
          <select v-model="form.institute" required>
            <option value="">Select institute</option>
            <option value="ICS">ICS - Institute of Computing Studies</option>
            <option value="IBE">IBE - Institute of Business Entrepreneurship</option>
            <option value="ITE">ITE - Institute of Teachers Education</option>
          </select>
        </label>
        <label v-if="mode === 'register' && form.role === 'student'">
          Course
          <input v-model.trim="form.course" type="text" placeholder="e.g. BSIT" required />
        </label>
        <label v-if="mode === 'register' && form.role === 'student'">
          Year Level
          <select v-model="form.year_level" required>
            <option value="">Select year level</option>
            <option v-for="year in yearLevels" :key="year" :value="year">{{ year }}</option>
          </select>
        </label>
        <label v-if="mode === 'register' && form.role === 'student'">
          Section
          <select v-model="form.section" required>
            <option value="">Select section</option>
            <option v-for="section in sections" :key="section" :value="section">{{ section }}</option>
          </select>
        </label>
        <label>
          Password
          <span class="input-wrap">
            <svg viewBox="0 0 24 24" aria-hidden="true"><rect x="4" y="10" width="16" height="11" rx="2" /><path d="M8 10V7a4 4 0 0 1 8 0v3" /></svg>
            <input v-model="form.password" type="password" :autocomplete="mode === 'login' ? 'current-password' : 'new-password'" placeholder="Enter your password" minlength="8" required />
          </span>
        </label>
        <label v-if="mode === 'register'">
          Confirm password
          <span class="input-wrap">
            <svg viewBox="0 0 24 24" aria-hidden="true"><rect x="4" y="10" width="16" height="11" rx="2" /><path d="M8 10V7a4 4 0 0 1 8 0v3" /></svg>
            <input v-model="form.password_confirmation" type="password" autocomplete="new-password" placeholder="Re-enter your password" minlength="8" required />
          </span>
        </label>

        <p v-if="errorMessage" class="auth-error" role="alert">{{ errorMessage }}</p>
        <button class="auth-submit" type="submit" :disabled="loading">
          {{ loading ? 'Please wait...' : mode === 'login' ? 'Login' : 'Create account' }}
        </button>
        <p class="auth-switch">
          {{ mode === 'login' ? "Don't have an account?" : 'Already have an account?' }}
          <button type="button" @click="switchMode(mode === 'login' ? 'register' : 'login')">
            {{ mode === 'login' ? 'Register here' : 'Login here' }}
          </button>
        </p>
      </form>
    </section>
  </main>
</template>

<script setup>
import { onMounted, reactive, ref } from 'vue'
import { apiUrl, parseApiResponse } from '../services/dataService'
import StudentDashboard from './StudentDashboard.vue'
import ProfessorDashboard from './ProfessorDashboard.vue'
import RegistarDashboard from './RegistarDashboard.vue'
import colegioLogo from '../logo/The_Colegio_de_Montalban_Seal (1).png'
import { jsx } from 'vue/jsx-runtime'

const mode = ref('login')
const loading = ref(false)
const errorMessage = ref('')
const user = ref(null)
const verificationRequired = ref(false)
const verificationEmail = ref('')
const verificationCode = ref('')
const sections = ['A', 'B', 'C', 'D']
const yearLevels = ['1st Year', '2nd Year', '3rd Year', '4th Year']
const form = reactive({
  name: '',
  username: '',
  email: '',
  password: '',
  password_confirmation: '',
  role: 'student',
  student_id: '',
  institute: '',
  course: '',
  year_level: '',
  section: '',
})

function switchMode(nextMode) {
  mode.value = nextMode
  errorMessage.value = ''
  form.password = ''
  form.password_confirmation = ''
}

async function submitAuth() {
  loading.value = true
  errorMessage.value = ''
  const controller = new AbortController()
  const timeout = window.setTimeout(() => controller.abort(), 10000)

  try {
    const response = await fetch(apiUrl(`/${mode.value}`), {
      method: 'POST',
      headers: { 'Content-Type': 'application/json', Accept: 'application/json' },
      body: JSON.stringify(mode.value === 'login'
        ? { username: form.username, password: form.password }
        : form.role === 'student'
          ? form
          : { ...form, student_id: undefined }),
      signal: controller.signal,
    })
    const payload = await parseApiResponse(response)

    if (!response.ok) {
      throw new Error(payload.message || Object.values(payload.errors || {}).flat()[0] || 'Authentication failed.')
    }

    if (payload.requires_verification) {
      verificationRequired.value = true
      verificationEmail.value = payload.email
      return
    }

    completeLogin(payload)
  } catch (error) {
    errorMessage.value = error instanceof DOMException && error.name === 'AbortError'
      ? 'The server took too long to respond. Please try again or use DEV ACCESS.'
      : error instanceof Error ? error.message : 'Unable to connect to the server.'
  } finally {
    window.clearTimeout(timeout)
    loading.value = false
  }
}

async function verifyEmail() {
  loading.value = true
  errorMessage.value = ''
  try {
    const response = await fetch(apiUrl('/verify-email'), {
      method: 'POST',
      headers: { 'Content-Type': 'application/json', Accept: 'application/json' },
      body: JSON.stringify({ email: verificationEmail.value, code: verificationCode.value }),
    })
    const payload = await parseApiResponse(response)
    if (!response.ok) throw new Error(payload.message || 'Unable to verify email.')
    completeLogin(payload)
  } catch (error) {
    errorMessage.value = error instanceof Error ? error.message : 'Unable to verify email.'
  } finally {
    loading.value = false
  }
}

function completeLogin(payload) {
  localStorage.setItem('auth_token', payload.token)
  user.value = payload.user
  verificationRequired.value = false
  verificationEmail.value = ''
  verificationCode.value = ''
  resetForm()
}

function cancelVerification() {
  verificationRequired.value = false
  verificationEmail.value = ''
  verificationCode.value = ''
  errorMessage.value = ''
  switchMode('login')
}

async function restoreSession() {
  const token = localStorage.getItem('auth_token')
  if (!token) return

  try {
    const response = await fetch(apiUrl('/me'), { headers: { Authorization: `Bearer ${token}`, Accept: 'application/json' } })
    if (!response.ok) throw new Error('Session expired.')
    user.value = (await parseApiResponse<{ user: typeof user.value }>(response)).user
  } catch {
    localStorage.removeItem('auth_token')
  }
}

async function logout() {
  const token = localStorage.getItem('auth_token')
  if (token) {
    await fetch(apiUrl('/logout'), { method: 'POST', headers: { Authorization: `Bearer ${token}`, Accept: 'application/json' } }).catch(() => {})
  }
  localStorage.removeItem('auth_token')
  user.value = null
  verificationRequired.value = false
  switchMode('login')
}

function resetForm() {
  form.name = ''
  form.username = ''
  form.email = ''
  form.password = ''
  form.password_confirmation = ''
  form.role = 'student'
  form.student_id = ''
  form.institute = ''
  form.course = ''
  form.year_level = ''
  form.section = ''
}

onMounted(restoreSession)
</script>

<style scoped>
.auth-page {
  position: relative;
  isolation: isolate;
  min-height: 100vh;
  display: grid;
  place-items: center;
  padding: 24px;
  overflow: hidden;
  background: linear-gradient(135deg, #edf5ef 0%, #f8fbf7 52%, #e7f0df 100%);
  color: #173025;
  font-family: 'Trebuchet MS', Arial, Helvetica, sans-serif;
}

.auth-card {
  position: relative;
  z-index: 1;
  width: min(100%, 460px);
  padding: 38px 42px 32px;
  border: 1px solid #d8e5db;
  border-radius: 12px;
  background: #fff;
  box-shadow: 0 24px 70px rgba(25, 67, 42, 0.16);
}

.auth-brand { text-align: center; }
.auth-mark {
  width: 70px;
  height: 70px;
  display: grid;
  place-items: center;
  margin: 0 auto 14px;
  border-radius: 50%;
  background: #f3f8ef;
  border: 4px solid #d9e8c6;
}
.auth-mark img { width: 58px; height: 58px; object-fit: contain; }
.auth-eyebrow { margin: 0; color: #527d35; font-size: 11px; font-weight: 800; letter-spacing: 1.4px; }
.auth-brand h1 { margin: 7px 0 0; color: #173025; font-size: 23px; }
.auth-brand-caption { margin: 8px 0 28px; color: #718177; font-size: 13px; }
.auth-tabs { display: grid; grid-template-columns: 1fr 1fr; margin-bottom: 27px; border-bottom: 1px solid #d9e4dd; }
.auth-tabs button { padding: 12px; border: 0; border-bottom: 3px solid transparent; background: transparent; color: #77847c; cursor: pointer; font: inherit; font-size: 14px; font-weight: 700; }
.auth-tabs button:hover { color: #17663a; background: #f6faf5; }
.auth-tabs button.active { border-bottom-color: #146b3b; color: #146b3b; }
.auth-form h2 { margin: 0; color: #173025; font-size: 22px; }
.auth-caption { margin: 5px 0 20px; color: #6a796f; font-size: 14px; }
.auth-form label { display: grid; gap: 6px; margin-top: 14px; color: #365442; font-size: 13px; font-weight: 700; }
.auth-form input, .auth-form select { width: 100%; padding: 12px; border: 1px solid #cbd9d0; border-radius: 7px; background: #fff; color: #173025; font: inherit; font-size: 14px; }
.auth-form select { cursor: pointer; }
.auth-form input::placeholder { color: #a2ada6; }
.auth-form input:focus, .auth-form select:focus { outline: 2px solid #8fcdb0; outline-offset: 1px; border-color: #4f8b62; }
.input-wrap { position: relative; display: block; }
.input-wrap svg { position: absolute; left: 12px; top: 50%; width: 17px; height: 17px; transform: translateY(-50%); fill: none; stroke: #789181; stroke-linecap: round; stroke-linejoin: round; stroke-width: 1.7; pointer-events: none; }
.input-wrap input { padding-left: 38px; }
.auth-error { margin-top: 16px; color: #b42318; font-size: 13px; }
.auth-submit { width: 100%; margin-top: 22px; padding: 13px; border: 0; border-radius: 7px; background: #17663a; color: #fff; cursor: pointer; font: inherit; font-size: 14px; font-weight: 800; transition: background 0.2s, transform 0.2s; }
.auth-submit:hover:not(:disabled) { background: #0f512c; transform: translateY(-1px); }
.auth-submit:disabled { cursor: wait; opacity: 0.65; }
.auth-submit:focus-visible, .auth-tabs button:focus-visible, .auth-switch button:focus-visible { outline: 3px solid #a8d7bb; outline-offset: 3px; }
.auth-switch { margin: 21px 0 0; color: #77847c; text-align: center; font-size: 13px; }
.auth-switch button { padding: 0; border: 0; background: transparent; color: #17663a; cursor: pointer; font: inherit; font-weight: 800; }
.auth-background-shape { position: absolute; z-index: -1; border-radius: 50%; background: rgba(98, 157, 104, 0.12); }
.auth-background-shape-one { width: 470px; height: 470px; top: -180px; right: -130px; }
.auth-background-shape-two { width: 360px; height: 360px; bottom: -170px; left: -120px; background: rgba(186, 205, 99, 0.2); }

@media (max-width: 520px) {
  .auth-page { padding: 16px; }
  .auth-card { padding: 30px 22px 25px; }
  .auth-brand h1 { font-size: 20px; }
}
</style>
