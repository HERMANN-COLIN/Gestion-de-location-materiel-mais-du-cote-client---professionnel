<template>
  <div class="profile-container">
    <!-- En-tête du profil -->
    <div class="profile-header">
      <div class="header-content">
        <h1>Mon Profil</h1>
        <p>Gérez vos informations personnelles et vos préférences</p>
      </div>
      <div class="header-actions">
        <button @click="logout" class="logout-btn">
          <span class="logout-icon">🚪</span>
          Déconnexion
        </button>
      </div>
    </div>

    <div class="profile-content">
      <!-- Menu latéral -->
      <div class="profile-sidebar">
        <nav class="profile-menu">
          <button 
            v-for="tab in tabs" 
            :key="tab.id"
            @click="activeTab = tab.id"
            class="menu-item"
            :class="{ 'active': activeTab === tab.id }"
          >
            <span class="menu-icon">{{ tab.icon }}</span>
            <span class="menu-label">{{ tab.label }}</span>
            <span v-if="tab.badge" class="menu-badge">{{ tab.badge }}</span>
          </button>
        </nav>
      </div>

      <!-- Contenu principal -->
      <div class="profile-main">
        <!-- Onglet Informations personnelles -->
        <div v-if="activeTab === 'info'" class="tab-content">
          <div class="tab-header">
            <h2>Informations personnelles</h2>
            <p>Modifiez vos coordonnées et vos préférences</p>
          </div>

          <form @submit.prevent="updateProfile" class="profile-form">
            <!-- Informations de base -->
            <div class="form-section">
              <h3>Informations de base</h3>
              <div class="form-grid">
                <div class="form-group">
                  <label for="nom">Nom *</label>
                  <input 
                    id="nom" 
                    v-model="form.nom" 
                    type="text" 
                    required
                    :class="{ 'error': errors.nom }"
                  >
                  <p v-if="errors.nom" class="error-message">{{ errors.nom[0] }}</p>
                </div>

                <div class="form-group">
                  <label for="prenom">Prénom *</label>
                  <input 
                    id="prenom" 
                    v-model="form.prenom" 
                    type="text" 
                    required
                    :class="{ 'error': errors.prenom }"
                  >
                  <p v-if="errors.prenom" class="error-message">{{ errors.prenom[0] }}</p>
                </div>

                <div class="form-group">
                  <label for="email">Email *</label>
                  <input 
                    id="email" 
                    v-model="form.email" 
                    type="email" 
                    required
                    :class="{ 'error': errors.email }"
                  >
                  <p v-if="errors.email" class="error-message">{{ errors.email[0] }}</p>
                </div>

                <div class="form-group">
                  <label for="telephone">Téléphone</label>
                  <input 
                    id="telephone" 
                    v-model="form.telephone" 
                    type="tel"
                    placeholder="+32 123 456 789"
                  >
                </div>
              </div>
            </div>

            <!-- Adresse -->
            <div class="form-section" v-if="userType === 'particulier'">
              <h3>Adresse</h3>
              <div class="form-group">
                <label for="adresse">Adresse complète *</label>
                <textarea 
                  id="adresse" 
                  v-model="form.adresse" 
                  rows="3" 
                  required
                  placeholder="Rue, numéro, code postal, ville, pays"
                  :class="{ 'error': errors.adresse }"
                ></textarea>
                <p v-if="errors.adresse" class="error-message">{{ errors.adresse[0] }}</p>
              </div>
            </div>

            <!-- Langue préférée -->
            <div class="form-section">
              <h3>Préférences</h3>
              <div class="form-group">
                <label for="langue_id">Langue préférée *</label>
                <select 
                  id="langue_id" 
                  v-model="form.langue_id" 
                  required
                  :class="{ 'error': errors.langue_id }"
                >
                  <option value="">Sélectionnez une langue</option>
                  <option v-for="langue in langues" :key="langue.id" :value="langue.id">
                    {{ langue.langue }}
                  </option>
                </select>
                <p v-if="errors.langue_id" class="error-message">{{ errors.langue_id[0] }}</p>
              </div>
            </div>

            <!-- Boutons d'action -->
            <div class="form-actions">
              <button type="button" @click="resetForm" class="cancel-btn">
                Annuler
              </button>
              <button type="submit" :disabled="loading" class="save-btn">
                <span v-if="!loading">Enregistrer les modifications</span>
                <span v-else class="loading-spinner"></span>
              </button>
            </div>
          </form>
        </div>

        <!-- Onglet Commandes -->
        <div v-else-if="activeTab === 'orders'" class="tab-content">
          <div class="tab-header">
            <h2>Mes Commandes</h2>
            <p>Historique de toutes vos locations</p>
          </div>

          <div v-if="commandes.length === 0" class="empty-state">
            <div class="empty-icon">📦</div>
            <h3>Aucune commande pour le moment</h3>
            <p>Commencez à explorer notre catalogue</p>
            <router-link to="/catalogue" class="browse-btn">
              Parcourir le catalogue
            </router-link>
          </div>

          <div v-else class="orders-list">
            <div v-for="commande in commandes" :key="commande.id" class="order-card">
              <div class="order-header">
                <div class="order-info">
                  <h4 class="order-number">Commande #{{ commande.numero_commande }}</h4>
                  <span class="order-date">{{ formatDate(commande.date_commande) }}</span>
                </div>
                <div class="order-status" :class="getStatusClass(commande.statut)">
                  {{ getStatusLabel(commande.statut) }}
                </div>
              </div>
              
              <div class="order-details">
                <div class="order-items">
                  <div v-for="item in commande.details" :key="item.id" class="order-item">
                    <img :src="item.materiel?.photo_principale || '/placeholder.jpg'" alt="" class="item-image">
                    <div class="item-info">
                      <h5>{{ item.materiel?.nom }}</h5>
                      <p>{{ item.quantite }} × {{ formatPrice(item.prix_unitaire) }}</p>
                    </div>
                    <div class="item-subtotal">{{ formatPrice(item.sous_total) }}</div>
                  </div>
                </div>
                
                <div class="order-summary">
                  <div class="summary-row">
                    <span>Sous-total</span>
                    <span>{{ formatPrice(commande.montant_total - commande.frais_livraison) }}</span>
                  </div>
                  <div class="summary-row">
                    <span>Frais de livraison</span>
                    <span>{{ formatPrice(commande.frais_livraison || 0) }}</span>
                  </div>
                  <div class="summary-row total">
                    <span>Total</span>
                    <span class="total-price">{{ formatPrice(commande.montant_total) }}</span>
                  </div>
                </div>
              </div>
              
              <div class="order-actions">
                <button @click="viewOrder(commande.id)" class="view-btn">
                  Voir les détails
                </button>
                <button v-if="commande.statut === 'en_attente'" @click="cancelOrder(commande.id)" class="cancel-order-btn">
                  Annuler
                </button>
                <a v-if="commande.facture_url" :href="commande.facture_url" target="_blank" class="invoice-btn">
                  📄 Facture
                </a>
              </div>
            </div>
          </div>
        </div>

        <!-- Onglet Sécurité -->
        <div v-else-if="activeTab === 'security'" class="tab-content">
          <div class="tab-header">
            <h2>Sécurité du compte</h2>
            <p>Gérez votre mot de passe et la sécurité</p>
          </div>

          <form @submit.prevent="updatePassword" class="security-form">
            <div class="form-section">
              <h3>Changer le mot de passe</h3>
              
              <div class="form-group">
                <label for="current_password">Mot de passe actuel *</label>
                <div class="password-input">
                  <input 
                    id="current_password" 
                    v-model="passwordForm.current_password" 
                    :type="showCurrentPassword ? 'text' : 'password'" 
                    required
                    :class="{ 'error': passwordErrors.current_password }"
                  >
                  <button type="button" @click="showCurrentPassword = !showCurrentPassword" class="password-toggle">
                    {{ showCurrentPassword ? '🙈' : '👁️' }}
                  </button>
                </div>
                <p v-if="passwordErrors.current_password" class="error-message">{{ passwordErrors.current_password[0] }}</p>
              </div>

              <div class="form-group">
                <label for="new_password">Nouveau mot de passe *</label>
                <div class="password-input">
                  <input 
                    id="new_password" 
                    v-model="passwordForm.new_password" 
                    :type="showNewPassword ? 'text' : 'password'" 
                    required
                    minlength="8"
                    :class="{ 'error': passwordErrors.new_password }"
                  >
                  <button type="button" @click="showNewPassword = !showNewPassword" class="password-toggle">
                    {{ showNewPassword ? '🙈' : '👁️' }}
                  </button>
                </div>
                <p v-if="passwordErrors.new_password" class="error-message">{{ passwordErrors.new_password[0] }}</p>
                <div class="password-strength">
                  <div class="strength-bar" :class="getPasswordStrength(passwordForm.new_password)"></div>
                  <span class="strength-text">{{ getPasswordStrengthText(passwordForm.new_password) }}</span>
                </div>
              </div>

              <div class="form-group">
                <label for="new_password_confirmation">Confirmer le nouveau mot de passe *</label>
                <div class="password-input">
                  <input 
                    id="new_password_confirmation" 
                    v-model="passwordForm.new_password_confirmation" 
                    :type="showConfirmPassword ? 'text' : 'password'" 
                    required
                    :class="{ 'error': passwordErrors.new_password_confirmation }"
                  >
                  <button type="button" @click="showConfirmPassword = !showConfirmPassword" class="password-toggle">
                    {{ showConfirmPassword ? '🙈' : '👁️' }}
                  </button>
                </div>
                <p v-if="passwordErrors.new_password_confirmation" class="error-message">{{ passwordErrors.new_password_confirmation[0] }}</p>
              </div>

              <div class="password-requirements">
                <p><strong>Le mot de passe doit contenir :</strong></p>
                <ul>
                  <li :class="{ 'valid': passwordForm.new_password.length >= 8 }">Au moins 8 caractères</li>
                  <li :class="{ 'valid': /[A-Z]/.test(passwordForm.new_password) }">Une majuscule</li>
                  <li :class="{ 'valid': /[a-z]/.test(passwordForm.new_password) }">Une minuscule</li>
                  <li :class="{ 'valid': /[0-9]/.test(passwordForm.new_password) }">Un chiffre</li>
                  <li :class="{ 'valid': /[^A-Za-z0-9]/.test(passwordForm.new_password) }">Un caractère spécial</li>
                </ul>
              </div>
            </div>

            <div class="form-actions">
              <button type="submit" :disabled="passwordLoading" class="save-btn">
                <span v-if="!passwordLoading">Mettre à jour le mot de passe</span>
                <span v-else class="loading-spinner"></span>
              </button>
            </div>
          </form>
        </div>

        <!-- Onglet Notifications -->
        <div v-else-if="activeTab === 'notifications'" class="tab-content">
          <div class="tab-header">
            <h2>Préférences de notifications</h2>
            <p>Choisissez les notifications que vous souhaitez recevoir</p>
          </div>

          <form @submit.prevent="updateNotifications" class="notifications-form">
            <div class="form-section">
              <h3>Notifications par email</h3>
              
              <div class="checkbox-group">
                <label class="checkbox-item">
                  <input type="checkbox" v-model="notifications.email.commandes" class="checkbox-input">
                  <span class="checkbox-label">
                    <span class="checkbox-title">Nouvelles commandes</span>
                    <span class="checkbox-description">Recevoir un email lors de la création d'une nouvelle commande</span>
                  </span>
                </label>

                <label class="checkbox-item">
                  <input type="checkbox" v-model="notifications.email.statut" class="checkbox-input">
                  <span class="checkbox-label">
                    <span class="checkbox-title">Changements de statut</span>
                    <span class="checkbox-description">Être informé des mises à jour de vos commandes</span>
                  </span>
                </label>

                <label class="checkbox-item">
                  <input type="checkbox" v-model="notifications.email.promotions" class="checkbox-input">
                  <span class="checkbox-label">
                    <span class="checkbox-title">Promotions et offres spéciales</span>
                    <span class="checkbox-description">Recevoir nos offres exclusives</span>
                  </span>
                </label>

                <label class="checkbox-item">
                  <input type="checkbox" v-model="notifications.email.newsletter" class="checkbox-input">
                  <span class="checkbox-label">
                    <span class="checkbox-title">Newsletter</span>
                    <span class="checkbox-description">Recevoir nos actualités et conseils</span>
                  </span>
                </label>
              </div>
            </div>

            <div class="form-section">
              <h3>Notifications push (si disponible)</h3>
              
              <div class="checkbox-group">
                <label class="checkbox-item">
                  <input type="checkbox" v-model="notifications.push.commandes" class="checkbox-input">
                  <span class="checkbox-label">
                    <span class="checkbox-title">Notifications de commandes</span>
                    <span class="checkbox-description">Recevoir des notifications push pour vos commandes</span>
                  </span>
                </label>

                <label class="checkbox-item">
                  <input type="checkbox" v-model="notifications.push.rappels" class="checkbox-input">
                  <span class="checkbox-label">
                    <span class="checkbox-title">Rappels de location</span>
                    <span class="checkbox-description">Rappels avant le début et la fin de location</span>
                  </span>
                </label>
              </div>
            </div>

            <div class="form-actions">
              <button type="submit" :disabled="notificationsLoading" class="save-btn">
                <span v-if="!notificationsLoading">Enregistrer les préférences</span>
                <span v-else class="loading-spinner"></span>
              </button>
            </div>
          </form>
        </div>
      </div>
    </div>

    <!-- Messages d'alerte -->
    <div v-if="successMessage" class="alert success">
      <span class="alert-icon">✅</span>
      <span class="alert-message">{{ successMessage }}</span>
      <button @click="successMessage = ''" class="alert-close">×</button>
    </div>

    <div v-if="errorMessage" class="alert error">
      <span class="alert-icon">⚠️</span>
      <span class="alert-message">{{ errorMessage }}</span>
      <button @click="errorMessage = ''" class="alert-close">×</button>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, computed } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
import api from '@/services/axios'

const router = useRouter()
const authStore = useAuthStore()

// États
const activeTab = ref('info')
const loading = ref(false)
const passwordLoading = ref(false)
const notificationsLoading = ref(false)
const successMessage = ref('')
const errorMessage = ref('')
const errors = ref({})
const passwordErrors = ref({})

// Données utilisateur
const userData = ref(null)
const langues = ref([])
const commandes = ref([])

// Formulaires
const form = ref({
  nom: '',
  prenom: '',
  email: '',
  telephone: '',
  adresse: '',
  langue_id: ''
})

const passwordForm = ref({
  current_password: '',
  new_password: '',
  new_password_confirmation: ''
})

const notifications = ref({
  email: {
    commandes: true,
    statut: true,
    promotions: true,
    newsletter: true
  },
  push: {
    commandes: true,
    rappels: true
  }
})

// Affichage des mots de passe
const showCurrentPassword = ref(false)
const showNewPassword = ref(false)
const showConfirmPassword = ref(false)

// Tabs
const tabs = [
  { id: 'info', label: 'Informations', icon: '👤' },
  { id: 'orders', label: 'Commandes', icon: '📦' },
  { id: 'security', label: 'Sécurité', icon: '🔒' },
  { id: 'notifications', label: 'Notifications', icon: '🔔' }
]

// Computed
const userType = computed(() => {
  return userData.value?.type?.type || 'particulier'
})

// Charger les données
const loadData = async () => {
  try {
    // Charger les langues
    const languesResponse = await api.get('/langues')
    langues.value = languesResponse.data.data || languesResponse.data

    // Charger l'utilisateur
    if (authStore.user) {
      userData.value = authStore.user
      form.value = {
        nom: userData.value.nom || '',
        prenom: userData.value.prenom || '',
        email: userData.value.email || '',
        telephone: userData.value.telephone || '',
        adresse: userData.value.adresse || '',
        langue_id: userData.value.langue_id || ''
      }
    }

    // Charger les commandes
    await loadCommandes()
  } catch (error) {
    console.error('Erreur chargement données:', error)
  }
}

const loadCommandes = async () => {
  try {
    const response = await api.get('/commandes')
    commandes.value = response.data.data || response.data || []
  } catch (error) {
    console.error('Erreur chargement commandes:', error)
  }
}

// Mettre à jour le profil
const updateProfile = async () => {
  loading.value = true
  errors.value = {}
  errorMessage.value = ''
  successMessage.value = ''

  try {
    const response = await api.put('/user/profile', form.value)
    
    if (response.data.success) {
      successMessage.value = 'Profil mis à jour avec succès'
      authStore.user = { ...authStore.user, ...form.value }
    }
  } catch (error) {
    if (error.response?.status === 422) {
      errors.value = error.response.data.errors
    } else {
      errorMessage.value = 'Erreur lors de la mise à jour du profil'
    }
  } finally {
    loading.value = false
  }
}

// Mettre à jour le mot de passe
const updatePassword = async () => {
  passwordLoading.value = true
  passwordErrors.value = {}
  errorMessage.value = ''
  successMessage.value = ''

  try {
    const response = await api.put('/user/password', passwordForm.value)
    
    if (response.data.success) {
      successMessage.value = 'Mot de passe mis à jour avec succès'
      passwordForm.value = {
        current_password: '',
        new_password: '',
        new_password_confirmation: ''
      }
    }
  } catch (error) {
    if (error.response?.status === 422) {
      passwordErrors.value = error.response.data.errors
    } else {
      errorMessage.value = 'Erreur lors de la mise à jour du mot de passe'
    }
  } finally {
    passwordLoading.value = false
  }
}

// Mettre à jour les notifications
const updateNotifications = async () => {
  notificationsLoading.value = true
  errorMessage.value = ''
  successMessage.value = ''

  try {
    // TODO: Implémenter l'API de notifications
    await new Promise(resolve => setTimeout(resolve, 1000))
    successMessage.value = 'Préférences de notifications mises à jour'
  } catch (error) {
    errorMessage.value = 'Erreur lors de la mise à jour des notifications'
  } finally {
    notificationsLoading.value = false
  }
}

// Commandes
const viewOrder = (orderId) => {
  router.push(`/commandes/${orderId}`)
}

const cancelOrder = async (orderId) => {
  if (!confirm('Êtes-vous sûr de vouloir annuler cette commande ?')) return

  try {
    await api.put(`/commandes/${orderId}/annuler`)
    successMessage.value = 'Commande annulée avec succès'
    loadCommandes()
  } catch (error) {
    errorMessage.value = 'Erreur lors de l\'annulation de la commande'
  }
}

// Utilitaires
const resetForm = () => {
  form.value = {
    nom: userData.value.nom || '',
    prenom: userData.value.prenom || '',
    email: userData.value.email || '',
    telephone: userData.value.telephone || '',
    adresse: userData.value.adresse || '',
    langue_id: userData.value.langue_id || ''
  }
  errors.value = {}
}

const formatDate = (dateString) => {
  return new Date(dateString).toLocaleDateString('fr-FR', {
    day: '2-digit',
    month: '2-digit',
    year: 'numeric'
  })
}

const formatPrice = (price) => {
  return new Intl.NumberFormat('fr-FR', {
    style: 'currency',
    currency: 'EUR',
    minimumFractionDigits: 2
  }).format(price)
}

const getStatusLabel = (status) => {
  const statuses = {
    'en_attente': 'En attente',
    'confirmee': 'Confirmée',
    'en_preparation': 'En préparation',
    'livree': 'Livrée',
    'recuperee': 'Récupérée',
    'retournee': 'Retournée',
    'annulee': 'Annulée'
  }
  return statuses[status] || status
}

const getStatusClass = (status) => {
  const classes = {
    'en_attente': 'status-pending',
    'confirmee': 'status-confirmed',
    'en_preparation': 'status-preparing',
    'livree': 'status-delivered',
    'recuperee': 'status-picked',
    'retournee': 'status-returned',
    'annulee': 'status-cancelled'
  }
  return classes[status] || ''
}

const getPasswordStrength = (password) => {
  if (!password) return 'strength-none'
  
  let strength = 0
  if (password.length >= 8) strength++
  if (/[A-Z]/.test(password)) strength++
  if (/[a-z]/.test(password)) strength++
  if (/[0-9]/.test(password)) strength++
  if (/[^A-Za-z0-9]/.test(password)) strength++
  
  if (strength < 2) return 'strength-weak'
  if (strength < 4) return 'strength-medium'
  return 'strength-strong'
}

const getPasswordStrengthText = (password) => {
  const strength = getPasswordStrength(password)
  switch (strength) {
    case 'strength-weak': return 'Faible'
    case 'strength-medium': return 'Moyen'
    case 'strength-strong': return 'Fort'
    default: return ''
  }
}

// Déconnexion
const logout = async () => {
  await authStore.logout()
  router.push('/login')
}

// Initialisation
onMounted(() => {
  loadData()
})
</script>

<style scoped>
/* Styles complets disponibles sur demande */
/* Le code est très long donc je fournis les styles essentiels */

.profile-container {
  max-width: 1200px;
  margin: 0 auto;
  padding: 20px;
  min-height: 100vh;
}

.profile-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 40px;
  padding-bottom: 20px;
  border-bottom: 2px solid #f3f4f6;
}

.profile-content {
  display: grid;
  grid-template-columns: 250px 1fr;
  gap: 40px;
}

.profile-menu {
  display: flex;
  flex-direction: column;
  gap: 5px;
}

.menu-item {
  display: flex;
  align-items: center;
  gap: 12px;
  padding: 15px;
  border: none;
  background: none;
  border-radius: 10px;
  cursor: pointer;
  transition: all 0.3s;
  position: relative;
}

.menu-item:hover {
  background: #f3f4f6;
}

.menu-item.active {
  background: #667eea;
  color: white;
}

.menu-badge {
  margin-left: auto;
  background: #ef4444;
  color: white;
  padding: 3px 8px;
  border-radius: 12px;
  font-size: 0.75rem;
}

.tab-content {
  background: white;
  border-radius: 12px;
  padding: 30px;
  box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
}

.form-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
  gap: 20px;
}

.form-group {
  margin-bottom: 20px;
}

.form-group label {
  display: block;
  margin-bottom: 8px;
  font-weight: 600;
  color: #374151;
}

.form-group input,
.form-group select,
.form-group textarea {
  width: 100%;
  padding: 12px;
  border: 2px solid #e5e7eb;
  border-radius: 8px;
  font-size: 1rem;
  transition: border-color 0.3s;
}

.form-group input:focus,
.form-group select:focus,
.form-group textarea:focus {
  outline: none;
  border-color: #667eea;
}

.form-group input.error,
.form-group select.error,
.form-group textarea.error {
  border-color: #ef4444;
}

.error-message {
  color: #ef4444;
  font-size: 0.875rem;
  margin-top: 5px;
}

.password-input {
  position: relative;
}

.password-toggle {
  position: absolute;
  right: 12px;
  top: 50%;
  transform: translateY(-50%);
  background: none;
  border: none;
  cursor: pointer;
  font-size: 1.2rem;
}

.form-actions {
  display: flex;
  justify-content: flex-end;
  gap: 15px;
  margin-top: 30px;
  padding-top: 30px;
  border-top: 1px solid #f3f4f6;
}

.cancel-btn {
  padding: 12px 24px;
  background: #f3f4f6;
  color: #374151;
  border: 2px solid #e5e7eb;
  border-radius: 8px;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.3s;
}

.save-btn {
  padding: 12px 24px;
  background: #667eea;
  color: white;
  border: none;
  border-radius: 8px;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.3s;
  min-width: 200px;
}

.save-btn:hover:not(:disabled) {
  background: #5a6fd8;
  transform: translateY(-2px);
}

.save-btn:disabled {
  opacity: 0.7;
  cursor: not-allowed;
}

.loading-spinner {
  display: inline-block;
  width: 20px;
  height: 20px;
  border: 2px solid rgba(255, 255, 255, 0.3);
  border-radius: 50%;
  border-top-color: white;
  animation: spin 1s linear infinite;
}

@keyframes spin {
  to { transform: rotate(360deg); }
}

.alert {
  position: fixed;
  top: 20px;
  right: 20px;
  display: flex;
  align-items: center;
  gap: 12px;
  padding: 16px 20px;
  border-radius: 8px;
  z-index: 1000;
  max-width: 400px;
  animation: slideIn 0.3s ease-out;
}

.alert.success {
  background: #10b981;
  color: white;
}

.alert.error {
  background: #ef4444;
  color: white;
}

@keyframes slideIn {
  from {
    transform: translateX(100%);
    opacity: 0;
  }
  to {
    transform: translateX(0);
    opacity: 1;
  }
}

/* Styles responsive */
@media (max-width: 768px) {
  .profile-content {
    grid-template-columns: 1fr;
  }
  
  .profile-sidebar {
    order: 2;
  }
  
  .profile-menu {
    flex-direction: row;
    overflow-x: auto;
    padding-bottom: 10px;
  }
  
  .menu-item {
    white-space: nowrap;
  }
  
  .form-actions {
    flex-direction: column;
  }
  
  .save-btn {
    min-width: 100%;
  }
}
</style>