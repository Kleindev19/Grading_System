<template>
  <div>
    <StudentDashboard v-if="showStudentDashboard" @signout="goBack" />
    <ProfessorDashboard v-else-if="showProfessorDashboard" @signout="goBack" @submitted="goToRegistrar" />
    <RegistarDashboard v-else-if="showRegistarDashboard" @signout="goBack" />

    <div v-else class="min-h-screen bg-emerald-200 flex flex-col items-center justify-center p-4 font-sans text-slate-900">
      <!-- Header Section -->
      <div class="text-center mb-10">
        <div class="w-16 h-16 bg-emerald-600 rounded-2xl flex items-center justify-center text-white mx-auto mb-4 shadow-lg">
          <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 14l9-5-9-5-9 5 9 5z"></path>
          </svg>
        </div>
        <h1 class="text-3xl font-bold text-slate-900 tracking-tight">Colegio de Montalban</h1>
        <p class="text-emerald-900 font-medium">Academic Management Portal</p>
      </div>

      <!-- Role Selection Card -->
      <div class="w-full max-w-lg bg-white rounded-3xl shadow-xl p-8 border border-slate-100">
        <h2 class="text-lg font-semibold mb-6">Select your role to continue</h2>
        
        <div class="space-y-4">
          <button
            v-for="role in roles"
            :key="role.id"
            @click="handleRoleClick(role.id)"
            :class="[
              'w-full flex items-center gap-4 p-4 rounded-2xl border transition-all duration-200 text-left',
              selectedRole === role.id 
                ? 'border-emerald-500 bg-emerald-50' 
                : 'border-slate-100 hover:border-emerald-200 hover:bg-slate-50'
            ]"
          >
            <div :class="['p-3 rounded-xl', selectedRole === role.id ? 'bg-emerald-100 text-emerald-700' : 'bg-slate-100 text-slate-600']">
              <!-- Icon SVG -->
              <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
              </svg>
            </div>
            <div class="flex-grow">
              <h3 class="font-bold text-slate-900">{{ role.title }}</h3>
              <p class="text-sm text-slate-500">{{ role.desc }}</p>
            </div>
            <div :class="[selectedRole === role.id ? 'text-emerald-600' : 'text-slate-300']">
              <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path></svg>
            </div>
          </button>
        </div>

        <p class="mt-8 text-center text-xs text-slate-400">
          For demonstration purposes only.<br />
          No real credentials required.
        </p>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue';
import { getRoles } from '../services/dataService'
import StudentDashboard from './StudentDashboard.vue';
import ProfessorDashboard from './ProfessorDashboard.vue';
import RegistarDashboard from './RegistarDashboard.vue';

const selectedRole = ref(null);
const showStudentDashboard = ref(false);
const showProfessorDashboard = ref(false);
const showRegistarDashboard = ref(false);

const roles = getRoles()

function handleRoleClick(role) {
  selectedRole.value = role;
  console.log('Role clicked:', role)
  // Immediately open the student dashboard for demo student
  if (role === 'student') {
    showStudentDashboard.value = true;
  }
  if (role === 'professor') {
    showProfessorDashboard.value = true;
  }
  if (role === 'registrar') {
    showRegistarDashboard.value = true;
  }
}

function goBack() {
  showStudentDashboard.value = false;
  showProfessorDashboard.value = false;
  showRegistarDashboard.value = false;
  selectedRole.value = null;
}

function goToRegistrar() {
  showProfessorDashboard.value = false;
  showRegistarDashboard.value = true;
}
</script>
