<script setup lang="ts">
import HelloWorld from './widgets/LoginandRegistration.vue'
import ProfessorDashboard from './widgets/ProfessorDashboard.vue'
const params = new URLSearchParams(window.location.search)
const debugProf = params.get('prof') === '1'
</script>

<template>
  <ProfessorDashboard v-if="debugProf" />
  <HelloWorld v-else />
</template>
