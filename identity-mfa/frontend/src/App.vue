<template>
  <main>
    <h1>Identity + MFA Admin</h1>
    <section v-if="!stage">
      <form @submit.prevent="login">
        <input v-model="email" placeholder="email" />
        <input v-model="password" placeholder="password" type="password" />
        <button>Login</button>
      </form>
      <p v-if="error">{{ error }}</p>
    </section>
    <section v-else-if="stage==='mfa'">
      <form @submit.prevent="verify">
        <input v-model="code" placeholder="MFA code" />
        <button>Verify</button>
      </form>
      <p v-if="error">{{ error }}</p>
    </section>
    <section v-else>
      <p>Logged in as {{ user?.email }}</p>
      <p class="muted">Passkey / WebAuthn lab routes were removed in Phase 0; use password + TOTP MFA.</p>
    </section>
  </main>
  </template>

<script setup lang="ts">
import api from './http'
import { ref } from 'vue'
const email = ref('admin@example.com')
const password = ref('Admin#123456')
const code = ref('')
const error = ref('')
const stage = ref<null | 'mfa' | 'done'>(null)
const tempToken = ref('')
const user = ref<any>(null)

async function login() {
  error.value = ''
  const { data } = await api.post('/auth/login', { email: email.value, password: password.value })
  if (data.mfa_required) {
    tempToken.value = data.token ?? data.access_token ?? ''
    stage.value = 'mfa'
  } else {
    user.value = data.user
    localStorage.setItem('access_token', data.access_token)
    localStorage.setItem('refresh_token', data.refresh_token)
    stage.value = 'done'
  }
}

async function verify() {
  error.value = ''
  const { data } = await api.post('/auth/mfa/verify', { token: tempToken.value, code: code.value })
  user.value = data.user
  localStorage.setItem('access_token', data.access_token)
  localStorage.setItem('refresh_token', data.refresh_token)
  stage.value = 'done'
}

</script>

<style>
main{max-width:480px;margin:40px auto;font-family:sans-serif}
.muted{color:#666;font-size:0.9rem;margin-top:12px}
input{display:block;margin:8px 0;padding:8px;width:100%}
button{padding:8px 12px}
</style>


