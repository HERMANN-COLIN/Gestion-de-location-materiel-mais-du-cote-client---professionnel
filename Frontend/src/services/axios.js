import axios from 'axios'

const api = axios.create({
  baseURL: 'http://127.0.0.1:8000/api',
  headers: {
    'Content-Type': 'application/json',
    'Accept': 'application/json',
  },
  withCredentials: true  // ← IMPORTANT pour CORS
})

// Intercepteur pour ajouter le token
api.interceptors.request.use(
  (config) => {
    const token = localStorage.getItem('token')
    if (token) {
      config.headers.Authorization = `Bearer ${token}`
    }
    console.log('🔵 Request:', {
      method: config.method,
      url: config.url,
      data: config.data
    })
    return config
  },
  (error) => {
    console.error('🔴 Request Error:', error)
    return Promise.reject(error)
  }
)

// Intercepteur pour logger les réponses
api.interceptors.response.use(
  (response) => {
    console.log('🟢 Response:', {
      status: response.status,
      data: response.data
    })
    return response
  },
  (error) => {
    console.error('🔴 Response Error:', {
      status: error.response?.status,
      data: error.response?.data,
      message: error.message
    })
    return Promise.reject(error)
  }
)

export default api