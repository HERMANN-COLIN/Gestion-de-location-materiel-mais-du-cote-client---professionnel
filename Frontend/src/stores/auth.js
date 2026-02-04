import { defineStore } from 'pinia'
import axios from 'axios'

// Créez une instance axios avec la base URL correcte
const api = axios.create({
  baseURL: 'http://localhost:8000/api',
  headers: {
    'Content-Type': 'application/json',
    'Accept': 'application/json'
  }
})

export const useAuthStore = defineStore('auth', {
  state: () => ({
    user: null,
    token: localStorage.getItem('token') || null,
    loading: false,
    error: null
  }),

  getters: {
    isAuthenticated: (state) => !!state.token,
    getUser: (state) => state.user,
    getLoading: (state) => state.loading,
    getError: (state) => state.error
  },

  actions: {
    async register(formData) {
      try {
        this.loading = true
        this.error = null
        
        console.log('📤 Sending registration data:', formData)
        
        const response = await api.post('/register', formData)
        
        console.log('✅ Registration response:', response.data)
        
        if (response.data.token) {
          this.token = response.data.token
          this.user = response.data.user
          localStorage.setItem('token', this.token)
          
          // Configure axios pour les futures requêtes
          api.defaults.headers.common['Authorization'] = `Bearer ${this.token}`
        }
        
        return { success: true, data: response.data }
      } catch (error) {
        console.error('❌ Register error:', error)
        this.error = error.response?.data?.message || error.message
        
        // Si c'est une erreur de validation, retournez les erreurs
        if (error.response?.status === 422) {
          return { 
            success: false, 
            error: this.error,
            validationErrors: error.response?.data?.errors
          }
        }
        
        return { 
          success: false, 
          error: this.error
        }
      } finally {
        this.loading = false
      }
    },

    async login(credentials) {
      try {
        this.loading = true
        this.error = null
        
        console.log('📤 Sending login data:', credentials)
        
        const response = await api.post('/login', credentials)
        
        console.log('✅ Login response:', response.data)
        
        if (response.data.token) {
          this.token = response.data.token
          this.user = response.data.user
          localStorage.setItem('token', this.token)
          
          // Configure axios pour les futures requêtes
          api.defaults.headers.common['Authorization'] = `Bearer ${this.token}`
        }
        
        return { success: true, data: response.data }
      } catch (error) {
        console.error('❌ Login error:', error)
        this.error = error.response?.data?.message || error.message
        return { success: false, error: this.error }
      } finally {
        this.loading = false
      }
    },

    async logout() {
      try {
        await api.post('/logout')
      } catch (error) {
        console.error('Logout error:', error)
      } finally {
        this.token = null
        this.user = null
        localStorage.removeItem('token')
        delete api.defaults.headers.common['Authorization']
      }
    },

    async fetchUser() {
      try {
        if (!this.token) return
        
        const response = await api.get('/user')
        this.user = response.data
      } catch (error) {
        console.error('Fetch user error:', error)
        this.token = null
        this.user = null
        localStorage.removeItem('token')
        delete api.defaults.headers.common['Authorization']
      }
    },

    clearError() {
      this.error = null
    }
  }
})