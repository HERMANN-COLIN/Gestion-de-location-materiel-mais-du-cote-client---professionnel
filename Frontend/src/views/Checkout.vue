<template>
  <div class="checkout-container">
    <!-- Header -->
    <div class="checkout-header">
      <h1>📦 Validation de commande</h1>
      <p>Finalisez votre réservation en quelques étapes</p>
    </div>

    <div class="checkout-content">
      <!-- Colonne gauche - Formulaire -->
      <div class="checkout-form">
        <!-- Mode de livraison -->
        <div class="form-section">
          <div class="section-header">
            <span class="section-icon">🚚</span>
            <h2>Mode de livraison</h2>
          </div>
          
          <!-- Options de livraison -->
          <div class="options-grid">
            <div 
              class="option-card"
              :class="{ 'active': form.mode_livraison === 1 }"
              @click="selectLivraison(1)"
            >
              <div class="option-radio">
                <div class="radio-circle" :class="{ 'selected': form.mode_livraison === 1 }"></div>
              </div>
              <div class="option-content">
                <span class="option-icon">🏪</span>
                <div class="option-text">
                  <h3>Retrait sur place</h3>
                  <p>Vous récupérez votre commande directement dans notre magasin</p>
                  <span class="option-price">Gratuit</span>
                </div>
              </div>
            </div>

            <div 
              class="option-card"
              :class="{ 'active': form.mode_livraison === 2 }"
              @click="selectLivraison(2)"
            >
              <div class="option-radio">
                <div class="radio-circle" :class="{ 'selected': form.mode_livraison === 2 }"></div>
              </div>
              <div class="option-content">
                <span class="option-icon">📦</span>
                <div class="option-text">
                  <h3>Livraison à domicile</h3>
                  <p>Nous livrons votre commande à l'adresse indiquée</p>
                  <span class="option-price">{{ fraisLivraison > 0 ? formatPrice(fraisLivraison) : 'Calcul...' }}</span>
                </div>
              </div>
            </div>
          </div>

          <!-- DÉTAILS DE LIVRAISON -->
          <div v-if="form.mode_livraison === 2" class="delivery-details-section">
            <h3>📋 Détails de livraison</h3>
            
            <div class="delivery-form">
              <!-- Type de jour -->
              <div class="form-group">
                <label>📅 Jour de livraison</label>
                <div class="day-selector">
                  <button 
                    v-for="day in joursLivraison" 
                    :key="day.value"
                    class="day-btn"
                    :class="{ 'active': form.jour_livraison === day.value }"
                    @click="selectJourLivraison(day.value)"
                  >
                    {{ day.label }}
                  </button>
                </div>
                <p class="field-help" v-if="!form.jour_livraison">
                  Sélectionnez le jour pour la livraison
                </p>
              </div>

              <!-- Distance (km) -->
              <div class="form-group">
                <label>📍 Distance (km)</label>
                <div class="distance-input-group">
                  <input 
                    type="number" 
                    v-model.number="form.distance"
                    placeholder="Distance en kilomètres"
                    min="0"
                    max="50"
                    step="0.1"
                    class="distance-input"
                    @input="calculerFrais"
                  >
                  <span class="distance-unit">km</span>
                </div>
                <p class="field-help">
                  Distance entre notre magasin et votre adresse
                </p>
                <div class="distance-info" v-if="form.distance > 50">
                  ⚠️ Distance maximale autorisée : 50 km
                </div>
              </div>

              <!-- Tarif calculé pour livraison -->
              <div v-if="form.jour_livraison && form.distance > 0" class="tarif-preview">
                <div class="tarif-header">
                  <span class="tarif-icon">💰</span>
                  <span class="tarif-label">Tarif de livraison :</span>
                </div>
                <div class="tarif-details">
                  <div class="tarif-row">
                    <span>{{ getJourLabel(form.jour_livraison) }} :</span>
                    <span class="tarif-rate">{{ getTarifKm(form.jour_livraison) }} €/km</span>
                  </div>
                  <div class="tarif-row">
                    <span>Distance :</span>
                    <span>{{ form.distance }} km</span>
                  </div>
                  <div class="tarif-total">
                    <span>Total livraison :</span>
                    <span class="tarif-montant">{{ formatPrice(fraisLivraison) }}</span>
                  </div>
                </div>
              </div>

              <!-- Message si informations manquantes -->
              <div v-else class="tarif-placeholder">
                <span class="placeholder-icon">📊</span>
                <span>Sélectionnez un jour et une distance pour calculer les frais de livraison</span>
              </div>
            </div>
          </div>

          <!-- ADRESSE DE LIVRAISON -->
          <div v-if="form.mode_livraison === 2" class="address-section">
            <h3>🏠 Adresse de livraison</h3>
            
            <!-- Chargement -->
            <div v-if="loading.user" class="loading-small">
              <div class="spinner-small"></div>
              <span>Chargement de votre adresse...</span>
            </div>
            
            <!-- Particulier : adresse du profil -->
            <div v-else-if="!isProfessional" class="addresses-list">
              <div v-if="userAdresse && userAdresse.trim() !== ''" class="address-card active">
                <div class="address-radio">
                  <div class="radio-circle selected"></div>
                </div>
                <div class="address-content">
                  <div class="address-name">{{ userNom }}</div>
                  <div class="address-line">{{ userAdresse }}</div>
                  <div class="address-phone" v-if="userTelephone">📞 {{ userTelephone }}</div>
                </div>
                <span class="badge-primary">Adresse personnelle</span>
              </div>
              
              <!-- Message si aucune adresse -->
              <div v-else class="address-missing error">
                <span class="missing-icon">📍</span>
                <div class="missing-text">
                  <strong>Adresse manquante</strong>
                  <p>Vous devez renseigner votre adresse dans votre profil</p>
                </div>
                <router-link to="/profil" class="btn-profile">
                  Ajouter mon adresse
                </router-link>
              </div>
            </div>

            <!-- Professionnel : liste des adresses -->
            <div v-else-if="isProfessional" class="addresses-list">
              <div v-if="adressesProfessionnel.length > 0">
                <div 
                  v-for="(adresse, index) in adressesProfessionnel" 
                  :key="adresse.id"
                  class="address-card"
                  :class="{ 'active': form.adresse_livraison_index === index }"
                  @click="selectAdresseProfessionnel(index)"
                >
                  <div class="address-radio">
                    <div class="radio-circle" :class="{ 'selected': form.adresse_livraison_index === index }"></div>
                  </div>
                  <div class="address-content">
                    <div class="address-company">
                      <span class="company-icon">🏢</span>
                      <strong>{{ adresse.nom_societe }}</strong>
                    </div>
                    <div class="address-line">{{ adresse.adresse }}</div>
                    <div class="address-type">
                      <span class="type-icon">{{ adresse.type === 'Livraison par défaut' ? '📦' : '🏛️' }}</span>
                      {{ adresse.type }}
                    </div>
                  </div>
                  <span v-if="adresse.est_principale" class="badge-primary">Principale</span>
                  <span v-else class="badge-secondary">{{ adresse.type }}</span>
                </div>
              </div>
              
              <div v-else class="address-missing warning">
                <span class="missing-icon">🏢</span>
                <div class="missing-text">
                  <strong>Aucune adresse disponible</strong>
                  <p>Veuillez configurer votre adresse dans votre profil professionnel</p>
                </div>
                <router-link to="/profil" class="btn-profile">
                  Configurer mon profil
                </router-link>
              </div>
            </div>
          </div>
        </div>

        <!-- SÉLECTION DES DATES DE LOCATION -->
        <div class="form-section">
          <div class="section-header">
            <span class="section-icon">📅</span>
            <h2>Période de location</h2>
          </div>
          
          <div class="dates-grid">
            <div class="form-group">
              <label>Date de début (retrait)</label>
              <input 
                type="date" 
                v-model="form.date_debut"
                class="date-input"
                :min="today"
                required
              >
            </div>
            
            <div class="form-group">
              <label>Date de fin (retour)</label>
              <input 
                type="date" 
                v-model="form.date_fin"
                class="date-input"
                :min="form.date_debut || today"
                required
              >
            </div>
          </div>
          
          <div v-if="form.date_debut && form.date_fin" class="duree-info">
            <span class="duree-icon">⏱️</span>
            <span>Durée de location : {{ dureeLocation }} jours</span>
          </div>
          
          <div v-else class="date-warning">
            ⚠️ Veuillez sélectionner les dates de location
          </div>
        </div>

        <!-- Mode de retour (avec calcul basé sur le jour de fin) -->
        <div class="form-section">
          <div class="section-header">
            <span class="section-icon">🔄</span>
            <h2>Mode de retour</h2>
            <p class="section-subtitle">Le jour de retour correspond à la date de fin de location</p>
          </div>
          
          <div class="options-grid">
            <div 
              class="option-card"
              :class="{ 'active': form.mode_retour === 1 }"
              @click="selectRetour(1)"
            >
              <div class="option-radio">
                <div class="radio-circle" :class="{ 'selected': form.mode_retour === 1 }"></div>
              </div>
              <div class="option-content">
                <span class="option-icon">🏪</span>
                <div class="option-text">
                  <h3>Retour sur place</h3>
                  <p>Vous rapportez le matériel en magasin</p>
                  <span class="option-price">Gratuit</span>
                </div>
              </div>
            </div>

            <div 
              class="option-card"
              :class="{ 'active': form.mode_retour === 2 }"
              @click="selectRetour(2)"
            >
              <div class="option-radio">
                <div class="radio-circle" :class="{ 'selected': form.mode_retour === 2 }"></div>
              </div>
              <div class="option-content">
                <span class="option-icon">🏠</span>
                <div class="option-text">
                  <h3>Retour à domicile</h3>
                  <p>Nous venons récupérer le matériel à votre adresse</p>
                  <span class="option-price">{{ fraisRetour > 0 ? formatPrice(fraisRetour) : 'Calcul...' }}</span>
                </div>
              </div>
            </div>
          </div>

          <!-- Détails retour (si mode retour à domicile) -->
          <div v-if="form.mode_retour === 2" class="delivery-details-section retour-details">
            <div class="info-box">
              <span class="info-icon">ℹ️</span>
              <div class="info-content">
                <strong>Informations de retour</strong>
                <p>
                  La récupération du matériel se fera à la même adresse que la livraison.
                </p>
                <p v-if="form.mode_livraison === 2 && form.date_fin">
                  📍 Adresse de retour : {{ getAdresseRetour() }}
                </p>
                <p v-if="form.mode_livraison === 1" class="warning-text">
                  ⚠️ Pour un retour à domicile, veuillez sélectionner "Livraison à domicile" ci-dessus.
                </p>
              </div>
            </div>

            <!-- Aperçu des frais de retour basés sur la date de fin -->
            <div v-if="form.date_fin && form.distance > 0" class="tarif-preview">
              <div class="tarif-header">
                <span class="tarif-icon">💰</span>
                <span class="tarif-label">Tarif de retour :</span>
              </div>
              <div class="tarif-details">
                <div class="tarif-row">
                  <span>Date de fin :</span>
                  <span>{{ formatDate(form.date_fin) }} ({{ getJourRetourLabel() }})</span>
                </div>
                <div class="tarif-row">
                  <span>Tarif appliqué :</span>
                  <span class="tarif-rate">{{ getTarifRetour() }} €/km</span>
                </div>
                <div class="tarif-row">
                  <span>Distance :</span>
                  <span>{{ form.distance }} km</span>
                </div>
                <div class="tarif-total">
                  <span>Total retour :</span>
                  <span class="tarif-montant">{{ formatPrice(fraisRetour) }}</span>
                </div>
              </div>
            </div>

            <!-- Message si informations manquantes -->
            <div v-else class="tarif-placeholder">
              <span class="placeholder-icon">📊</span>
              <span>Sélectionnez une date de fin et une distance pour calculer les frais de retour</span>
            </div>
          </div>
        </div>

        <!-- Notes (optionnel) -->
        <div class="form-section">
          <div class="section-header">
            <span class="section-icon">📝</span>
            <h2>Notes</h2>
          </div>
          
          <textarea 
            v-model="form.notes"
            placeholder="Ajoutez une note pour votre commande (instructions spéciales, etc.)"
            rows="3"
            class="notes-input"
          ></textarea>
        </div>
      </div>

      <!-- Colonne droite - Récapitulatif -->
      <div class="checkout-summary">
        <div class="summary-card sticky">
          <div class="summary-header">
            <span class="summary-icon">📋</span>
            <h2>Récapitulatif</h2>
          </div>

          <!-- Articles -->
          <div class="summary-items">
            <div v-for="item in cartItems" :key="item.id" class="summary-item">
              <div class="item-image">
                <img :src="item.photo || '/placeholder.jpg'" :alt="item.nom">
              </div>
              <div class="item-details">
                <div class="item-name">{{ item.nom }}</div>
                <div class="item-meta">{{ item.quantite }} × {{ formatPrice(item.prix_journalier_ht) }} HT / jour</div>
                <div class="item-meta-ttc">{{ formatPrice(item.prix_journalier_ttc) }} TTC / jour</div>
              </div>
              <div class="item-price">
                <div class="price-ht">{{ formatPrice(item.total_ht * (dureeLocation || 1)) }}</div>
                <div class="price-ttc">{{ formatPrice(item.total_ttc * (dureeLocation || 1)) }}</div>
              </div>
            </div>
          </div>

          <!-- Détails des prix -->
          <div class="price-details">
            <div class="price-row">
              <span class="price-label">Sous-total HT ({{ dureeLocation }} jours)</span>
              <span class="price-value">{{ formatPrice(subtotalHT) }}</span>
            </div>
            <div class="price-row">
              <span class="price-label">TVA (21%)</span>
              <span class="price-value">{{ formatPrice(subtotalTTC - subtotalHT) }}</span>
            </div>
            <div class="price-row">
              <span class="price-label">Sous-total TTC</span>
              <span class="price-value">{{ formatPrice(subtotalTTC) }}</span>
            </div>
            
            <div class="price-row">
              <span class="price-label">
                Frais de livraison
                <span class="price-info" v-if="form.mode_livraison === 2">
                  ({{ getJourLabel(form.jour_livraison) }} - {{ form.distance || 0 }} km)
                </span>
              </span>
              <span class="price-value">
                {{ fraisLivraison > 0 ? formatPrice(fraisLivraison) : 'Gratuit' }}
              </span>
            </div>
            
            <div class="price-row">
              <span class="price-label">
                Frais de retour
                <span class="price-info" v-if="form.mode_retour === 2">
                  ({{ getJourRetourLabel() }} - {{ form.distance || 0 }} km)
                </span>
              </span>
              <span class="price-value">
                {{ fraisRetour > 0 ? formatPrice(fraisRetour) : 'Gratuit' }}
              </span>
            </div>
            
            <div v-if="remise > 0" class="price-row discount">
              <span class="price-label">
                <span class="discount-icon">🎁</span>
                Remise
              </span>
              <span class="price-value discount">-{{ formatPrice(remise) }}</span>
            </div>
          </div>

          <!-- Total -->
          <div class="total-section">
            <div class="total-row">
              <span class="total-label">Total TTC</span>
              <span class="total-amount">{{ formatPrice(total) }}</span>
            </div>
            <div class="total-tax">TVA incluse</div>
          </div>

          <!-- Bouton de validation -->
          <button 
            @click="submitOrder"
            :disabled="!canSubmit || submitting"
            class="btn-submit"
            :class="{ 'loading': submitting }"
          >
            <span v-if="!submitting" class="btn-icon">✅</span>
            <span v-else class="spinner-small"></span>
            {{ submitting ? 'Traitement...' : 'Confirmer la commande' }}
          </button>

          <!-- Garanties -->
          <div class="guarantees">
            <div class="guarantee">
              <span class="guarantee-icon">🛡️</span>
              <span>Paiement sécurisé</span>
            </div>
            <div class="guarantee">
              <span class="guarantee-icon">🔒</span>
              <span>Données confidentielles</span>
            </div>
            <div class="guarantee">
              <span class="guarantee-icon">📞</span>
              <span>Support 7j/7</span>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Message de succès -->
    <div v-if="orderSuccess" class="success-overlay">
      <div class="success-modal">
        <div class="success-icon">🎉</div>
        <h2>Commande confirmée !</h2>
        <p>Votre commande n°{{ orderNumber }} a été enregistrée avec succès.</p>
        
        <div class="success-actions">
          <router-link :to="`/commandes/${orderId}`" class="btn-primary" v-if="orderId">
            <span class="btn-icon">🔍</span>
            Voir le détail
          </router-link>
          
          <router-link to="/commandes" class="btn-secondary">
            <span class="btn-icon">📋</span>
            Mes commandes
          </router-link>
          
          <router-link to="/catalogue" class="btn-outline">
            <span class="btn-icon">🛍️</span>
            Continuer mes achats
          </router-link>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, reactive, computed, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
import api from '@/services/axios'

const router = useRouter()
const auth = useAuthStore()
const loading = ref({
  user: true,
  cart: true,
  codes: true
})

// Date du jour pour le min des inputs date
const today = new Date().toISOString().split('T')[0]

// État du formulaire
const form = reactive({
  date_debut: '',
  date_fin: '',
  mode_livraison: 1,
  mode_retour: 1,
  adresse_livraison_id: null,
  adresse_livraison_texte: '',
  frais_livraison: 0,
  frais_retour: 0,
  jour_livraison: null,
  distance: 0, // Distance unique pour livraison et retour
  code_reduction: null,
  notes: '',
  adresse_livraison_index: null
})

// Données utilisateur
const userInfos = ref(null)
const userType = ref(null)
const userNom = ref('')
const userAdresse = ref('')
const userTelephone = ref('')
const userEmail = ref('')

// Adresses professionnelles
const adressesProfessionnel = ref([])

// Données du panier (depuis serveur)
const cartItems = ref([])

// Code promo
const appliedPromo = ref(null)

// État de la commande
const submitting = ref(false)
const orderSuccess = ref(false)
const orderNumber = ref('')
const orderId = ref(null)

// Configuration des jours et tarifs
const joursLivraison = [
  { value: 'lundi_vendredi', label: 'Lundi - Vendredi', tarifKm: 1.25 },
  { value: 'samedi', label: 'Samedi', tarifKm: 1.00 },
  { value: 'dimanche', label: 'Dimanche', tarifKm: 1.50 }
]

// Configuration des jours pour le retour basée sur le jour de la semaine
const getJourRetourFromDate = (date) => {
  if (!date) return null
  const dateObj = new Date(date)
  const jourSemaine = dateObj.getDay() // 0 = dimanche, 1 = lundi, ..., 6 = samedi
  
  if (jourSemaine === 0) return 'dimanche'
  if (jourSemaine === 6) return 'samedi'
  return 'lundi_vendredi'
}

const getTarifRetour = () => {
  if (!form.date_fin) return 0
  const jourRetour = getJourRetourFromDate(form.date_fin)
  if (jourRetour === 'lundi_vendredi') return 1.25
  if (jourRetour === 'samedi') return 1.00
  if (jourRetour === 'dimanche') return 1.50
  return 0
}

const getJourRetourLabel = () => {
  if (!form.date_fin) return 'Non définie'
  const jourRetour = getJourRetourFromDate(form.date_fin)
  if (jourRetour === 'lundi_vendredi') return 'Lundi - Vendredi'
  if (jourRetour === 'samedi') return 'Samedi'
  if (jourRetour === 'dimanche') return 'Dimanche'
  return 'Non définie'
}

// Computed
const isProfessional = computed(() => userType.value === 'professionnel')
const isAuthenticated = computed(() => auth.isAuthenticated)

const dureeLocation = computed(() => {
  if (!form.date_debut || !form.date_fin) return 0
  const debut = new Date(form.date_debut)
  const fin = new Date(form.date_fin)
  const diffTime = Math.abs(fin - debut)
  const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24))
  return diffDays + 1
})

const subtotalHT = computed(() => {
  const jours = dureeLocation.value || 1
  return cartItems.value.reduce((total, item) => {
    return total + (item.total_ht * jours)
  }, 0)
})

const subtotalTTC = computed(() => {
  const jours = dureeLocation.value || 1
  return cartItems.value.reduce((total, item) => {
    return total + (item.total_ttc * jours)
  }, 0)
})

const fraisLivraison = computed(() => {
  if (form.mode_livraison === 1) return 0
  if (!form.jour_livraison || !form.distance) return 0
  
  const jour = joursLivraison.find(j => j.value === form.jour_livraison)
  if (!jour) return 0
  
  return jour.tarifKm * form.distance
})

const fraisRetour = computed(() => {
  if (form.mode_retour === 1) return 0
  if (!form.date_fin || !form.distance) return 0
  
  const tarifRetour = getTarifRetour()
  return tarifRetour * form.distance
})

const remise = computed(() => {
  return appliedPromo.value?.remise || 0
})

const total = computed(() => {
  return subtotalTTC.value + fraisLivraison.value + fraisRetour.value - remise.value
})

const canSubmit = computed(() => {
  if (cartItems.value.length === 0) return false
  if (!form.date_debut || !form.date_fin) return false
  if (new Date(form.date_debut) < new Date(today)) return false
  if (new Date(form.date_fin) < new Date(form.date_debut)) return false
  
  if (form.mode_livraison === 2) {
    if (!form.jour_livraison) return false
    if (!form.distance || form.distance <= 0) return false
    if (form.distance > 50) return false
    
    if (isProfessional.value) {
      if (form.adresse_livraison_index === null) return false
    } else {
      if (!userAdresse.value || userAdresse.value.trim() === '') return false
    }
  }
  
  // Pour le retour à domicile, on a besoin de la distance (déjà définie) et de la date de fin
  if (form.mode_retour === 2) {
    if (!form.date_fin) return false
    if (!form.distance || form.distance <= 0) return false
    if (form.distance > 50) return false
  }
  
  return true
})

// Méthodes
const getAdresseRetour = () => {
  if (form.mode_livraison === 2) {
    if (isProfessional.value && adressesProfessionnel.value[form.adresse_livraison_index]) {
      return adressesProfessionnel.value[form.adresse_livraison_index].adresse
    } else if (userAdresse.value) {
      return userAdresse.value
    }
  }
  return 'Adresse de retrait en magasin'
}

const getJourLabel = (value) => {
  const jour = joursLivraison.find(j => j.value === value)
  return jour ? jour.label : value
}

const getTarifKm = (value) => {
  const jour = joursLivraison.find(j => j.value === value)
  return jour ? jour.tarifKm : 0
}

const formatDate = (dateString) => {
  if (!dateString) return ''
  return new Date(dateString).toLocaleDateString('fr-FR', {
    weekday: 'long',
    day: 'numeric',
    month: 'long'
  })
}

const selectLivraison = (mode) => {
  form.mode_livraison = mode
  if (mode === 1) {
    form.jour_livraison = null
    form.distance = 0
    form.adresse_livraison_id = null
    form.adresse_livraison_texte = ''
  }
}

const selectRetour = (mode) => {
  form.mode_retour = mode
}

const selectJourLivraison = (jour) => {
  form.jour_livraison = jour
  calculerFrais()
}

const selectAdresseProfessionnel = (index) => {
  form.adresse_livraison_index = index
  const adresse = adressesProfessionnel.value[index]
  form.adresse_livraison_id = adresse.id
  form.adresse_livraison_texte = adresse.adresse_complete
}

const calculerFrais = () => {
  // La distance est la même pour livraison et retour
  // Les frais sont recalculés automatiquement via les computed
  form.frais_livraison = fraisLivraison.value
  form.frais_retour = fraisRetour.value
}

// Charger le panier depuis l'API
const loadCart = async () => {
  if (!isAuthenticated.value) {
    router.push('/login?redirect=/checkout')
    return
  }
  
  loading.value.cart = true
  try {
    const response = await api.get('/panier')
    if (response.data.success) {
      cartItems.value = response.data.data.items.map(item => ({
        ...item,
        prix_journalier_ht: item.prix_unitaire_ht,
        prix_journalier_ttc: item.prix_unitaire_ttc,
        total_ht: item.total_ht,
        total_ttc: item.total_ttc,
        photo: item.photo
      }))
    }
  } catch (error) {
    console.error('Erreur chargement panier:', error)
    router.push('/panier')
  } finally {
    loading.value.cart = false
  }
}

// Charger les informations utilisateur
const loadUserInfo = async () => {
  loading.value.user = true
  try {
    const response = await api.get('/user')
    
    if (response.data?.success && response.data?.data) {
      const userData = response.data.data
      
      userInfos.value = userData
      userType.value = userData.type || 'particulier'
      userNom.value = userData.type === 'professionnel' 
        ? (userData.nom_societe || userData.nom_complet || '')
        : (userData.prenom && userData.nom ? `${userData.prenom} ${userData.nom}` : userData.nom_complet || '')
      
      userEmail.value = userData.email || ''
      
      if (userType.value === 'particulier') {
        userAdresse.value = userData.adresse || ''
        userTelephone.value = userData.telephone || ''
      } else if (userType.value === 'professionnel') {
        userTelephone.value = userData.telephone || ''
        
        try {
          const adressesResponse = await api.get('/commandes/adresses')
          if (adressesResponse.data?.success && Array.isArray(adressesResponse.data.data)) {
            adressesProfessionnel.value = adressesResponse.data.data
            if (adressesProfessionnel.value.length > 0) {
              form.adresse_livraison_index = 0
              const premiereAdresse = adressesProfessionnel.value[0]
              form.adresse_livraison_id = premiereAdresse.id
              form.adresse_livraison_texte = premiereAdresse.adresse_complete
            }
          }
        } catch (adresseError) {
          console.error('Erreur chargement adresses:', adresseError)
        }
      }
    }
  } catch (error) {
    console.error('❌ Erreur chargement utilisateur:', error)
    if (error.response?.data?.message) {
      alert(`Erreur serveur: ${error.response.data.message}`)
    }
  } finally {
    loading.value.user = false
  }
}

// Soumettre la commande
const submitOrder = async () => {
  if (!canSubmit.value) return
  
  submitting.value = true
  
  try {
    const items = cartItems.value.map(item => ({
      materiel_id: item.materiel_id,
      quantite: item.quantite,
      prix_unitaire: item.prix_unitaire_ht
    }))
    
    const commandeData = {
      date_debut: form.date_debut,
      date_fin: form.date_fin,
      mode_livraison: form.mode_livraison,
      mode_retour: form.mode_retour,
      frais_livraison: fraisLivraison.value,
      frais_retour: fraisRetour.value,
      items
    }
    
    if (form.mode_livraison === 2) {
      commandeData.jour_livraison = form.jour_livraison
      commandeData.distance_livraison = form.distance
      if (isProfessional.value) {
        commandeData.adresse_livraison_id = form.adresse_livraison_id
      }
    }
    
    // Pour le retour, on utilise la même distance et le jour basé sur la date de fin
    if (form.mode_retour === 2) {
      const jourRetour = getJourRetourFromDate(form.date_fin)
      commandeData.jour_retour = jourRetour
      commandeData.distance_retour = form.distance
      // Réutiliser la même adresse que la livraison
      if (form.mode_livraison === 2 && form.adresse_livraison_id) {
        commandeData.adresse_retour_id = form.adresse_livraison_id
      }
    }
    
    if (form.code_reduction) {
      commandeData.code_reduction = form.code_reduction
    }
    
    if (form.notes) {
      commandeData.notes = form.notes
    }
    
    console.log('📤 Données envoyées:', commandeData)
    
    const response = await api.post('/commandes', commandeData)
    
    if (response.data.success) {
      await api.delete('/panier')
      window.dispatchEvent(new CustomEvent('cartUpdated'))
      
      orderNumber.value = response.data.data.numero_commande
      orderId.value = response.data.data.id
      orderSuccess.value = true
    }
  } catch (error) {
    console.error('❌ Erreur commande:', error)
    
    if (error.response?.data?.message) {
      alert(`Erreur: ${error.response.data.message}`)
    } else if (error.response?.data?.error) {
      alert(`Erreur technique: ${error.response.data.error}`)
    } else {
      alert('Erreur inconnue lors de la création de la commande')
    }
  } finally {
    submitting.value = false
  }
}

// Formatage prix
const formatPrice = (price) => {
  return new Intl.NumberFormat('fr-FR', {
    style: 'currency',
    currency: 'EUR',
    minimumFractionDigits: 2
  }).format(price)
}

// Initialisation
onMounted(() => {
  if (!isAuthenticated.value) {
    router.push('/login?redirect=/checkout')
    return
  }
  loadCart()
  loadUserInfo()
  
  const today = new Date()
  const tomorrow = new Date(today)
  tomorrow.setDate(tomorrow.getDate() + 1)
  const nextWeek = new Date(today)
  nextWeek.setDate(nextWeek.getDate() + 7)
  
  form.date_debut = tomorrow.toISOString().split('T')[0]
  form.date_fin = nextWeek.toISOString().split('T')[0]
})
</script>

<style scoped>
/* Styles existants - ajoutez ces nouveaux styles */

.section-subtitle {
  font-size: 0.8rem;
  color: #6b7280;
  margin-left: 10px;
  font-weight: normal;
}

.retour-details {
  margin-top: 15px;
}

.info-box {
  display: flex;
  gap: 12px;
  padding: 15px;
  background: #f0f9ff;
  border-radius: 10px;
  border-left: 4px solid #3b82f6;
  margin-bottom: 15px;
}

.info-icon {
  font-size: 1.2rem;
}

.info-content {
  flex: 1;
}

.info-content strong {
  display: block;
  margin-bottom: 5px;
  color: #1e40af;
}

.info-content p {
  margin: 5px 0;
  color: #4b5563;
  font-size: 0.9rem;
}

.info-content .warning-text {
  color: #d97706;
  margin-top: 8px;
}

/* ... reste des styles existants ... */
</style>

<style scoped>
/* Styles existants - ajoutez ces nouveaux styles */
.section-subtitle {
  font-size: 0.8rem;
  color: #6b7280;
  margin-left: 10px;
  font-weight: normal;
}

.retour-details {
  margin-top: 15px;
}

.info-box {
  display: flex;
  gap: 12px;
  padding: 15px;
  background: #f0f9ff;
  border-radius: 10px;
  border-left: 4px solid #3b82f6;
  margin-bottom: 15px;
}

.info-icon {
  font-size: 1.2rem;
}

.info-content {
  flex: 1;
}

.info-content strong {
  display: block;
  margin-bottom: 5px;
  color: #1e40af;
}

.info-content p {
  margin: 5px 0;
  color: #4b5563;
  font-size: 0.9rem;
}

.info-content .warning-text {
  color: #d97706;
  margin-top: 8px;
}

.tarif-preview.free {
  background: #f0fdf4;
  border-left-color: #22c55e;
}

.tarif-total.free .tarif-montant {
  color: #16a34a;
}

.section-subtitle {
  font-size: 0.8rem;
  color: #6b7280;
  margin-left: 10px;
  font-weight: normal;
}

.retour-details {
  margin-top: 15px;
}

.info-box {
  display: flex;
  gap: 12px;
  padding: 15px;
  background: #f0f9ff;
  border-radius: 10px;
  border-left: 4px solid #3b82f6;
  margin-bottom: 15px;
}

.info-icon {
  font-size: 1.2rem;
}

.info-content {
  flex: 1;
}

.info-content strong {
  display: block;
  margin-bottom: 5px;
  color: #1e40af;
}

.info-content p {
  margin: 5px 0;
  color: #4b5563;
  font-size: 0.9rem;
}

.info-content .warning-text {
  color: #d97706;
  margin-top: 8px;
}

.tarif-preview.free {
  background: #f0fdf4;
  border-left-color: #22c55e;
}

.tarif-total.free .tarif-montant {
  color: #16a34a;
}

/* ... reste des styles existants ... */
</style>

<style scoped>
/* Vos styles existants, avec ajouts pour les codes promo */
.promo-grid {
  display: flex;
  gap: 10px;
  flex-wrap: wrap;
  margin-bottom: 15px;
}

.promo-badge {
  border: 2px dashed #ccc;
  padding: 8px 15px;
  border-radius: 8px;
  cursor: pointer;
  transition: all 0.3s;
}
.order-summary-mini {
  background: #f8f9fa;
  border-radius: 8px;
  padding: 16px;
  margin: 16px 0;
  width: 100%;
}

.summary-item {
  display: flex;
  justify-content: space-between;
  padding: 8px 0;
  border-bottom: 1px dashed #e0e0e0;
}

.summary-item:last-child {
  border-bottom: none;
}

.summary-label {
  color: #666;
  font-weight: 500;
}

.summary-value {
  font-weight: 600;
  color: #333;
}

.redirect-message {
  margin-top: 20px;
  color: #999;
  font-size: 0.9em;
}

.countdown {
  font-weight: 700;
  color: var(--primary-color, #4CAF50);
}
.promo-badge.active {
  border-color: #3490dc;
  background-color: #ebf8ff;
}
.promo-badge.applied {
  border-color: #38c172;
  background-color: #e3fcec;
  border-style: solid;
}
.no-promo-hint {
  font-size: 0.9em;
  color: #777;
  font-style: italic;
}
.item-meta-ttc {
  font-size: 0.8rem;
  color: #10b981;
}
.price-ht {
  font-size: 1rem;
  font-weight: 600;
}
.price-ttc {
  font-size: 0.9rem;
  color: #10b981;
}
/* ... le reste de vos styles existants ... */
</style>
<style scoped>
/* Votre CSS existant - Ajout des nouveaux styles */
.dates-grid {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 20px;
  margin-bottom: 15px;
}
.promo-grid {
  display: flex;
  gap: 10px;
  flex-wrap: wrap;
  margin-bottom: 15px;
}
.promo-badge {
  border: 2px dashed #ccc;
  padding: 8px 15px;
  border-radius: 8px;
  cursor: pointer;
  transition: all 0.3s;
}
.promo-badge.active {
  border-color: #3490dc;
  background-color: #ebf8ff;
}
.promo-badge.applied {
  border-color: #38c172;
  background-color: #e3fcec;
  border-style: solid;
}
.no-promo-hint {
  font-size: 0.9em;
  color: #777;
  font-style: italic;
}
.date-input {
  width: 100%;
  padding: 12px 15px;
  border: 2px solid #e2e8f0;
  border-radius: 10px;
  font-size: 1rem;
}

.date-input:focus {
  outline: none;
  border-color: #667eea;
}

.duree-info {
  display: flex;
  align-items: center;
  gap: 10px;
  padding: 12px 15px;
  background: #e6f7ff;
  border: 1px solid #91d5ff;
  border-radius: 10px;
  color: #0050b3;
  font-weight: 500;
}

.duree-icon {
  font-size: 1.2rem;
}

.date-warning {
  padding: 10px;
  color: #fa8c16;
  font-size: 0.9rem;
  background: #fff7e6;
  border-radius: 8px;
  text-align: center;
}

/* Affichage du nom de société dans les adresses */
.address-company {
  display: flex;
  align-items: center;
  gap: 8px;
  margin-bottom: 8px;
  font-size: 1.05rem;
  color: #1f2937;
}

.company-icon {
  font-size: 1.2rem;
}

.address-company strong {
  font-weight: 700;
}

.address-type {
  display: flex;
  align-items: center;
  gap: 6px;
  margin-top: 8px;
  padding-top: 8px;
  border-top: 1px solid #f3f4f6;
  font-size: 0.85rem;
  color: #6b7280;
  font-style: italic;
}

.type-icon {
  font-size: 1rem;
}

/* Amélioration des badges */
.badge-secondary {
  position: absolute;
  top: 15px;
  right: 15px;
  background: #f3f4f6;
  color: #4b5563;
  padding: 4px 10px;
  border-radius: 15px;
  font-size: 0.75rem;
  font-weight: 600;
}

/* Le reste de votre CSS existant */
.checkout-container {
  max-width: 1200px;
  margin: 0 auto;
  padding: 20px;
}

.checkout-header {
  text-align: center;
  margin-bottom: 40px;
}

.checkout-header h1 {
  font-size: 2.5rem;
  color: #2d3748;
  margin-bottom: 10px;
}

.checkout-header p {
  color: #718096;
  font-size: 1.1rem;
}

.checkout-content {
  display: grid;
  grid-template-columns: 1fr 380px;
  gap: 30px;
}

/* Formulaire */
.checkout-form {
  display: flex;
  flex-direction: column;
  gap: 30px;
}

.form-section {
  background: white;
  border-radius: 15px;
  padding: 25px;
  box-shadow: 0 2px 10px rgba(0,0,0,0.05);
  border: 1px solid #e2e8f0;
}

.section-header {
  display: flex;
  align-items: center;
  gap: 12px;
  margin-bottom: 20px;
}

.section-icon {
  font-size: 1.5rem;
}

.section-header h2 {
  font-size: 1.3rem;
  color: #2d3748;
  margin: 0;
}

/* Options */
.options-grid {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 15px;
}

.option-card {
  display: flex;
  gap: 15px;
  padding: 20px;
  border: 2px solid #e2e8f0;
  border-radius: 12px;
  cursor: pointer;
  transition: all 0.3s;
}

.option-card:hover {
  border-color: #cbd5e0;
}

.option-card.active {
  border-color: #667eea;
  background: #f7fafc;
}

.option-radio {
  display: flex;
  align-items: center;
}

.radio-circle {
  width: 20px;
  height: 20px;
  border: 2px solid #cbd5e0;
  border-radius: 50%;
  position: relative;
}

.radio-circle.selected {
  border-color: #667eea;
}

.radio-circle.selected::after {
  content: '';
  position: absolute;
  top: 50%;
  left: 50%;
  transform: translate(-50%, -50%);
  width: 10px;
  height: 10px;
  background: #667eea;
  border-radius: 50%;
}

.option-content {
  display: flex;
  gap: 12px;
  flex: 1;
}

.option-icon {
  font-size: 1.5rem;
}

.option-text h3 {
  font-size: 1rem;
  color: #2d3748;
  margin: 0 0 5px 0;
}

.option-text p {
  font-size: 0.9rem;
  color: #718096;
  margin: 0 0 10px 0;
}

.option-price {
  font-size: 0.9rem;
  font-weight: 700;
  color: #10b981;
}

/* Adresses */
.address-section {
  margin-top: 20px;
  padding-top: 20px;
  border-top: 1px solid #e2e8f0;
}

.address-section h3 {
  font-size: 1.1rem;
  color: #2d3748;
  margin-bottom: 15px;
}

.addresses-list {
  display: flex;
  flex-direction: column;
  gap: 15px;
}

.address-card {
  display: flex;
  gap: 15px;
  padding: 15px;
  border: 2px solid #e2e8f0;
  border-radius: 12px;
  cursor: pointer;
  transition: all 0.3s;
  position: relative;
}

.address-card:hover {
  border-color: #cbd5e0;
}

.address-card.active {
  border-color: #667eea;
  background: #f7fafc;
}

.address-content {
  flex: 1;
}

.address-name {
  font-weight: 600;
  color: #2d3748;
  margin-bottom: 5px;
}

.address-line {
  color: #718096;
  font-size: 0.9rem;
  margin-bottom: 2px;
}

.address-phone {
  color: #4a5568;
  font-size: 0.9rem;
  margin-top: 5px;
}

.badge-primary {
  position: absolute;
  top: 15px;
  right: 15px;
  background: #667eea;
  color: white;
  padding: 4px 10px;
  border-radius: 15px;
  font-size: 0.75rem;
  font-weight: 600;
}

.btn-add-address {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 8px;
  padding: 12px;
  background: white;
  color: #667eea;
  border: 2px dashed #cbd5e0;
  border-radius: 12px;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.3s;
}

.btn-add-address:hover {
  border-color: #667eea;
  background: #f7fafc;
}

.address-missing {
  display: flex;
  align-items: center;
  gap: 15px;
  padding: 20px;
  border-radius: 12px;
}

.address-missing.warning {
  background: #fef3c7;
  border: 1px solid #fbbf24;
  color: #92400e;
}

.address-missing.error {
  background: #fee2e2;
  border: 1px solid #ef4444;
  color: #b91c1c;
}

.missing-icon {
  font-size: 2rem;
}

.missing-text {
  flex: 1;
}

.missing-text strong {
  display: block;
  margin-bottom: 5px;
}

.missing-text p {
  margin: 0;
  font-size: 0.9rem;
  opacity: 0.9;
}

.btn-profile {
  padding: 8px 16px;
  background: white;
  border-radius: 8px;
  text-decoration: none;
  font-weight: 600;
  font-size: 0.9rem;
  box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.address-missing.warning .btn-profile {
  color: #92400e;
  border: 1px solid #fbbf24;
}

.address-missing.error .btn-profile {
  color: #b91c1c;
  border: 1px solid #ef4444;
}

/* Code promo */
.promo-code {
  margin-top: 15px;
}

.promo-input-group {
  display: flex;
  gap: 10px;
}

.promo-input-group input {
  flex: 1;
  padding: 12px 15px;
  border: 2px solid #e2e8f0;
  border-radius: 10px;
  font-size: 1rem;
}

.promo-input-group input:focus {
  outline: none;
  border-color: #667eea;
}

.promo-input-group input:disabled {
  background: #f7fafc;
  opacity: 0.7;
}

.btn-apply {
  padding: 12px 24px;
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  color: white;
  border: none;
  border-radius: 10px;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.3s;
  min-width: 120px;
}

.btn-apply:hover:not(:disabled) {
  transform: translateY(-2px);
  box-shadow: 0 4px 15px rgba(102,126,234,0.3);
}

.btn-apply:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}

.promo-applied {
  display: flex;
  align-items: center;
  gap: 12px;
  margin-top: 15px;
  padding: 12px 15px;
  background: #d1fae5;
  border: 1px solid #a7f3d0;
  border-radius: 10px;
  color: #065f46;
}

.applied-icon {
  font-size: 1.1rem;
}

.applied-text {
  flex: 1;
  font-weight: 500;
}

.applied-discount {
  font-weight: 700;
}

.btn-remove-promo {
  width: 30px;
  height: 30px;
  background: rgba(220,38,38,0.1);
  color: #dc2626;
  border: none;
  border-radius: 50%;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
}

.promo-error {
  display: flex;
  align-items: center;
  gap: 8px;
  margin-top: 10px;
  color: #dc2626;
  font-size: 0.9rem;
}

/* Notes */
.notes-input {
  width: 100%;
  padding: 15px;
  border: 2px solid #e2e8f0;
  border-radius: 10px;
  font-size: 0.95rem;
  resize: vertical;
}

.notes-input:focus {
  outline: none;
  border-color: #667eea;
}

/* Récapitulatif */
.checkout-summary {
  position: sticky;
  top: 20px;
  align-self: start;
}

.summary-card {
  background: white;
  border-radius: 15px;
  padding: 25px;
  box-shadow: 0 4px 20px rgba(0,0,0,0.08);
  border: 1px solid #e2e8f0;
}

.summary-header {
  display: flex;
  align-items: center;
  gap: 12px;
  margin-bottom: 25px;
  padding-bottom: 20px;
  border-bottom: 1px solid #e2e8f0;
}

.summary-icon {
  font-size: 1.5rem;
}

.summary-header h2 {
  font-size: 1.2rem;
  color: #2d3748;
  margin: 0;
}

.summary-items {
  max-height: 300px;
  overflow-y: auto;
  margin-bottom: 25px;
}

.summary-item {
  display: flex;
  gap: 12px;
  padding: 12px 0;
  border-bottom: 1px solid #f1f5f9;
}

.summary-item:last-child {
  border-bottom: none;
}

.item-image {
  width: 50px;
  height: 50px;
  border-radius: 8px;
  overflow: hidden;
  background: #f8fafc;
}

.item-image img {
  width: 100%;
  height: 100%;
  object-fit: cover;
}

.item-details {
  flex: 1;
}

.item-name {
  font-weight: 600;
  color: #2d3748;
  margin-bottom: 4px;
}

.item-meta {
  font-size: 0.85rem;
  color: #718096;
}

.item-price {
  font-weight: 600;
  color: #10b981;
}

.price-details {
  margin-bottom: 25px;
}

.price-row {
  display: flex;
  justify-content: space-between;
  padding: 10px 0;
  border-bottom: 1px solid #f1f5f9;
}

.price-row:last-child {
  border-bottom: none;
}

.price-label {
  color: #4a5568;
}

.price-info {
  font-size: 0.85rem;
  color: #718096;
  margin-left: 5px;
}

.price-value {
  font-weight: 600;
  color: #2d3748;
}

.price-row.discount .price-value {
  color: #10b981;
}

.discount-icon {
  margin-right: 5px;
}

.total-section {
  background: #f8fafc;
  border-radius: 12px;
  padding: 20px;
  margin-bottom: 25px;
}

.total-row {
  display: flex;
  justify-content: space-between;
  align-items: baseline;
}

.total-label {
  font-size: 1.2rem;
  font-weight: 700;
  color: #2d3748;
}

.total-amount {
  font-size: 1.8rem;
  font-weight: 800;
  color: #10b981;
}

.total-tax {
  font-size: 0.85rem;
  color: #718096;
  margin-top: 5px;
  text-align: right;
}

.btn-submit {
  width: 100%;
  padding: 16px;
  background: linear-gradient(135deg, #10b981 0%, #34d399 100%);
  color: white;
  border: none;
  border-radius: 12px;
  font-size: 1.1rem;
  font-weight: 700;
  cursor: pointer;
  transition: all 0.3s;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 12px;
  margin-bottom: 20px;
}

.btn-submit:hover:not(:disabled) {
  transform: translateY(-2px);
  box-shadow: 0 8px 25px rgba(16,185,129,0.3);
}

.btn-submit:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}

.btn-submit.loading {
  opacity: 0.7;
  cursor: wait;
}

/* Garanties */
.guarantees {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: 15px;
  padding-top: 20px;
  border-top: 1px solid #e2e8f0;
}

.guarantee {
  display: flex;
  flex-direction: column;
  align-items: center;
  text-align: center;
  gap: 5px;
  color: #4a5568;
  font-size: 0.85rem;
}

.guarantee-icon {
  font-size: 1.2rem;
}

/* Succès */
.success-overlay {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: rgba(255,255,255,0.95);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 2000;
}

.success-modal {
  text-align: center;
  max-width: 400px;
  padding: 40px;
  background: white;
  border-radius: 20px;
  box-shadow: 0 20px 40px rgba(0,0,0,0.1);
}

.success-icon {
  font-size: 4rem;
  margin-bottom: 20px;
  animation: bounce 1s;
}

.success-modal h2 {
  font-size: 1.8rem;
  color: #2d3748;
  margin-bottom: 10px;
}

.success-modal p {
  color: #718096;
  margin-bottom: 30px;
}

.success-actions {
  display: flex;
  gap: 15px;
  justify-content: center;
}

.delivery-details-section {
  margin-top: 20px;
  padding-top: 20px;
  border-top: 1px solid #e2e8f0;
}

.delivery-details-section h3 {
  font-size: 1.1rem;
  color: #2d3748;
  margin-bottom: 20px;
}

.day-selector {
  display: flex;
  gap: 10px;
  flex-wrap: wrap;
}

.day-btn {
  padding: 10px 20px;
  background: white;
  border: 2px solid #e2e8f0;
  border-radius: 25px;
  font-size: 0.95rem;
  color: #4a5568;
  cursor: pointer;
  transition: all 0.3s;
  flex: 1;
  min-width: 150px;
}

.day-btn:hover {
  border-color: #667eea;
  background: #f7fafc;
}

.day-btn.active {
  background: #667eea;
  color: white;
  border-color: #667eea;
}

.distance-input-group {
  position: relative;
  display: flex;
  align-items: center;
}

.distance-input {
  width: 100%;
  padding: 12px 15px;
  padding-right: 60px;
  border: 2px solid #e2e8f0;
  border-radius: 10px;
  font-size: 1rem;
}

.distance-input:focus {
  outline: none;
  border-color: #667eea;
}

.distance-unit {
  position: absolute;
  right: 15px;
  top: 50%;
  transform: translateY(-50%);
  color: #718096;
  font-weight: 600;
}

.distance-info {
  margin-top: 8px;
  color: #dc2626;
  font-size: 0.85rem;
}

.tarif-preview {
  margin-top: 15px;
  padding: 15px;
  background: #f8fafc;
  border-radius: 10px;
  border: 1px solid #e2e8f0;
}

.tarif-header {
  display: flex;
  align-items: center;
  gap: 8px;
  margin-bottom: 10px;
  color: #2d3748;
  font-weight: 600;
}

.tarif-icon {
  font-size: 1.2rem;
}

.tarif-details {
  display: flex;
  flex-direction: column;
  gap: 8px;
}

.tarif-row {
  display: flex;
  justify-content: space-between;
  color: #4a5568;
  font-size: 0.95rem;
}

.tarif-total {
  display: flex;
  justify-content: space-between;
  margin-top: 8px;
  padding-top: 8px;
  border-top: 1px dashed #cbd5e0;
  font-weight: 600;
  color: #2d3748;
}

.tarif-montant {
  color: #10b981;
  font-size: 1.2rem;
}

/* Loading */
.loading-small {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 10px;
  padding: 20px;
  color: #718096;
}

.spinner-small {
  width: 20px;
  height: 20px;
  border: 2px solid #e2e8f0;
  border-top-color: #667eea;
  border-radius: 50%;
  animation: spin 0.8s linear infinite;
}

@keyframes spin {
  to { transform: rotate(360deg); }
}

@keyframes bounce {
  0%, 100% { transform: translateY(0); }
  50% { transform: translateY(-20px); }
}

/* Field help */
.field-help {
  margin-top: 5px;
  font-size: 0.85rem;
  color: #718096;
}

.tarif-placeholder {
  display: flex;
  align-items: center;
  gap: 10px;
  padding: 15px;
  background: #f8fafc;
  border-radius: 10px;
  color: #718096;
  font-size: 0.95rem;
}

.placeholder-icon {
  font-size: 1.2rem;
}

/* Responsive */
@media (max-width: 992px) {
  .checkout-content {
    grid-template-columns: 1fr;
  }
  
  .checkout-summary {
    position: static;
  }
}

@media (max-width: 768px) {
  .options-grid {
    grid-template-columns: 1fr;
  }
  
  .dates-grid {
    grid-template-columns: 1fr;
  }
  
  .guarantees {
    grid-template-columns: 1fr;
  }
  
  .success-actions {
    flex-direction: column;
  }
  
  .day-selector {
    flex-direction: column;
  }
  
  .day-btn {
    width: 100%;
  }
  
  .address-missing {
    flex-direction: column;
    text-align: center;
  }
}
</style>