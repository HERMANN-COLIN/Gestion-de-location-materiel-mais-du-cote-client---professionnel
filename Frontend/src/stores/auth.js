import { defineStore } from 'pinia'
import api from '@/services/axios'

// 🔧 Fonction utilitaire pour lire le localStorage en toute sécurité
const getStoredUser = () => {
  try {
    const storedUser = localStorage.getItem('user')
    // Si c'est null, undefined, ou "undefined", on retourne null
    if (!storedUser || storedUser === 'undefined' || storedUser === 'null') {
      return null
    }
    return JSON.parse(storedUser)
  } catch (error) {
    console.error('Erreur parsing user depuis localStorage:', error)
    return null
  }
}

export const useAuthStore = defineStore('auth', {
  state: () => ({
    // Utilisation de la fonction sécurisée
    user: getStoredUser(),
    token: localStorage.getItem('token') || null,
    loading: false,
    error: null
  }),

  getters: {
    isAuthenticated: (state) => !!state.token,
    isAdmin: (state) => state.user?.type_id === 3 || state.user?.type?.id === 3,
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
          this.user = response.data.data
          
          localStorage.setItem('token', this.token)
          // On ne sauvegarde que si user est bien défini
          if (this.user) {
            localStorage.setItem('user', JSON.stringify(this.user))
          }
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

        const response = await api.post('/login', credentials)

        if (response.data.token) {
          this.token = response.data.token
          this.user = response.data.data
          
          localStorage.setItem('token', this.token)
          // On ne sauvegarde que si user est bien défini
          if (this.user) {
            localStorage.setItem('user', JSON.stringify(this.user))
          }
        }

        return { success: true, data: response.data }
      } catch (error) {
        console.error('❌ Login error:', error)

        this.error = error.response?.data?.message || 'Identifiants incorrects'

        if (error.response?.status === 422) {
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

    async logout() {
      try {
        await api.post('/logout')
      } catch (error) {
        console.error('Logout error:', error)
      } finally {
        this.token = null
        this.user = null
        localStorage.removeItem('token')
        localStorage.removeItem('user')
      }
    },

    async fetchUser() {
      try {
        if (!this.token) return

        const response = await api.get('/user')
        this.user = response.data.data
        
        if (this.user) {
          localStorage.setItem('user', JSON.stringify(this.user))
        }
      } catch (error) {
        console.error('Fetch user error:', error)

        this.token = null
        this.user = null
        localStorage.removeItem('token')
        localStorage.removeItem('user')
      }
    },

    clearError() {
      this.error = null
    }
  }
})