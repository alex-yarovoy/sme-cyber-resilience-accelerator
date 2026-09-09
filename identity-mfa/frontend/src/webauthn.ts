import api from './http'

function coerceToArrayBuffer(value: unknown): ArrayBuffer | unknown {
  if (value instanceof ArrayBuffer) {
    return value
  }
  if (ArrayBuffer.isView(value)) {
    return value.buffer.slice(value.byteOffset, value.byteOffset + value.byteLength)
  }
  if (typeof value === 'string') {
    const binary = atob(value.replace(/-/g, '+').replace(/_/g, '/'))
    const bytes = new Uint8Array(binary.length)
    for (let i = 0; i < binary.length; i += 1) {
      bytes[i] = binary.charCodeAt(i)
    }
    return bytes.buffer
  }
  return value
}

function preparePublicKeyOptions(options: Record<string, unknown>): PublicKeyCredentialCreationOptions {
  const publicKey = { ...(options.publicKey as Record<string, unknown> ?? options) }
  if (publicKey.challenge) {
    publicKey.challenge = coerceToArrayBuffer(publicKey.challenge)
  }
  if (Array.isArray(publicKey.excludeCredentials)) {
    publicKey.excludeCredentials = publicKey.excludeCredentials.map((cred: Record<string, unknown>) => ({
      ...cred,
      id: coerceToArrayBuffer(cred.id),
    }))
  }
  return publicKey as PublicKeyCredentialCreationOptions
}

function prepareRequestOptions(options: Record<string, unknown>): PublicKeyCredentialRequestOptions {
  const publicKey = { ...(options.publicKey as Record<string, unknown> ?? options) }
  if (publicKey.challenge) {
    publicKey.challenge = coerceToArrayBuffer(publicKey.challenge)
  }
  if (Array.isArray(publicKey.allowCredentials)) {
    publicKey.allowCredentials = publicKey.allowCredentials.map((cred: Record<string, unknown>) => ({
      ...cred,
      id: coerceToArrayBuffer(cred.id),
    }))
  }
  return publicKey as PublicKeyCredentialRequestOptions
}

export async function registerPasskey(): Promise<unknown> {
  const { data: options } = await api.post('/webauthn/options/register')
  const credential = await navigator.credentials.create({
    publicKey: preparePublicKeyOptions(options),
  })
  const { data: result } = await api.post('/webauthn/register', credential)
  return result
}

export async function loginPasskey(): Promise<unknown> {
  const { data: options } = await api.post('/webauthn/options/login')
  const assertion = await navigator.credentials.get({
    publicKey: prepareRequestOptions(options),
  })
  const { data: result } = await api.post('/webauthn/login', assertion)
  return result
}
