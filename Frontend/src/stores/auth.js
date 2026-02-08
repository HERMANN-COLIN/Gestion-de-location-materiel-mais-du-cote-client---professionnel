import { defineStore } from 'pinia'
import api from '@/services/axios'

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
        }

        return { success: true, data: response.data }
      } catch (error) {
        console.error('❌ Register error:', error)

        this.error =
          error.response?.data?.message ||
          'Erreur lors de l’inscription'

       if (error.response?.status === 422) {
  console.log('🧨 Erreurs de validation Laravel:', error.response.data.errors)

  return {
    success: false,
    error: this.error,
    validationErrors: error.response.data.errors
  }
}


        return { success: false, error: this.error }
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
        }

        return { success: true, data: response.data }
      } catch (error) {
        console.error('❌ Login error:', error)

        this.error =
          error.response?.data?.message ||
          'Identifiants incorrects'

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
      }
    },

    clearError() {
      this.error = null
    }
  }
})
