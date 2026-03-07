<template>
  <div class="commande-detail-container">
    <!-- Loading -->
    <div v-if="loading" class="loading-container">
      <div class="loading-spinner">
        <div class="spinner-ring"></div>
        <div class="spinner-ring"></div>
        <div class="spinner-ring"></div>
      </div>
      <p class="loading-text">Chargement de la commande...</p>
    </div>

    <!-- Erreur -->
    <div v-else-if="error" class="error-container">
      <div class="error-icon">❌</div>
      <h2>Erreur</h2>
      <p>{{ error }}</p>
      <router-link to="/commandes" class="btn-back">
        <span class="btn-icon">←</span>
        Retour aux commandes
      </router-link>
    </div>

    <!-- Détails de la commande -->
    <div v-else-if="commande" class="commande-detail">
      <!-- Header avec infos client -->
      <div class="detail-header">
        <div class="header-left">
          <router-link to="/commandes" class="btn-back-link">
            <span class="back-icon">←</span>
            Retour
          </router-link>
          <div class="header-info">
            <h1>Commande {{ commande.numero_commande }}</h1>
            <div class="client-badge" :class="commande.client?.type">
              <span class="client-icon">{{ commande.client?.type === 'professionnel' ? '🏢' : '👤' }}</span>
              <span class="client-name">{{ commande.client?.nom_complet }}</span>
            </div>
            <p class="commande-date">
              Passée le {{ commande.date_commande }}
            </p>
          </div>
        </div>
        <div class="header-right">
          <span class="status-badge" :class="getStatutClass(commande.statut)">
            {{ commande.statut_libelle || getStatutLabel(commande.statut) }}
          </span>
        </div>
      </div>

      <!-- Grille principale -->
      <div class="detail-grid">
        <!-- Colonne gauche -->
        <div class="detail-main">
          <!-- Période de location -->
          <div class="detail-card">
            <div class="card-header">
              <span class="card-icon">📅</span>
              <h2>Période de location</h2>
            </div>
            <div class="card-content">
              <div class="period-info">
                <div class="period-item">
                  <span class="period-label">Début :</span>
                  <span class="period-value">{{ commande.date_debut }}</span>
                </div>
                <div class="period-separator">→</div>
                <div class="period-item">
                  <span class="period-label">Fin :</span>
                  <span class="period-value">{{ commande.date_fin }}</span>
                </div>
              </div>
              <div class="period-duration">
                <span class="duration-icon">⏱️</span>
                <span class="duration-text">Durée : <strong>{{ commande.duree  }}</strong> ({{ commande.nombre_jours }} jour{{ commande.nombre_jours > 1 ? 's' : '' }} de location)</span>
              </div>
            </div>
          </div>

          <!-- Matériels loués avec photos -->
          <div class="detail-card">
            <div class="card-header">
              <span class="card-icon">📦</span>
              <h2>Matériels loués</h2>
            </div>
            <div class="card-content">
              <div class="materiels-list">
                <div 
                  v-for="article in commande.articles" 
                  :key="article.id"
                  class="materiel-item"
                >
                  <div class="materiel-image">
                    <img 
                      :src="article.photo || getDefaultImage(article)" 
                      :alt="article.nom"
                      @error="setDefaultImage"
                    >
                  </div>
                  <div class="materiel-info">
                    <h3 class="materiel-name">{{ article.nom }}</h3>
                    <div class="materiel-details">
                      <span class="detail-item">
                        <span class="detail-label">Quantité :</span>
                        <span class="detail-value">{{ article.quantite }}</span>
                      </span>
                      <span class="detail-item">
                        <span class="detail-label">Prix HT/jour :</span>
                        <span class="detail-value">{{ formatPrice(article.prix_unitaire_ht) }}</span>
                      </span>
                      <span class="detail-item highlight">
                        <span class="detail-label">Nb jours :</span>
                        <span class="detail-value">{{ article.nombre_jours || commande.nombre_jours }}</span>
                      </span>
                      <span class="detail-item">
                        <span class="detail-label">TVA :</span>
                        <span class="detail-value">{{ article.taux_tva }}%</span>
                      </span>
                    </div>
                    <!-- Calcul détaillé -->
                    <div class="calcul-detail">
                      <span class="calcul-formula">
                        {{ formatPrice(article.prix_unitaire_ht) }} × {{ article.quantite }} unité{{ article.quantite > 1 ? 's' : '' }} × {{ article.nombre_jours || commande.nombre_jours }} jour{{ (article.nombre_jours || commande.nombre_jours) > 1 ? 's' : '' }}
                      </span>
                    </div>
                  </div>
                  <div class="materiel-pricing">
                    <div class="pricing-row">
                      <span class="pricing-label">Sous-total HT :</span>
                      <span class="pricing-value">{{ formatPrice(article.sous_total_ht) }}</span>
                    </div>
                    <div class="pricing-row">
                      <span class="pricing-label">TVA :</span>
                      <span class="pricing-value">{{ formatPrice(article.montant_tva) }}</span>
                    </div>
                    <div class="pricing-row total">
                      <span class="pricing-label">Total TTC :</span>
                      <span class="pricing-value">{{ formatPrice(article.sous_total_ttc) }}</span>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- Livraison et Retour -->
          <div class="detail-card">
            <div class="card-header">
              <span class="card-icon">🚚</span>
              <h2>Livraison & Retour</h2>
            </div>
            <div class="card-content">
              <div class="delivery-grid">
                <!-- Livraison -->
                <div class="delivery-section">
                  <h3 class="delivery-title">
                    <span class="delivery-icon">📦</span>
                    Livraison
                  </h3>
                  <div class="delivery-info">
                    <div class="info-item">
                      <span class="info-label">Mode :</span>
                      <span class="info-value">
                        {{ commande.mode_livraison_libelle }}
                      </span>
                    </div>
                    <div v-if="commande.mode_livraison === 2" class="info-details">
                      <div class="info-item">
                        <span class="info-label">Adresse :</span>
                        <span class="info-value">{{ commande.adresse_livraison?.adresse_complete || commande.adresse_livraison }}</span>
                      </div>
                      <div v-if="commande.details_livraison">
                        <div class="info-item" v-if="commande.details_livraison.jour">
                          <span class="info-label">Jour :</span>
                          <span class="info-value">{{ formatJour(commande.details_livraison.jour) }}</span>
                        </div>
                        <div class="info-item" v-if="commande.details_livraison.distance">
                          <span class="info-label">Distance :</span>
                          <span class="info-value">{{ commande.details_livraison.distance }} km</span>
                        </div>
                      </div>
                      <div class="info-item">
                        <span class="info-label">Frais :</span>
                        <span class="info-value price">{{ formatPrice(commande.frais_livraison) }}</span>
                      </div>
                    </div>
                    <div v-else class="info-free">
                      <span class="free-icon">✓</span>
                      <span>Gratuit</span>
                    </div>
                  </div>
                </div>

                <!-- Retour -->
                <div class="delivery-section">
                  <h3 class="delivery-title">
                    <span class="delivery-icon">🔄</span>
                    Retour
                  </h3>
                  <div class="delivery-info">
                    <div class="info-item">
                      <span class="info-label">Mode :</span>
                      <span class="info-value">
                        {{ commande.mode_retour_libelle }}
                      </span>
                    </div>
                    <div v-if="commande.mode_retour === 2" class="info-details">
                      <div v-if="commande.details_retour">
                        <div class="info-item" v-if="commande.details_retour.jour">
                          <span class="info-label">Jour :</span>
                          <span class="info-value">{{ formatJour(commande.details_retour.jour) }}</span>
                        </div>
                        <div class="info-item" v-if="commande.details_retour.distance">
                          <span class="info-label">Distance :</span>
                          <span class="info-value">{{ commande.details_retour.distance }} km</span>
                        </div>
                      </div>
                      <div class="info-item" v-if="commande.frais_retour">
                        <span class="info-label">Frais :</span>
                        <span class="info-value price">{{ formatPrice(commande.frais_retour) }}</span>
                      </div>
                    </div>
                    <div v-else class="info-free">
                      <span class="free-icon">✓</span>
                      <span>Gratuit</span>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- Notes -->
          <div v-if="commande.notes" class="detail-card">
            <div class="card-header">
              <span class="card-icon">📝</span>
              <h2>Notes</h2>
            </div>
            <div class="card-content">
              <p class="notes-content">{{ commande.notes }}</p>
            </div>
          </div>
        </div>

        <!-- Colonne droite - Résumé -->
        <div class="detail-sidebar">
          <!-- Résumé financier -->
          <div class="summary-card sticky">
            <div class="summary-header">
              <span class="summary-icon">💰</span>
              <h2>Résumé financier</h2>
            </div>

            <div class="summary-details">
              <div class="summary-row info-row-duration">
                <span class="summary-label">📅 Durée de location</span>
                <span class="summary-value highlight-days">{{ commande.nombre_jours }} jour{{ commande.nombre_jours > 1 ? 's' : '' }}</span>
              </div>

              <div class="summary-divider"></div>

              <div class="summary-row">
                <span class="summary-label">Sous-total HT</span>
                <span class="summary-value">{{ formatPrice(commande.totaux?.sous_total_ht) }}</span>
              </div>

              <div class="summary-row">
                <span class="summary-label">TVA totale</span>
                <span class="summary-value">{{ formatPrice(commande.totaux?.total_tva) }}</span>
              </div>

              <div class="summary-row">
                <span class="summary-label">Sous-total TTC</span>
                <span class="summary-value">{{ formatPrice(commande.totaux?.sous_total_ttc) }}</span>
              </div>

              <div class="summary-divider"></div>

              <div class="summary-row" v-if="commande.frais_livraison > 0">
                <span class="summary-label">Frais de livraison</span>
                <span class="summary-value">{{ formatPrice(commande.frais_livraison) }}</span>
              </div>

              <div class="summary-row" v-if="commande.frais_retour > 0">
                <span class="summary-label">Frais de retour</span>
                <span class="summary-value">{{ formatPrice(commande.frais_retour) }}</span>
              </div>

              <div v-if="commande.code_reduction" class="summary-row discount">
                <span class="summary-label">
                  <span class="discount-icon">🎁</span>
                  Réduction ({{ commande.code_reduction.code }})
                </span>
                <span class="summary-value">-{{ formatPrice(commande.totaux?.remise) }}</span>
              </div>

              <div class="summary-divider"></div>

              <div class="summary-total">
                <span class="total-label">Total TTC</span>
                <span class="total-value">{{ formatPrice(commande.montant_total) }}</span>
              </div>
            </div>

            <!-- Actions -->
            <div class="summary-actions">
              <button 
                v-if="canCancelOrder()"
                @click="cancelOrder"
                :disabled="cancelling"
                class="btn-cancel"
              >
                <span v-if="!cancelling" class="btn-icon">🚫</span>
                <span v-else class="spinner-small"></span>
                {{ cancelling ? 'Annulation...' : 'Annuler la commande' }}
              </button>

              <button @click="downloadInvoice" class="btn-invoice" :disabled="downloading">
                <span v-if="!downloading" class="btn-icon">📄</span>
                <span v-else class="spinner-small"></span>
                {{ downloading ? 'Génération...' : 'Télécharger la facture' }}
              </button>

              <button @click="contactSupport" class="btn-support">
                <span class="btn-icon">💬</span>
                Contacter le support
              </button>
            </div>
          </div>

          <!-- Informations supplémentaires -->
          <div class="info-card">
            <div class="info-card-header">
              <span class="info-icon">ℹ️</span>
              <h3>Informations</h3>
            </div>
            <div class="info-card-content">
              <div class="info-row">
                <span class="info-label">Référence :</span>
                <span class="info-value mono">{{ commande.numero_commande }}</span>
              </div>
              <div class="info-row">
                <span class="info-label">Date :</span>
                <span class="info-value">{{ commande.date_commande }}</span>
              </div>
              <div class="info-row">
                <span class="info-label">Client :</span>
                <span class="info-value">{{ commande.client?.nom_complet }}</span>
              </div>
              <div class="info-row" v-if="commande.client?.telephone">
                <span class="info-label">Tél :</span>
                <span class="info-value">{{ commande.client.telephone }}</span>
              </div>
              <div class="info-row">
                <span class="info-label">Statut :</span>
                <span class="info-value">
                  <span class="status-badge-inline" :class="getStatutClass(commande.statut)">
                    {{ commande.statut_libelle }}
                  </span>
                </span>
              </div>
            </div>
          </div>

          <!-- Aide -->
          <div class="help-card">
            <div class="help-icon">❓</div>
            <h3>Besoin d'aide ?</h3>
            <p>Notre équipe est disponible pour répondre à toutes vos questions sur cette commande.</p>
            <button @click="contactSupport" class="btn-help">
              Contactez-nous
            </button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import api from '@/services/axios'

const route = useRoute()
const router = useRouter()

const commande = ref(null)
const loading = ref(true)
const error = ref(null)
const cancelling = ref(false)
const downloading = ref(false)

// Charger la commande
const loadCommande = async () => {
  loading.value = true
  error.value = null
  
  try {
    const commandeId = route.params.id
    console.log('Chargement commande ID:', commandeId)
    
    const response = await api.get(`/commandes/${commandeId}`)
    console.log('Réponse API:', response.data)
    
    if (response.data.success) {
      commande.value = response.data.data
      console.log('Commande chargée:', commande.value)
    } else {
      error.value = response.data.message || 'Commande introuvable'
    }
  } catch (err) {
    console.error('Erreur chargement commande:', err)
    console.error('Détails:', err.response?.data)
    error.value = err.response?.data?.message || 'Erreur lors du chargement de la commande'
  } finally {
    loading.value = false
  }
}

// Vérifier si la commande peut être annulée
const canCancelOrder = () => {
  if (!commande.value) return false
  return [1, 2].includes(commande.value.statut)
}

// Annuler la commande
const cancelOrder = async () => {
  if (!confirm('Êtes-vous sûr de vouloir annuler cette commande ?')) return
  
  cancelling.value = true
  
  try {
    const response = await api.put(`/commandes/${commande.value.id}/annuler`)
    
    if (response.data.success) {
      alert('Commande annulée avec succès')
      await loadCommande()
    }
  } catch (err) {
    console.error('Erreur annulation:', err)
    alert(err.response?.data?.message || 'Erreur lors de l\'annulation')
  } finally {
    cancelling.value = false
  }
}

// Télécharger la facture
const downloadInvoice = async () => {
  downloading.value = true
  
  try {
    const response = await api.get(`/commandes/${commande.value.id}/facture`, {
      responseType: 'blob'
    })
    
    // Créer un lien de téléchargement
    const url = window.URL.createObjectURL(new Blob([response.data], { type: 'application/pdf' }))
    const link = document.createElement('a')
    link.href = url
    link.setAttribute('download', `facture_${commande.value.numero_commande}.pdf`)
    document.body.appendChild(link)
    link.click()
    link.remove()
    
    // Nettoyer l'URL
    window.URL.revokeObjectURL(url)
    
  } catch (err) {
    console.error('Erreur téléchargement facture:', err)
    alert(err.response?.data?.message || 'Erreur lors du téléchargement de la facture')
  } finally {
    downloading.value = false
  }
}

// Contacter le support
const contactSupport = () => {
  router.push('/contact')
}

// Image par défaut selon la catégorie
const getDefaultImage = (article) => {
  return '/placeholder.jpg'
}

// Image par défaut en cas d'erreur
const setDefaultImage = (event) => {
  event.target.src = '/placeholder.jpg'
}

// Obtenir la classe CSS du statut
const getStatutClass = (statut) => {
  const classes = {
    1: 'statut-attente',
    2: 'statut-confirmee',
    3: 'statut-en-cours',
    4: 'statut-terminee',
    5: 'statut-annulee'
  }
  return classes[statut] || 'statut-default'
}

// Obtenir le label du statut (fallback)
const getStatutLabel = (statut) => {
  const labels = {
    1: 'En attente',
    2: 'Confirmée',
    3: 'En cours',
    4: 'Terminée',
    5: 'Annulée'
  }
  return labels[statut] || 'Inconnu'
}

// Formater les jours
const formatJour = (jour) => {
  const jours = {
    'lundi_vendredi': 'Lundi - Vendredi',
    'samedi': 'Samedi',
    'dimanche': 'Dimanche'
  }
  return jours[jour] || jour
}

// Formater les prix
const formatPrice = (price) => {
  if (price === undefined || price === null) return '0,00 €'
  return new Intl.NumberFormat('fr-FR', {
    style: 'currency',
    currency: 'EUR',
    minimumFractionDigits: 2
  }).format(price)
}

// Initialisation
onMounted(() => {
  loadCommande()
})
</script>

<style scoped>
/* Styles pour le nombre de jours */
.detail-item.highlight {
  background: linear-gradient(135deg, #667eea15 0%, #764ba215 100%);
  padding: 4px 10px;
  border-radius: 8px;
  border: 1px solid #667eea30;
}

.detail-item.highlight .detail-value {
  color: #667eea;
  font-weight: 700;
}

.calcul-detail {
  margin-top: 10px;
  padding: 8px 12px;
  background: #f0f9ff;
  border-radius: 6px;
  border-left: 3px solid #667eea;
}

.calcul-formula {
  font-size: 0.85rem;
  color: #4a5568;
  font-family: 'Courier New', monospace;
}

.info-row-duration {
  background: linear-gradient(135deg, #667eea10 0%, #764ba210 100%);
  padding: 12px;
  border-radius: 10px;
  margin-bottom: 10px;
}

.highlight-days {
  color: #667eea !important;
  font-weight: 700 !important;
  font-size: 1.1rem !important;
}

/* Styles existants */
.client-badge {
  display: inline-flex;
  align-items: center;
  gap: 8px;
  padding: 6px 12px;
  background: #f8f9fa;
  border-radius: 20px;
  font-size: 0.9rem;
  margin: 5px 0;
}

.client-badge.particulier {
  background: #e3f2fd;
  color: #0d47a1;
}

.client-badge.professionnel {
  background: #f3e5f5;
  color: #4a148c;
}

.client-icon {
  font-size: 1.1rem;
}

.client-name {
  font-weight: 600;
}

.spinner-small {
  display: inline-block;
  width: 16px;
  height: 16px;
  border: 2px solid rgba(255,255,255,0.3);
  border-radius: 50%;
  border-top-color: white;
  animation: spin 0.8s linear infinite;
}

.btn-invoice:disabled {
  opacity: 0.6;
  cursor: not-allowed;
}

@keyframes spin {
  to { transform: rotate(360deg); }
}

.commande-detail-container {
  max-width: 1400px;
  margin: 0 auto;
  padding: 20px;
  min-height: 100vh;
}

/* Loading */
.loading-container {
  text-align: center;
  padding: 80px 20px;
}

.loading-spinner {
  position: relative;
  width: 80px;
  height: 80px;
  margin: 0 auto 20px;
}

.spinner-ring {
  position: absolute;
  width: 100%;
  height: 100%;
  border-radius: 50%;
  border: 4px solid transparent;
  border-top-color: #667eea;
  animation: spin 1.5s linear infinite;
}

.spinner-ring:nth-child(2) {
  animation-delay: 0.5s;
  border-top-color: #764ba2;
}

.spinner-ring:nth-child(3) {
  animation-delay: 1s;
  border-top-color: #667eea;
}

.loading-text {
  font-size: 1.1rem;
  color: #718096;
}

/* Erreur */
.error-container {
  text-align: center;
  padding: 80px 20px;
  background: white;
  border-radius: 15px;
  box-shadow: 0 2px 10px rgba(0,0,0,0.05);
}

.error-icon {
  font-size: 4rem;
  margin-bottom: 20px;
}

.error-container h2 {
  font-size: 1.8rem;
  color: #2d3748;
  margin-bottom: 10px;
}

.error-container p {
  color: #718096;
  margin-bottom: 30px;
}

.btn-back {
  display: inline-flex;
  align-items: center;
  gap: 8px;
  padding: 12px 24px;
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  color: white;
  text-decoration: none;
  border-radius: 10px;
  font-weight: 600;
  transition: all 0.3s;
}

.btn-back:hover {
  transform: translateY(-2px);
  box-shadow: 0 8px 25px rgba(102,126,234,0.4);
}

/* Header */
.detail-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 30px;
  background: white;
  padding: 25px;
  border-radius: 15px;
  box-shadow: 0 2px 10px rgba(0,0,0,0.05);
}

.header-left {
  display: flex;
  align-items: center;
  gap: 20px;
}

.btn-back-link {
  display: flex;
  align-items: center;
  gap: 8px;
  color: #667eea;
  text-decoration: none;
  font-weight: 600;
  transition: all 0.3s;
}

.btn-back-link:hover {
  color: #5a67d8;
  transform: translateX(-3px);
}

.back-icon {
  font-size: 1.2rem;
}

.header-info h1 {
  font-size: 1.8rem;
  color: #2d3748;
  margin: 0 0 5px 0;
}

.commande-date {
  color: #718096;
  font-size: 0.95rem;
  margin: 0;
}

/* Status badge */
.status-badge {
  padding: 8px 16px;
  border-radius: 20px;
  font-size: 0.9rem;
  font-weight: 600;
}

.statut-attente {
  background: #fef3c7;
  color: #92400e;
}

.statut-confirmee {
  background: #dbeafe;
  color: #1e40af;
}

.statut-en-cours {
  background: #e0e7ff;
  color: #5b21b6;
}

.statut-terminee {
  background: #d1fae5;
  color: #065f46;
}

.statut-annulee {
  background: #fee2e2;
  color: #991b1b;
}

/* Grid */
.detail-grid {
  display: grid;
  grid-template-columns: 1fr 400px;
  gap: 30px;
}

/* Cards */
.detail-card {
  background: white;
  border-radius: 15px;
  padding: 25px;
  box-shadow: 0 2px 10px rgba(0,0,0,0.05);
  border: 1px solid #e2e8f0;
  margin-bottom: 20px;
}

.card-header {
  display: flex;
  align-items: center;
  gap: 12px;
  margin-bottom: 20px;
  padding-bottom: 15px;
  border-bottom: 1px solid #e2e8f0;
}

.card-icon {
  font-size: 1.5rem;
}

.card-header h2 {
  font-size: 1.2rem;
  color: #2d3748;
  margin: 0;
}

/* Période */
.period-info {
  display: flex;
  align-items: center;
  gap: 20px;
  margin-bottom: 15px;
}

.period-item {
  display: flex;
  flex-direction: column;
  gap: 5px;
}

.period-label {
  font-size: 0.85rem;
  color: #718096;
  font-weight: 500;
}

.period-value {
  font-size: 1.1rem;
  color: #2d3748;
  font-weight: 600;
}

.period-separator {
  font-size: 1.5rem;
  color: #cbd5e0;
}

.period-duration {
  display: flex;
  align-items: center;
  gap: 8px;
  padding: 12px;
  background: linear-gradient(135deg, #667eea10 0%, #764ba210 100%);
  border-radius: 10px;
  color: #4a5568;
  font-weight: 500;
  border: 1px solid #667eea20;
}

.duration-icon {
  font-size: 1.2rem;
}

/* Matériels */
.materiels-list {
  display: flex;
  flex-direction: column;
  gap: 20px;
}

.materiel-item {
  display: flex;
  gap: 20px;
  padding: 15px;
  background: #f7fafc;
  border-radius: 12px;
  border: 1px solid #e2e8f0;
}

.materiel-image {
  width: 100px;
  height: 100px;
  border-radius: 10px;
  overflow: hidden;
  background: white;
  flex-shrink: 0;
}

.materiel-image img {
  width: 100%;
  height: 100%;
  object-fit: cover;
}

.materiel-info {
  flex: 1;
}

.materiel-name {
  font-size: 1.1rem;
  color: #2d3748;
  margin: 0 0 10px 0;
  font-weight: 600;
}

.materiel-details {
  display: flex;
  flex-wrap: wrap;
  gap: 15px;
}

.detail-item {
  display: flex;
  gap: 5px;
  font-size: 0.9rem;
}

.detail-label {
  color: #718096;
}

.detail-value {
  color: #2d3748;
  font-weight: 500;
}

.materiel-pricing {
  display: flex;
  flex-direction: column;
  gap: 5px;
  min-width: 180px;
  text-align: right;
}

.pricing-row {
  display: flex;
  justify-content: space-between;
  font-size: 0.9rem;
}

.pricing-row.total {
  margin-top: 5px;
  padding-top: 5px;
  border-top: 1px solid #cbd5e0;
  font-weight: 600;
  color: #10b981;
}

.pricing-label {
  color: #718096;
}

.pricing-value {
  color: #2d3748;
  font-weight: 500;
}

/* Livraison */
.delivery-grid {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 20px;
}

.delivery-section {
  padding: 15px;
  background: #f7fafc;
  border-radius: 10px;
}

.delivery-title {
  display: flex;
  align-items: center;
  gap: 8px;
  font-size: 1rem;
  color: #2d3748;
  margin: 0 0 15px 0;
}

.delivery-icon {
  font-size: 1.2rem;
}

.info-item {
  display: flex;
  justify-content: space-between;
  padding: 8px 0;
  border-bottom: 1px solid #e2e8f0;
}

.info-item:last-child {
  border-bottom: none;
}

.info-label {
  color: #718096;
  font-size: 0.9rem;
}

.info-value {
  color: #2d3748;
  font-weight: 500;
  font-size: 0.9rem;
}

.info-value.price {
  color: #10b981;
  font-weight: 600;
}

.info-details {
  margin-top: 10px;
}

.info-free {
  display: flex;
  align-items: center;
  gap: 8px;
  padding: 10px;
  background: #d1fae5;
  border-radius: 8px;
  color: #065f46;
  font-weight: 500;
}

.free-icon {
  font-size: 1.2rem;
}

/* Notes */
.notes-content {
  padding: 15px;
  background: #f7fafc;
  border-radius: 10px;
  color: #4a5568;
  line-height: 1.6;
  margin: 0;
}

/* Sidebar */
.detail-sidebar {
  display: flex;
  flex-direction: column;
  gap: 20px;
}

.summary-card {
  background: white;
  border-radius: 15px;
  padding: 25px;
  box-shadow: 0 4px 20px rgba(0,0,0,0.08);
  border: 1px solid #e2e8f0;
}

.summary-card.sticky {
  position: sticky;
  top: 20px;
}

.summary-header {
  display: flex;
  align-items: center;
  gap: 12px;
  margin-bottom: 20px;
  padding-bottom: 15px;
  border-bottom: 1px solid #e2e8f0;
}

.summary-icon {
  font-size: 1.5rem;
}

.summary-header h2 {
  font-size: 1.1rem;
  color: #2d3748;
  margin: 0;
}

.summary-row {
  display: flex;
  justify-content: space-between;
  padding: 10px 0;
  border-bottom: 1px solid #f1f5f9;
}

.summary-row:last-child {
  border-bottom: none;
}

.summary-label {
  color: #4a5568;
  font-size: 0.95rem;
}

.summary-value {
  color: #2d3748;
  font-weight: 600;
  font-size: 0.95rem;
}

.summary-row.discount {
  color: #10b981;
}

.discount-icon {
  margin-right: 5px;
}

.summary-divider {
  height: 1px;
  background: #e2e8f0;
  margin: 15px 0;
}

.summary-total {
  display: flex;
  justify-content: space-between;
  padding: 15px 0;
  margin-top: 15px;
  border-top: 2px solid #e2e8f0;
}

.total-label {
  font-size: 1.2rem;
  font-weight: 700;
  color: #2d3748;
}

.total-value {
  font-size: 1.6rem;
  font-weight: 800;
  color: #10b981;
}

/* Actions */
.summary-actions {
  display: flex;
  flex-direction: column;
  gap: 10px;
  margin-top: 20px;
  padding-top: 20px;
  border-top: 1px solid #e2e8f0;
}

.btn-cancel,
.btn-invoice,
.btn-support {
  width: 100%;
  padding: 12px;
  border: none;
  border-radius: 10px;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.3s;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 8px;
}

.btn-cancel {
  background: #fee2e2;
  color: #991b1b;
}

.btn-cancel:hover:not(:disabled) {
  background: #fecaca;
}

.btn-cancel:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}

.btn-invoice {
  background: #f7fafc;
  color: #4a5568;
  border: 2px solid #e2e8f0;
}

.btn-invoice:hover {
  background: #edf2f7;
  border-color: #cbd5e0;
}

.btn-support {
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  color: white;
}

.btn-support:hover {
  transform: translateY(-2px);
  box-shadow: 0 4px 15px rgba(102,126,234,0.3);
}

/* Info Card */
.info-card,
.help-card {
  background: white;
  border-radius: 15px;
  padding: 20px;
  box-shadow: 0 2px 10px rgba(0,0,0,0.05);
  border: 1px solid #e2e8f0;
}

.info-card-header {
  display: flex;
  align-items: center;
  gap: 10px;
  margin-bottom: 15px;
}

.info-icon {
  font-size: 1.2rem;
}

.info-card-header h3 {
  font-size: 1rem;
  color: #2d3748;
  margin: 0;
}

.info-row {
  display: flex;
  justify-content: space-between;
  padding: 8px 0;
  border-bottom: 1px solid #f1f5f9;
}

.info-row:last-child {
  border-bottom: none;
}

.mono {
  font-family: 'Courier New', monospace;
  background: #f7fafc;
  padding: 2px 8px;
  border-radius: 4px;
}

.status-badge-inline {
  padding: 4px 10px;
  border-radius: 12px;
  font-size: 0.8rem;
  font-weight: 600;
}

/* Help Card */
.help-card {
  text-align: center;
  background: linear-gradient(135deg, #f7fafc 0%, #edf2f7 100%);
}

.help-icon {
  font-size: 2.5rem;
  margin-bottom: 10px;
}

.help-card h3 {
  font-size: 1.1rem;
  color: #2d3748;
  margin: 0 0 10px 0;
}

.help-card p {
  color: #718096;
  font-size: 0.9rem;
  line-height: 1.6;
  margin: 0 0 15px 0;
}

.btn-help {
  width: 100%;
  padding: 12px;
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  color: white;
  border: none;
  border-radius: 10px;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.3s;
}

.btn-help:hover {
  transform: translateY(-2px);
  box-shadow: 0 4px 15px rgba(102,126,234,0.3);
}

/* Responsive */
@media (max-width: 1200px) {
  .detail-grid {
    grid-template-columns: 1fr;
  }
  
  .summary-card.sticky {
    position: static;
  }
}

@media (max-width: 768px) {
  .detail-header {
    flex-direction: column;
    align-items: flex-start;
    gap: 15px;
  }
  
  .delivery-grid {
    grid-template-columns: 1fr;
  }
  
  .materiel-item {
    flex-direction: column;
  }
  
  .materiel-pricing {
    text-align: left;
    min-width: auto;
  }
}
</style>
