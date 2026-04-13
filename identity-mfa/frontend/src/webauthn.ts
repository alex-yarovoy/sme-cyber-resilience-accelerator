import api from './http'

export async function registerPasskey() {
  const { data: options } = await api.post('/webauthn/options/register')
  // navigator.credentials.create expects ArrayBuffers; conversion omitted for brevity in PoC
  // After creation, send back to /webauthn/register
}

export async function loginPasskey() {
  const { data: options } = await api.post('/webauthn/options/login')
  // navigator.credentials.get(...)
}



