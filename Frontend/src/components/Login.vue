<template>
  <div class="login-container">
    <div class="login-background">
      <div class="bg-shape shape-1"></div>
      <div class="bg-shape shape-2"></div>
      <div class="bg-shape shape-3"></div>
    </div>
    
    <div class="login-card animate-fade-in">
      <!-- Header -->
      <div class="login-header">
        <div class="login-icon">🔐</div>
        <h1 class="login-title">Connexion</h1>
        <p class="login-subtitle">Accédez à votre espace personnel</p>
      </div>
      
      <!-- Form -->
      <form @submit.prevent="handleLogin" class="login-form">
        <!-- Email -->
        <div class="form-group">
          <div class="input-wrapper">
            <input
              v-model="form.email"
              type="email"
              required
              placeholder=" "
              class="form-input"
              :class="{ 'error': errors.email }"
            />
            <label class="form-label">Adresse email</label>
            <div class="input-icon">📧</div>
          </div>
          <p v-if="errors.email" class="error-message">{{ errors.email[0] }}</p>
        </div>
        
        <!-- Password -->
        <div class="form-group">
          <div class="input-wrapper">
            <input
              v-model="form.password"
              :type="showPassword ? 'text' : 'password'"
              required
              placeholder=" "
              class="form-input"
              :class="{ 'error': errors.password }"
            />
            <label class="form-label">Mot de passe</label>
            <div class="input-icon">🔒</div>
            <button 
              type="button" 
              @click="showPassword = !showPassword"
              class="password-toggle"
            >
              {{ showPassword ? '🙈' : '👁️' }}
            </button>
          </div>
          <p v-if="errors.password" class="error-message">{{ errors.password[0] }}</p>
        </div>
        
        <!-- Options -->
        <div class="form-options">
          <label class="checkbox-container">
            <input v-model="form.remember" type="checkbox" />
            <span class="checkmark"></span>
            <span class="checkbox-label">Se souvenir de moi</span>
          </label>
          <router-link to="/forgot-password" class="forgot-link">
            Mot de passe oublié ?
          </router-link>
        </div>
        
        <!-- Error Message -->
        <div v-if="errorMessage" class="error-card">
          <div class="error-icon">⚠️</div>
          <div class="error-content">
            <h3 class="error-title">Erreur de connexion</h3>
            <p class="error-text">{{ errorMessage }}</p>
          </div>
        </div>
        
        <!-- Submit Button -->
        <button 
          type="submit" 
          :disabled="loading"
          class="submit-btn"
          :class="{ 'loading': loading }"
        >
          <span v-if="!loading">Se connecter</span>
          <span v-else class="loading-spinner"></span>
        </button>
        
        <!-- Divider -->
        <div class="divider">
          <span class="divider-text">ou</span>
        </div>
        
        <!-- Social Login -->
        <div class="social-login">
          <button type="button" class="social-btn google-btn">
            <span class="social-icon">G</span>
            Continuer avec Google
          </button>
        </div>
      </form>
      
      <!-- Footer -->
      <div class="login-footer">
        <p class="footer-text">
          Nouveau sur LocationMatériel ? 
          <router-link to="/register" class="footer-link">
            Créer un compte
          </router-link>
        </p>
        <router-link to="/" class="back-link">
          ← Retour à l'accueil
        </router-link>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, reactive } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '../stores/auth'

const router = useRouter()
const authStore = useAuthStore()

const loading = ref(false)
const errorMessage = ref('')
const errors = ref({})
const showPassword = ref(false)

const form = reactive({
  email: '',
  password: '',
  remember: false
})

const handleLogin = async () => {
  loading.value = true
  errorMessage.value = ''
  errors.value = {}

  try {
    const result = await authStore.login({
      email: form.email,
      password: form.password
    })

    if (result.success) {
      router.push('/')
    } else {
      errorMessage.value = result.error || 'Erreur de connexion'
    }
  } catch (error) {
    if (error.response?.status === 422) {
      errors.value = error.response.data.errors
      errorMessage.value = 'Veuillez corriger les erreurs.'
    } else {
      errorMessage.value = 'Une erreur est survenue. Veuillez réessayer.'
    }
  } finally {
    loading.value = false
  }
}
</script>

<style scoped>
/* ===== LOGIN STYLES ===== */
.login-container {
  min-height: 100vh;
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 2rem;
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  position: relative;
  overflow: hidden;
}

.login-background {
  position: absolute;
  inset: 0;
  z-index: 1;
}

.bg-shape {
  position: absolute;
  border-radius: 50%;
  background: rgba(255, 255, 255, 0.1);
}

.shape-1 {
  width: 400px;
  height: 400px;
  top: -200px;
  right: -200px;
  animation: float 6s ease-in-out infinite;
}

.shape-2 {
  width: 300px;
  height: 300px;
  bottom: -150px;
  left: -150px;
  animation: float 8s ease-in-out infinite reverse;
}

.shape-3 {
  width: 200px;
  height: 200px;
  top: 50%;
  left: 80%;
  animation: float 10s ease-in-out infinite;
}

/* Login Card */
.login-card {
  position: relative;
  z-index: 2;
  background: white;
  border-radius: 24px;
  padding: 3rem;
  width: 100%;
  max-width: 480px;
  box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
  animation: fadeIn 0.8s ease-out;
}

/* Header */
.login-header {
  text-align: center;
  margin-bottom: 2.5rem;
}

.login-icon {
  font-size: 4rem;
  margin-bottom: 1rem;
  animation: bounce 2s infinite;
}

.login-title {
  font-size: 2.5rem;
  font-weight: 800;
  color: #1e293b;
  margin-bottom: 0.5rem;
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  -webkit-background-clip: text;
  -webkit-text-fill-color: transparent;
}

.login-subtitle {
  color: #64748b;
  font-size: 1.125rem;
}

/* Form */
.login-form {
  margin-bottom: 2rem;
}

.form-group {
  margin-bottom: 1.5rem;
}

.input-wrapper {
  position: relative;
}

.form-input {
  width: 100%;
  padding: 1rem 1rem 1rem 3rem;
  border: 2px solid #e2e8f0;
  border-radius: 12px;
  font-size: 1rem;
  transition: all 0.3s ease;
  background: #f8fafc;
}

.form-input:focus {
  outline: none;
  border-color: #6366f1;
  background: white;
  box-shadow: 0 0 0 4px rgba(99, 102, 241, 0.1);
}

.form-input.error {
  border-color: #ef4444;
}

.form-label {
  position: absolute;
  left: 3rem;
  top: 50%;
  transform: translateY(-50%);
  color: #94a3b8;
  pointer-events: none;
  transition: all 0.3s ease;
}

.form-input:focus + .form-label,
.form-input:not(:placeholder-shown) + .form-label {
  top: 0;
  left: 1rem;
  font-size: 0.875rem;
  background: white;
  padding: 0 0.5rem;
  color: #6366f1;
}

.input-icon {
  position: absolute;
  left: 1rem;
  top: 50%;
  transform: translateY(-50%);
  font-size: 1.25rem;
}

.password-toggle {
  position: absolute;
  right: 1rem;
  top: 50%;
  transform: translateY(-50%);
  background: none;
  border: none;
  font-size: 1.25rem;
  cursor: pointer;
  color: #94a3b8;
  transition: color 0.3s ease;
}

.password-toggle:hover {
  color: #6366f1;
}

.error-message {
  color: #ef4444;
  font-size: 0.875rem;
  margin-top: 0.5rem;
}

/* Form Options */
.form-options {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin: 1.5rem 0;
}

.checkbox-container {
  display: flex;
  align-items: center;
  cursor: pointer;
}

.checkbox-container input {
  display: none;
}

.checkmark {
  width: 20px;
  height: 20px;
  border: 2px solid #cbd5e1;
  border-radius: 6px;
  margin-right: 0.75rem;
  position: relative;
  transition: all 0.3s ease;
}

.checkbox-container input:checked + .checkmark {
  background: #6366f1;
  border-color: #6366f1;
}

.checkbox-container input:checked + .checkmark::after {
  content: '✓';
  position: absolute;
  color: white;
  font-size: 0.875rem;
  top: 50%;
  left: 50%;
  transform: translate(-50%, -50%);
}

.checkbox-label {
  color: #475569;
}

.forgot-link {
  color: #6366f1;
  text-decoration: none;
  font-size: 0.875rem;
  font-weight: 500;
  transition: color 0.3s ease;
}

.forgot-link:hover {
  color: #4f46e5;
  text-decoration: underline;
}

/* Error Card */
.error-card {
  display: flex;
  align-items: flex-start;
  gap: 1rem;
  background: #fef2f2;
  border: 1px solid #fee2e2;
  border-radius: 12px;
  padding: 1rem;
  margin: 1.5rem 0;
}

.error-icon {
  font-size: 1.5rem;
}

.error-content {
  flex: 1;
}

.error-title {
  color: #991b1b;
  font-size: 0.875rem;
  font-weight: 600;
  margin-bottom: 0.25rem;
}

.error-text {
  color: #b91c1c;
  font-size: 0.875rem;
}

/* Submit Button */
.submit-btn {
  width: 100%;
  padding: 1rem;
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  color: white;
  border: none;
  border-radius: 12px;
  font-size: 1rem;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.3s ease;
  position: relative;
  overflow: hidden;
}

.submit-btn:hover:not(:disabled) {
  transform: translateY(-2px);
  box-shadow: 0 10px 25px rgba(102, 126, 234, 0.4);
}

.submit-btn:disabled {
  opacity: 0.7;
  cursor: not-allowed;
}

.submit-btn.loading {
  pointer-events: none;
}

.loading-spinner {
  display: inline-block;
  width: 20px;
  height: 20px;
  border: 3px solid rgba(255, 255, 255, 0.3);
  border-radius: 50%;
  border-top-color: white;
  animation: spin 1s ease-in-out infinite;
}

/* Divider */
.divider {
  display: flex;
  align-items: center;
  margin: 2rem 0;
}

.divider::before,
.divider::after {
  content: '';
  flex: 1;
  height: 1px;
  background: #e2e8f0;
}

.divider-text {
  padding: 0 1rem;
  color: #94a3b8;
  font-size: 0.875rem;
}

/* Social Login */
.social-login {
  margin-bottom: 2rem;
}

.social-btn {
  width: 100%;
  padding: 0.875rem;
  border: 2px solid #e2e8f0;
  border-radius: 12px;
  background: white;
  font-size: 1rem;
  font-weight: 500;
  cursor: pointer;
  transition: all 0.3s ease;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 0.75rem;
}

.social-btn:hover {
  border-color: #cbd5e1;
  transform: translateY(-1px);
}

.social-icon {
  font-weight: bold;
  color: #ea4335;
}

/* Footer */
.login-footer {
  text-align: center;
}

.footer-text {
  color: #64748b;
  margin-bottom: 1rem;
}

.footer-link {
  color: #6366f1;
  font-weight: 600;
  text-decoration: none;
  transition: color 0.3s ease;
}

.footer-link:hover {
  color: #4f46e5;
  text-decoration: underline;
}

.back-link {
  display: inline-block;
  color: #94a3b8;
  text-decoration: none;
  font-size: 0.875rem;
  transition: color 0.3s ease;
}

.back-link:hover {
  color: #6366f1;
}

/* Animations */
@keyframes bounce {
  0%, 100% {
    transform: translateY(0);
  }
  50% {
    transform: translateY(-10px);
  }
}

@keyframes spin {
  to {
    transform: rotate(360deg);
  }
}

/* Responsive */
@media (max-width: 480px) {
  .login-card {
    padding: 2rem 1.5rem;
  }
  
  .login-title {
    font-size: 2rem;
  }
  
  .form-options {
    flex-direction: column;
    gap: 1rem;
    align-items: flex-start;
  }
}
</style>