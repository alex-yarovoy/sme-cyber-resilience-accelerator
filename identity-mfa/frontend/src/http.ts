import axios from 'axios'

const api = axios.create({ baseURL: '/api' })

let refreshing = false
let pending: Array<() => void> = []

api.interceptors.response.use(
  r => r,
  async err => {
    const original = err.config
    if (err.response?.status === 401 && !original.__retry) {
      original.__retry = true
      if (!refreshing) {
        refreshing = true
        try {
          const rt = localStorage.getItem('refresh_token')
          if (rt) {
            const { data } = await api.post('/auth/refresh', { refresh_token: rt })
            localStorage.setItem('access_token', data.access_token)
            localStorage.setItem('refresh_token', data.refresh_token)
            pending.forEach(fn => fn())
            pending = []
          }
        } finally {
          refreshing = false
        }
      }
      await new Promise<void>(resolve => pending.push(resolve))
      original.headers = original.headers || {}
      original.headers['Authorization'] = 'Bearer ' + localStorage.getItem('access_token')
      return api(original)
    }
    return Promise.reject(err)
  }
)

api.interceptors.request.use(cfg => {
  const at = localStorage.getItem('access_token')
  if (at) {
    cfg.headers = cfg.headers || {}
    cfg.headers['Authorization'] = 'Bearer ' + at
  }
  return cfg
})

export default api



