<template>
  <div class="min-h-screen bg-slate-50 flex flex-col md:flex-row font-sans text-slate-900">
    <!-- Sidebar -->
    <!-- Mobile backdrop -->
    <div v-if="sidebarOpen" @click="closeSidebar" class="fixed inset-0 bg-black bg-opacity-40 z-30 md:hidden"></div>

    <aside :class="[
      sidebarOpen ? 'translate-x-0' : '-translate-x-full',
      'fixed inset-y-0 left-0 z-40 w-64 bg-emerald-900 text-white flex flex-col p-4 md:static md:translate-x-0 md:w-64 transition-transform duration-200'
    ]">
      <!-- Gradient footer effect -->
      <div class="absolute inset-x-0 bottom-0 h-48 bg-gradient-to-t from-emerald-700 to-transparent pointer-events-none"></div>
      
      <div class="flex flex-col items-center mb-8 md:mb-10 z-10">
        <div class="w-16 h-16 md:w-20 md:h-20 bg-white rounded-full flex items-center justify-center mb-3 md:mb-4 border-4 border-emerald-800 p-1 overflow-hidden">
          <img :src="colegioLogo" alt="Colegio de Montalban" class="w-full h-full object-contain" />
        </div>
        <h1 class="text-center font-bold text-lg leading-tight">
          <span class="block text-white">COLEGIO DE</span>
          <span class="block text-yellow-300">MONTALBAN</span>
        </h1>
      </div>

      <nav class="flex-grow space-y-2 z-10">
        <button class="w-full flex items-center gap-3 bg-emerald-800 p-3 rounded-xl font-semibold shadow-inner text-sm md:text-base">
          <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"></path></svg>
          Dashboard
        </button>
        <button class="w-full flex items-center gap-3 p-3 rounded-xl hover:bg-emerald-800 transition text-sm md:text-base">
          <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"></path></svg>
          My Subjects
        </button>
      </nav>

      <div class="mt-auto border-t border-emerald-800 pt-4 md:pt-6 z-10">
        <div class="flex items-center gap-3 mb-4">
          <div class="w-10 h-10 bg-emerald-700 rounded-full flex items-center justify-center font-bold text-emerald-100">JA</div>
          <div>
            <p class="font-bold text-sm md:text-base">Junas Arroyo</p>
            <p class="text-[10px] md:text-xs text-emerald-300">STUDENT</p>
          </div>
        </div>
        <button @click="emitSignOut" class="flex items-center gap-2 text-xs md:text-sm text-emerald-300 hover:text-white">
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"></path></svg>
          Sign out
        </button>
      </div>
    </aside>

    <!-- Main Content -->
    <main class="flex-grow p-4 md:p-8">
      <!-- Mobile top bar -->
      <div class="md:hidden flex items-center justify-between bg-white border-b">
        <button @click="toggleSidebar" class="p-3 focus:outline-none">
          <svg class="w-6 h-6 text-emerald-900" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path></svg>
        </button>
        <div class="py-3 font-bold text-emerald-900">COLEGIO DE MONTALBAN</div>
        <div class="w-10"></div>
      </div>
      <header class="mb-8">
        <h2 class="text-xl md:text-2xl font-bold text-slate-800">GRADES</h2>
        <p class="text-slate-500 text-sm md:text-sm">View your officially published academic grades.</p>
      </header>

      <!-- Stat Cards -->
      <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6 md:mb-8">
        <div class="bg-emerald-900 text-white p-4 md:p-6 rounded-2xl shadow-lg">
          <p class="text-[10px] opacity-80 uppercase tracking-wide">General Weighted Average</p>
          <p class="text-2xl md:text-3xl font-bold mt-1">1.59</p>
        </div>
        <div class="bg-white p-4 md:p-6 rounded-2xl border border-slate-100 shadow-sm flex items-center gap-4">
          <div class="p-3 bg-emerald-50 text-emerald-700 rounded-xl"><svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"></path></svg></div>
          <div>
            <p class="text-[10px] text-slate-500 uppercase">Enrolled Subjects</p>
            <p class="text-lg md:text-xl font-bold">8</p>
          </div>
        </div>
        <div class="bg-white p-4 md:p-6 rounded-2xl border border-slate-100 shadow-sm flex items-center gap-4">
          <div class="p-3 bg-emerald-50 text-emerald-700 rounded-xl"><svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg></div>
          <div>
            <p class="text-[10px] text-slate-500 uppercase">Total Units</p>
            <p class="text-lg md:text-xl font-bold">24</p>
          </div>
        </div>
      </div>

      <!-- Published Grades Table -->
      <div class="bg-white rounded-2xl border border-slate-100 shadow-sm">
        <div class="p-6 border-b flex justify-between items-center">
          <h3 class="font-bold text-md text-slate-800">PUBLISHED GRADES</h3>
          <select v-model="selectedSemester" class="border border-slate-200 rounded-lg p-2 text-sm bg-slate-50 text-slate-700 focus:ring-2 focus:ring-emerald-500">
            <option value="1">1st Semester 2025-2026</option>
            <option value="2">2nd Semester 2025-2026</option>
          </select>
        </div>
        <div class="overflow-x-auto">
          <table class="w-full text-sm">
            <thead class="bg-slate-50 text-slate-500 text-[10px] uppercase tracking-wider font-bold">
              <tr>
                <th class="p-4 text-left">Subject Code</th>
                <th class="p-4 text-left">Description</th>
                <th class="p-4 text-left">Professor</th>
                <th class="p-4 text-center">Midterm</th>
                <th class="p-4 text-center">Finals</th>
                <th class="p-4 text-center">Average</th>
                <th class="p-4 text-center">Grade</th>
                <th class="p-4 text-center">Remarks</th>
                <th class="p-4 text-center">Action</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
              <tr v-for="sub in subjects" :key="sub.code" class="hover:bg-slate-50 transition">
                <td class="p-4 font-bold text-slate-900">{{ sub.code }}</td>
                <td class="p-4 text-slate-600">{{ sub.desc }}</td>
                <td class="p-4 text-slate-600">{{ sub.prof }}</td>
                <td class="p-4 text-center text-slate-700">{{ sub.mid }}</td>
                <td class="p-4 text-center text-slate-700">{{ sub.fin }}</td>
                <td class="p-4 text-center font-bold text-emerald-800">{{ sub.avg }}</td>
                <td class="p-4 text-center font-bold text-slate-900">{{ sub.grade }}</td>
                <td class="p-4 text-center">
                  <span class="px-2.5 py-1 bg-emerald-50 text-emerald-700 rounded-full text-[10px] font-bold border border-emerald-100">{{ sub.rem }}</span>
                </td>
                <!-- Message Icon per row -->
                <td class="p-4 text-center">
                  <button
                    @click="openChat(sub)"
                    title="Message professor about this subject"
                    class="inline-flex items-center justify-center w-9 h-9 rounded-full hover:bg-emerald-50 transition"
                  >
                    <svg width="22" height="22" fill="none" stroke="#059669" stroke-width="2" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                      <path stroke-linecap="round" stroke-linejoin="round" d="M8 10h.01M12 10h.01M16 10h.01M21 12c0 4.418-4.03 8-9 8a9.77 9.77 0 01-4-.84L3 20l1.09-3.27C3.4 15.5 3 13.8 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/>
                    </svg>
                  </button>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </main>

    <!-- Chat Box -->
    <div
      v-if="activeChatSubject"
      style="position:fixed; bottom:28px; right:28px; z-index:9999; width:320px; background:#fff; border-radius:16px; box-shadow:0 8px 32px rgba(0,0,0,0.18); display:flex; flex-direction:column; overflow:hidden; border:1px solid #d1fae5;"
    >
      <!-- Chat Header -->
      <div style="background:#059669; padding:14px 16px; display:flex; align-items:center; justify-content:space-between;">
        <div style="display:flex; flex-direction:column;">
          <span style="color:white; font-weight:700; font-size:14px;">{{ activeChatSubject.prof }}</span>
          <span style="color:#d1fae5; font-size:11px;">{{ activeChatSubject.code }} — {{ activeChatSubject.desc }}</span>
        </div>
        <button @click="activeChatSubject = null" style="background:none; border:none; cursor:pointer; color:white; font-size:20px; line-height:1; padding:0;">&#x2715;</button>
      </div>

      <!-- Messages Area -->
      <div
        ref="chatMessagesRef"
        style="flex:1; min-height:200px; max-height:260px; overflow-y:auto; padding:14px; background:#f8fafb; display:flex; flex-direction:column; gap:8px;"
      >
        <div style="text-align:center; color:#6b7280; font-size:12px; margin-top:8px;">
          Send a complaint or concern to <strong>{{ activeChatSubject.prof }}</strong> about your grade in <strong>{{ activeChatSubject.desc }}</strong>.
        </div>
        <div
          v-for="(msg, idx) in chatMessages[activeChatSubject.code] || []"
          :key="idx"
          :style="{
            alignSelf: 'flex-end',
            background: '#059669',
            color: 'white',
            borderRadius: '12px',
            padding: '8px 12px',
            maxWidth: '85%',
            fontSize: '13px',
            wordBreak: 'break-word'
          }"
        >{{ msg }}</div>
      </div>

      <!-- Input Bar -->
      <div style="display:flex; align-items:center; gap:6px; background:#d1fae5; padding:8px 10px; flex-wrap:nowrap; overflow:hidden;">
        <!-- Plus -->
        <button title="Add" style="background:none; border:none; cursor:pointer; color:#059669; display:flex; align-items:center; justify-content:center; padding:2px;">
          <svg width="20" height="20" fill="none" stroke="#059669" stroke-width="2.2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 5v14M5 12h14"/></svg>
        </button>
        <!-- Mic -->
        <button title="Voice" style="background:none; border:none; cursor:pointer; color:#059669; display:flex; align-items:center; justify-content:center; padding:2px;">
          <svg width="20" height="20" fill="none" stroke="#059669" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 1a3 3 0 00-3 3v8a3 3 0 006 0V4a3 3 0 00-3-3z"/><path stroke-linecap="round" stroke-linejoin="round" d="M19 10v2a7 7 0 01-14 0v-2M12 19v4M8 23h8"/></svg>
        </button>
        <!-- Image -->
        <button title="Image" style="background:none; border:none; cursor:pointer; color:#059669; display:flex; align-items:center; justify-content:center; padding:2px;">
          <svg width="20" height="20" fill="none" stroke="#059669" stroke-width="2" viewBox="0 0 24 24"><rect x="3" y="3" width="18" height="18" rx="2"/><path stroke-linecap="round" stroke-linejoin="round" d="M3 15l5-5 4 4 3-3 6 6"/><circle cx="8.5" cy="8.5" r="1.5" fill="#059669" stroke="none"/></svg>
        </button>
        <!-- Text Input -->
        <input
          v-model="chatInput"
          @keyup.enter="sendMessage"
          type="text"
          placeholder="Message"
          style="flex:1; min-width:0; border:none; border-radius:20px; padding:7px 12px; font-size:13px; outline:none; background:#fff; color:#1f2937;"
        />
        <!-- Emoji -->
        <button title="Emoji" style="background:none; border:none; cursor:pointer; display:flex; align-items:center; justify-content:center; padding:2px;">
          <svg width="20" height="20" fill="none" stroke="#059669" stroke-width="2" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><path stroke-linecap="round" d="M8 14s1.5 2 4 2 4-2 4-2"/><line x1="9" y1="9" x2="9.01" y2="9" stroke-linecap="round" stroke-width="3"/><line x1="15" y1="9" x2="15.01" y2="9" stroke-linecap="round" stroke-width="3"/></svg>
        </button>
        <!-- Send -->
        <button
          @click="sendMessage"
          title="Send"
          style="background:#059669; border:none; cursor:pointer; display:flex; align-items:center; justify-content:center; width:34px; height:34px; border-radius:50%; flex-shrink:0;"
        >
          <svg width="16" height="16" fill="none" stroke="white" stroke-width="2" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" d="M22 2L11 13M22 2L15 22l-4-9-9-4 20-7z"/>
          </svg>
        </button>
      </div>
    </div>

  </div>
</template>

<script setup>
import { ref, nextTick } from 'vue';
import colegioLogo from '../logo/The_Colegio_de_Montalban_Seal (1).png';

const emit = defineEmits(['signout']);

function emitSignOut() {
  emit('signout');
}

const selectedSemester = ref('1');

const sidebarOpen = ref(false);

function toggleSidebar() {
  sidebarOpen.value = !sidebarOpen.value;
}

function closeSidebar() {
  sidebarOpen.value = false;
}

const subjects = [
  { code: 'ITNETW2', desc: 'Networking 2', prof: 'Prof. Junas Arroyo', mid: 88, fin: 92, avg: 90.4, grade: '1.50', rem: 'PASSED' },
  { code: 'ITINFOM', desc: 'Information Management', prof: 'Prof. Junas Arroyo', mid: 95, fin: 93, avg: 93.8, grade: '1.25', rem: 'PASSED' },
  { code: 'ITAPPSD', desc: 'App Dev and Emerging Tech', prof: 'Prof. Junas Arroyo', mid: 85, fin: 87, avg: 86.2, grade: '2.00', rem: 'PASSED' },
  { code: 'ITIASE1', desc: 'Information Assurance', prof: 'Prof. Junas Arroyo', mid: 90, fin: 90, avg: 90.0, grade: '1.50', rem: 'PASSED' },
  { code: 'ITHUMCI', desc: 'Human Computer Interaction', prof: 'Prof. Junas Arroyo', mid: 98, fin: 96, avg: 96.8, grade: '1.00', rem: 'PASSED' },
];

// Chat feature
const activeChatSubject = ref(null);
const chatInput = ref('');
const chatMessages = ref({});
const chatMessagesRef = ref(null);

function openChat(sub) {
  activeChatSubject.value = sub;
  chatInput.value = '';
  if (!chatMessages.value[sub.code]) {
    chatMessages.value[sub.code] = [];
  }
}

function sendMessage() {
  const text = chatInput.value.trim();
  if (!text || !activeChatSubject.value) return;
  const code = activeChatSubject.value.code;
  if (!chatMessages.value[code]) chatMessages.value[code] = [];
  chatMessages.value[code].push(text);
  chatInput.value = '';
  nextTick(() => {
    if (chatMessagesRef.value) {
      chatMessagesRef.value.scrollTop = chatMessagesRef.value.scrollHeight;
    }
  });
}
</script>