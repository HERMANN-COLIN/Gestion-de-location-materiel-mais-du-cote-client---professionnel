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
      <!-- Header -->
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
            <p class="commande-date">Passée le {{ commande.date_commande }}</p>
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
                <span class="duration-text">
                  Durée : <strong>{{ commande.duree }}</strong>
                  ({{ commande.nombre_jours }} jour{{ commande.nombre_jours > 1 ? 's' : '' }} de location)
                </span>
              </div>
            </div>
          </div>

          <!-- Matériels loués -->
          <div class="detail-card">
            <div class="card-header">
              <span class="card-icon">📦</span>
              <h2>Matériels loués</h2>
            </div>
            <div class="card-content">
              <div class="materiels-list">
                <div v-for="article in commande.articles" :key="article.id" class="materiel-item">
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
                    <div class="calcul-detail">
                      <span class="calcul-formula">
                        {{ formatPrice(article.prix_unitaire_ht) }} ×
                        {{ article.quantite }} unité{{ article.quantite > 1 ? 's' : '' }} ×
                        {{ article.nombre_jours || commande.nombre_jours }}
                        jour{{ (article.nombre_jours || commande.nombre_jours) > 1 ? 's' : '' }}
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
                <div class="delivery-section">
                  <h3 class="delivery-title">
                    <span class="delivery-icon">📦</span>
                    Livraison
                  </h3>
                  <div class="delivery-info">
                    <div class="info-item">
                      <span class="info-label">Mode :</span>
                      <span class="info-value">{{ commande.mode_livraison_libelle }}</span>
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

                <div class="delivery-section">
                  <h3 class="delivery-title">
                    <span class="delivery-icon">🔄</span>
                    Retour
                  </h3>
                  <div class="delivery-info">
                    <div class="info-item">
                      <span class="info-label">Mode :</span>
                      <span class="info-value">{{ commande.mode_retour_libelle }}</span>
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
                <span class="summary-value highlight-days">
                  {{ commande.nombre_jours }} jour{{ commande.nombre_jours > 1 ? 's' : '' }}
                </span>
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
            <div class="summary-actions">
              <button v-if="canCancelOrder()" @click="cancelOrder" :disabled="cancelling" class="btn-cancel">
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

          <!-- ===================== SECTION AVIS ===================== -->
          <div class="avis-card">
            <div class="avis-header">
              <span class="avis-icon">⭐</span>
              <h3>Donnez votre avis</h3>
            </div>

            <!-- Commande terminée -->
            <div v-if="commande.statut === 4" class="avis-content">

              <!-- Avis existant affiché -->
              <div v-if="avis && !showAvisForm" class="avis-existing">
                <div class="avis-rating-display">
                  <span class="rating-stars">
                    <span v-for="n in 5" :key="n" class="star" :class="{ filled: n <= avis.note }">★</span>
                  </span>
                  <span class="rating-note">{{ avis.note }}/5</span>
                </div>
                <!-- ✅ CORRECTION : un seul attribut class par élément -->
                <p v-if="avis.commentaire" class="avis-commentaire">{{ avis.commentaire }}</p>
                <p v-else class="avis-commentaire no-comment">Aucun commentaire</p>
                <div class="avis-actions">
                  <button @click="editAvis" class="btn-edit-avis">
                    <span class="btn-icon">✏️</span>
                    Modifier mon avis
                  </button>
                  <button @click="deleteAvis" class="btn-delete-avis" :disabled="deletingAvis">
                    <span v-if="!deletingAvis" class="btn-icon">🗑️</span>
                    <span v-else class="spinner-small"></span>
                    {{ deletingAvis ? 'Suppression...' : 'Supprimer' }}
                  </button>
                </div>
              </div>

              <!-- Formulaire avis (nouveau ou modification) -->
              <div v-else-if="showAvisForm" class="avis-form">
                <div class="rating-input">
                  <label>Note :</label>
                  <div class="star-rating">
                    <span
                      v-for="n in 5"
                      :key="n"
                      class="star-selectable"
                      :class="{ selected: avisForm.note >= n }"
                      @click="avisForm.note = n"
                    >★</span>
                  </div>
                </div>
                <div class="commentaire-input">
                  <label>Votre commentaire (optionnel) :</label>
                  <textarea
                    v-model="avisForm.commentaire"
                    rows="3"
                    placeholder="Partagez votre expérience avec ce matériel..."
                    maxlength="1000"
                  ></textarea>
                  <small>{{ avisForm.commentaire.length }}/1000 caractères</small>
                </div>
                <div class="form-actions-avis">
                  <button @click="cancelAvisForm" class="btn-cancel-avis">Annuler</button>
                  <button @click="submitAvis" class="btn-submit-avis" :disabled="submittingAvis">
                    <span v-if="!submittingAvis">{{ avis ? 'Mettre à jour' : 'Envoyer mon avis' }}</span>
                    <span v-else class="spinner-small"></span>
                  </button>
                </div>
              </div>

              <!-- Bouton pour ouvrir le formulaire (pas encore d'avis) -->
              <div v-else class="avis-prompt">
                <p>Cette commande est terminée. Partagez votre expérience !</p>
                <button @click="showAvisForm = true" class="btn-write-avis">
                  <span class="btn-icon">✍️</span>
                  Écrire un avis
                </button>
              </div>
            </div>

            <!-- Commande pas encore terminée -->
            <div v-else class="avis-disabled">
              <span class="disabled-icon">🔒</span>
              <p>Vous pourrez laisser un avis une fois la commande terminée.</p>
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
            <button @click="contactSupport" class="btn-help">Contactez-nous</button>
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

const commande     = ref(null)
const loading      = ref(true)
const error        = ref(null)
const cancelling   = ref(false)
const downloading  = ref(false)

// ── Avis ──────────────────────────────────────────────────────────────────────
const avis          = ref(null)
const showAvisForm  = ref(false)
const submittingAvis= ref(false)
const deletingAvis  = ref(false)
const avisForm      = ref({ note: 5, commentaire: '' })

// ── Chargement commande ───────────────────────────────────────────────────────
const loadCommande = async () => {
  loading.value = true
  error.value   = null
  try {
    const commandeId = route.params.id
    const response   = await api.get(`/commandes/${commandeId}`)
    if (response.data.success) {
      commande.value = response.data.data
      if (commande.value.statut === 4) await loadAvis()
    } else {
      error.value = response.data.message || 'Commande introuvable'
    }
  } catch (err) {
    error.value = err.response?.data?.message || 'Erreur lors du chargement de la commande'
  } finally {
    loading.value = false
  }
}

// ── Avis ──────────────────────────────────────────────────────────────────────
const loadAvis = async () => {
  try {
    const res = await api.get(`/commandes/${route.params.id}/avis`)
    if (res.data.success) {
      avis.value = res.data.data
      avisForm.value = { note: avis.value.note, commentaire: avis.value.commentaire || '' }
    }
  } catch (err) {
    if (err.response?.status !== 404) console.error('Erreur chargement avis:', err)
    avis.value = null
  }
}

const editAvis = () => {
  showAvisForm.value = true
}

const cancelAvisForm = () => {
  showAvisForm.value = false
  if (avis.value) {
    avisForm.value = { note: avis.value.note, commentaire: avis.value.commentaire || '' }
  } else {
    avisForm.value = { note: 5, commentaire: '' }
  }
}

const submitAvis = async () => {
  if (!avisForm.value.note || avisForm.value.note < 1 || avisForm.value.note > 5) {
    alert('Veuillez sélectionner une note entre 1 et 5')
    return
  }
  submittingAvis.value = true
  try {
    const res = await api.post(`/commandes/${route.params.id}/avis`, {
      note:        avisForm.value.note,
      commentaire: avisForm.value.commentaire || null,
    })
    if (res.data.success) {
      avis.value         = res.data.data
      showAvisForm.value = false
    }
  } catch (err) {
    alert(err.response?.data?.message || "Erreur lors de l'enregistrement de l'avis")
  } finally {
    submittingAvis.value = false
  }
}

const deleteAvis = async () => {
  if (!confirm('Êtes-vous sûr de vouloir supprimer votre avis ?')) return
  deletingAvis.value = true
  try {
    const res = await api.delete(`/commandes/${route.params.id}/avis`)
    if (res.data.success) {
      avis.value     = null
      avisForm.value = { note: 5, commentaire: '' }
    }
  } catch (err) {
    alert(err.response?.data?.message || "Erreur lors de la suppression de l'avis")
  } finally {
    deletingAvis.value = false
  }
}

// ── Commande ──────────────────────────────────────────────────────────────────
const canCancelOrder = () => commande.value ? [1, 2].includes(commande.value.statut) : false

const cancelOrder = async () => {
  if (!confirm('Êtes-vous sûr de vouloir annuler cette commande ?')) return
  cancelling.value = true
  try {
    const res = await api.put(`/commandes/${commande.value.id}/annuler`)
    if (res.data.success) { alert('Commande annulée avec succès'); await loadCommande() }
  } catch (err) {
    alert(err.response?.data?.message || "Erreur lors de l'annulation")
  } finally {
    cancelling.value = false
  }
}

const downloadInvoice = async () => {
  downloading.value = true
  try {
    const res = await api.get(`/commandes/${commande.value.id}/facture`, { responseType: 'blob' })
    const url  = window.URL.createObjectURL(new Blob([res.data], { type: 'application/pdf' }))
    const link = document.createElement('a')
    link.href  = url
    link.setAttribute('download', `facture_${commande.value.numero_commande}.pdf`)
    document.body.appendChild(link)
    link.click()
    link.remove()
    window.URL.revokeObjectURL(url)
  } catch (err) {
    alert(err.response?.data?.message || 'Erreur lors du téléchargement de la facture')
  } finally {
    downloading.value = false
  }
}

const contactSupport = () => router.push('/contact')

const getDefaultImage  = () => '/placeholder.jpg'
const setDefaultImage  = (e) => { e.target.src = '/placeholder.jpg' }

const getStatutClass = (statut) => ({
  1: 'statut-attente', 2: 'statut-confirmee', 3: 'statut-en-cours',
  4: 'statut-terminee', 5: 'statut-annulee'
})[statut] || 'statut-default'

const getStatutLabel = (statut) => ({
  1: 'En attente', 2: 'Confirmée', 3: 'En cours', 4: 'Terminée', 5: 'Annulée'
})[statut] || 'Inconnu'

const formatJour = (jour) => ({
  'lundi_vendredi': 'Lundi - Vendredi', 'samedi': 'Samedi', 'dimanche': 'Dimanche'
})[jour] || jour

const formatPrice = (price) => {
  if (price === undefined || price === null) return '0,00 €'
  return new Intl.NumberFormat('fr-FR', { style: 'currency', currency: 'EUR', minimumFractionDigits: 2 }).format(price)
}

onMounted(() => loadCommande())
</script>

<style scoped>
.detail-item.highlight {
  background: linear-gradient(135deg, #667eea15 0%, #764ba215 100%);
  padding: 4px 10px;
  border-radius: 8px;
  border: 1px solid #667eea30;
}
.detail-item.highlight .detail-value { color: #667eea; font-weight: 700; }
.calcul-detail { margin-top: 10px; padding: 8px 12px; background: #f0f9ff; border-radius: 6px; border-left: 3px solid #667eea; }
.calcul-formula { font-size: 0.85rem; color: #4a5568; font-family: 'Courier New', monospace; }
.info-row-duration { background: linear-gradient(135deg, #667eea10 0%, #764ba210 100%); padding: 12px; border-radius: 10px; margin-bottom: 10px; }
.highlight-days { color: #667eea !important; font-weight: 700 !important; font-size: 1.1rem !important; }

.client-badge { display: inline-flex; align-items: center; gap: 8px; padding: 6px 12px; background: #f8f9fa; border-radius: 20px; font-size: 0.9rem; margin: 5px 0; }
.client-badge.particulier  { background: #e3f2fd; color: #0d47a1; }
.client-badge.professionnel{ background: #f3e5f5; color: #4a148c; }
.client-icon { font-size: 1.1rem; }
.client-name { font-weight: 600; }

.spinner-small { display: inline-block; width: 16px; height: 16px; border: 2px solid rgba(255,255,255,0.3); border-radius: 50%; border-top-color: white; animation: spin 0.8s linear infinite; }
.btn-invoice:disabled { opacity: 0.6; cursor: not-allowed; }

@keyframes spin { to { transform: rotate(360deg); } }

.commande-detail-container { max-width: 1400px; margin: 0 auto; padding: 20px; min-height: 100vh; }

.loading-container { text-align: center; padding: 80px 20px; }
.loading-spinner { position: relative; width: 80px; height: 80px; margin: 0 auto 20px; }
.spinner-ring { position: absolute; width: 100%; height: 100%; border-radius: 50%; border: 4px solid transparent; border-top-color: #667eea; animation: spin 1.5s linear infinite; }
.spinner-ring:nth-child(2) { animation-delay: 0.5s; border-top-color: #764ba2; }
.spinner-ring:nth-child(3) { animation-delay: 1s; border-top-color: #667eea; }
.loading-text { font-size: 1.1rem; color: #718096; }

.error-container { text-align: center; padding: 80px 20px; background: white; border-radius: 15px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); }
.error-icon { font-size: 4rem; margin-bottom: 20px; }
.error-container h2 { font-size: 1.8rem; color: #2d3748; margin-bottom: 10px; }
.error-container p  { color: #718096; margin-bottom: 30px; }
.btn-back { display: inline-flex; align-items: center; gap: 8px; padding: 12px 24px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; text-decoration: none; border-radius: 10px; font-weight: 600; transition: all 0.3s; }
.btn-back:hover { transform: translateY(-2px); box-shadow: 0 8px 25px rgba(102,126,234,0.4); }

.detail-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px; background: white; padding: 25px; border-radius: 15px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); }
.header-left { display: flex; align-items: center; gap: 20px; }
.btn-back-link { display: flex; align-items: center; gap: 8px; color: #667eea; text-decoration: none; font-weight: 600; transition: all 0.3s; }
.btn-back-link:hover { color: #5a67d8; transform: translateX(-3px); }
.back-icon { font-size: 1.2rem; }
.header-info h1 { font-size: 1.8rem; color: #2d3748; margin: 0 0 5px 0; }
.commande-date { color: #718096; font-size: 0.95rem; margin: 0; }

.status-badge { padding: 8px 16px; border-radius: 20px; font-size: 0.9rem; font-weight: 600; }
.statut-attente   { background: #fef3c7; color: #92400e; }
.statut-confirmee { background: #dbeafe; color: #1e40af; }
.statut-en-cours  { background: #e0e7ff; color: #5b21b6; }
.statut-terminee  { background: #d1fae5; color: #065f46; }
.statut-annulee   { background: #fee2e2; color: #991b1b; }

.detail-grid { display: grid; grid-template-columns: 1fr 400px; gap: 30px; }

.detail-card { background: white; border-radius: 15px; padding: 25px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); border: 1px solid #e2e8f0; margin-bottom: 20px; }
.card-header { display: flex; align-items: center; gap: 12px; margin-bottom: 20px; padding-bottom: 15px; border-bottom: 1px solid #e2e8f0; }
.card-icon { font-size: 1.5rem; }
.card-header h2 { font-size: 1.2rem; color: #2d3748; margin: 0; }

.period-info { display: flex; align-items: center; gap: 20px; margin-bottom: 15px; }
.period-item { display: flex; flex-direction: column; gap: 5px; }
.period-label { font-size: 0.85rem; color: #718096; font-weight: 500; }
.period-value { font-size: 1.1rem; color: #2d3748; font-weight: 600; }
.period-separator { font-size: 1.5rem; color: #cbd5e0; }
.period-duration { display: flex; align-items: center; gap: 8px; padding: 12px; background: linear-gradient(135deg, #667eea10 0%, #764ba210 100%); border-radius: 10px; color: #4a5568; font-weight: 500; border: 1px solid #667eea20; }
.duration-icon { font-size: 1.2rem; }

.materiels-list { display: flex; flex-direction: column; gap: 20px; }
.materiel-item { display: flex; gap: 20px; padding: 15px; background: #f7fafc; border-radius: 12px; border: 1px solid #e2e8f0; }
.materiel-image { width: 100px; height: 100px; border-radius: 10px; overflow: hidden; background: white; flex-shrink: 0; }
.materiel-image img { width: 100%; height: 100%; object-fit: cover; }
.materiel-info { flex: 1; }
.materiel-name { font-size: 1.1rem; color: #2d3748; margin: 0 0 10px 0; font-weight: 600; }
.materiel-details { display: flex; flex-wrap: wrap; gap: 15px; }
.detail-item { display: flex; gap: 5px; font-size: 0.9rem; }
.detail-label { color: #718096; }
.detail-value { color: #2d3748; font-weight: 500; }
.materiel-pricing { display: flex; flex-direction: column; gap: 5px; min-width: 180px; text-align: right; }
.pricing-row { display: flex; justify-content: space-between; font-size: 0.9rem; }
.pricing-row.total { margin-top: 5px; padding-top: 5px; border-top: 1px solid #cbd5e0; font-weight: 600; color: #10b981; }
.pricing-label { color: #718096; }
.pricing-value { color: #2d3748; font-weight: 500; }

.delivery-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; }
.delivery-section { padding: 15px; background: #f7fafc; border-radius: 10px; }
.delivery-title { display: flex; align-items: center; gap: 8px; font-size: 1rem; color: #2d3748; margin: 0 0 15px 0; }
.delivery-icon { font-size: 1.2rem; }
.info-item { display: flex; justify-content: space-between; padding: 8px 0; border-bottom: 1px solid #e2e8f0; }
.info-item:last-child { border-bottom: none; }
.info-label { color: #718096; font-size: 0.9rem; }
.info-value { color: #2d3748; font-weight: 500; font-size: 0.9rem; }
.info-value.price { color: #10b981; font-weight: 600; }
.info-details { margin-top: 10px; }
.info-free { display: flex; align-items: center; gap: 8px; padding: 10px; background: #d1fae5; border-radius: 8px; color: #065f46; font-weight: 500; }
.free-icon { font-size: 1.2rem; }

.notes-content { padding: 15px; background: #f7fafc; border-radius: 10px; color: #4a5568; line-height: 1.6; margin: 0; }

.detail-sidebar { display: flex; flex-direction: column; gap: 20px; }
.summary-card { background: white; border-radius: 15px; padding: 25px; box-shadow: 0 4px 20px rgba(0,0,0,0.08); border: 1px solid #e2e8f0; }
.summary-card.sticky { position: sticky; top: 20px; }
.summary-header { display: flex; align-items: center; gap: 12px; margin-bottom: 20px; padding-bottom: 15px; border-bottom: 1px solid #e2e8f0; }
.summary-icon { font-size: 1.5rem; }
.summary-header h2 { font-size: 1.1rem; color: #2d3748; margin: 0; }
.summary-row { display: flex; justify-content: space-between; padding: 10px 0; border-bottom: 1px solid #f1f5f9; }
.summary-row:last-child { border-bottom: none; }
.summary-label { color: #4a5568; font-size: 0.95rem; }
.summary-value { color: #2d3748; font-weight: 600; font-size: 0.95rem; }
.summary-row.discount { color: #10b981; }
.discount-icon { margin-right: 5px; }
.summary-divider { height: 1px; background: #e2e8f0; margin: 15px 0; }
.summary-total { display: flex; justify-content: space-between; padding: 15px 0; margin-top: 15px; border-top: 2px solid #e2e8f0; }
.total-label { font-size: 1.2rem; font-weight: 700; color: #2d3748; }
.total-value { font-size: 1.6rem; font-weight: 800; color: #10b981; }

.summary-actions { display: flex; flex-direction: column; gap: 10px; margin-top: 20px; padding-top: 20px; border-top: 1px solid #e2e8f0; }
.btn-cancel, .btn-invoice, .btn-support { width: 100%; padding: 12px; border: none; border-radius: 10px; font-weight: 600; cursor: pointer; transition: all 0.3s; display: flex; align-items: center; justify-content: center; gap: 8px; }
.btn-cancel { background: #fee2e2; color: #991b1b; }
.btn-cancel:hover:not(:disabled) { background: #fecaca; }
.btn-cancel:disabled { opacity: 0.5; cursor: not-allowed; }
.btn-invoice { background: #f7fafc; color: #4a5568; border: 2px solid #e2e8f0; }
.btn-invoice:hover { background: #edf2f7; border-color: #cbd5e0; }
.btn-support { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; }
.btn-support:hover { transform: translateY(-2px); box-shadow: 0 4px 15px rgba(102,126,234,0.3); }

/* ── AVIS ───────────────────────────────────────────────────────────── */
.avis-card { background: white; border-radius: 15px; padding: 20px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); border: 1px solid #e2e8f0; }
.avis-header { display: flex; align-items: center; gap: 10px; margin-bottom: 20px; padding-bottom: 15px; border-bottom: 2px solid #fbbf24; }
.avis-icon { font-size: 1.5rem; }
.avis-header h3 { font-size: 1.1rem; color: #2d3748; margin: 0; }
.avis-content { min-height: 150px; }

.avis-existing { text-align: center; }
.avis-rating-display { margin-bottom: 15px; }
.rating-stars { display: inline-flex; gap: 5px; margin-right: 10px; }
.star { font-size: 1.5rem; color: #cbd5e0; }
.star.filled { color: #fbbf24; }
.rating-note { font-weight: 600; color: #4a5568; }
.avis-commentaire { padding: 15px; background: #f7fafc; border-radius: 10px; color: #4a5568; line-height: 1.6; margin: 15px 0; font-style: italic; }
.no-comment { color: #a0aec0; }
.avis-actions { display: flex; gap: 10px; justify-content: center; }
.btn-edit-avis, .btn-delete-avis { padding: 8px 16px; border: none; border-radius: 8px; font-weight: 500; cursor: pointer; transition: all 0.3s; display: inline-flex; align-items: center; gap: 5px; font-size: 0.85rem; }
.btn-edit-avis { background: #e0e7ff; color: #3730a3; }
.btn-edit-avis:hover { background: #c7d2fe; transform: translateY(-2px); }
.btn-delete-avis { background: #fee2e2; color: #991b1b; }
.btn-delete-avis:hover:not(:disabled) { background: #fecaca; transform: translateY(-2px); }
.btn-delete-avis:disabled { opacity: 0.5; cursor: not-allowed; }

.avis-form { padding: 10px 0; }
.rating-input { margin-bottom: 20px; }
.rating-input label { display: block; font-weight: 600; color: #2d3748; margin-bottom: 10px; }
.star-rating { display: flex; gap: 8px; }
.star-selectable { font-size: 2rem; color: #cbd5e0; cursor: pointer; transition: all 0.2s; }
.star-selectable:hover, .star-selectable.selected { color: #fbbf24; transform: scale(1.1); }
.commentaire-input { margin-bottom: 20px; }
.commentaire-input label { display: block; font-weight: 600; color: #2d3748; margin-bottom: 10px; }
.commentaire-input textarea { width: 100%; padding: 12px; border: 2px solid #e2e8f0; border-radius: 10px; font-family: inherit; font-size: 0.95rem; resize: vertical; transition: all 0.3s; box-sizing: border-box; }
.commentaire-input textarea:focus { outline: none; border-color: #667eea; box-shadow: 0 0 0 3px rgba(102,126,234,0.1); }
.commentaire-input small { display: block; margin-top: 5px; color: #718096; font-size: 0.8rem; text-align: right; }
.form-actions-avis { display: flex; gap: 10px; justify-content: flex-end; }
.btn-cancel-avis, .btn-submit-avis { padding: 10px 20px; border: none; border-radius: 8px; font-weight: 600; cursor: pointer; transition: all 0.3s; }
.btn-cancel-avis { background: #f3f4f6; color: #374151; }
.btn-cancel-avis:hover { background: #e5e7eb; }
.btn-submit-avis { background: linear-gradient(135deg, #10b981, #34d399); color: white; }
.btn-submit-avis:hover:not(:disabled) { transform: translateY(-2px); box-shadow: 0 4px 12px rgba(16,185,129,0.3); }
.btn-submit-avis:disabled { opacity: 0.6; cursor: not-allowed; }

.avis-prompt { text-align: center; padding: 20px; }
.avis-prompt p { color: #718096; margin-bottom: 15px; }
.btn-write-avis { padding: 10px 20px; background: linear-gradient(135deg, #fbbf24, #f59e0b); color: white; border: none; border-radius: 8px; font-weight: 600; cursor: pointer; transition: all 0.3s; display: inline-flex; align-items: center; gap: 5px; }
.btn-write-avis:hover { transform: translateY(-2px); box-shadow: 0 4px 12px rgba(251,191,36,0.3); }

.avis-disabled { text-align: center; padding: 30px 20px; background: #f7fafc; border-radius: 10px; }
.disabled-icon { font-size: 2rem; display: block; margin-bottom: 10px; opacity: 0.5; }
.avis-disabled p { color: #a0aec0; margin: 0; }

/* ── Info & Help cards ──────────────────────────────────────────────── */
.info-card, .help-card { background: white; border-radius: 15px; padding: 20px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); border: 1px solid #e2e8f0; }
.info-card-header { display: flex; align-items: center; gap: 10px; margin-bottom: 15px; }
.info-icon { font-size: 1.2rem; }
.info-card-header h3 { font-size: 1rem; color: #2d3748; margin: 0; }
.info-row { display: flex; justify-content: space-between; padding: 8px 0; border-bottom: 1px solid #f1f5f9; }
.info-row:last-child { border-bottom: none; }
.mono { font-family: 'Courier New', monospace; background: #f7fafc; padding: 2px 8px; border-radius: 4px; }
.status-badge-inline { padding: 4px 10px; border-radius: 12px; font-size: 0.8rem; font-weight: 600; }
.help-card { text-align: center; background: linear-gradient(135deg, #f7fafc 0%, #edf2f7 100%); }
.help-icon { font-size: 2.5rem; margin-bottom: 10px; }
.help-card h3 { font-size: 1.1rem; color: #2d3748; margin: 0 0 10px 0; }
.help-card p  { color: #718096; font-size: 0.9rem; line-height: 1.6; margin: 0 0 15px 0; }
.btn-help { width: 100%; padding: 12px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; border: none; border-radius: 10px; font-weight: 600; cursor: pointer; transition: all 0.3s; }
.btn-help:hover { transform: translateY(-2px); box-shadow: 0 4px 15px rgba(102,126,234,0.3); }

/* ── Responsive ─────────────────────────────────────────────────────── */
@media (max-width: 1200px) {
  .detail-grid { grid-template-columns: 1fr; }
  .summary-card.sticky { position: static; }
}
@media (max-width: 768px) {
  .detail-header { flex-direction: column; align-items: flex-start; gap: 15px; }
  .delivery-grid { grid-template-columns: 1fr; }
  .materiel-item { flex-direction: column; }
  .materiel-pricing { text-align: left; min-width: auto; }
  .avis-actions { flex-direction: column; }
  .form-actions-avis { flex-direction: column; }
  .btn-cancel-avis, .btn-submit-avis { width: 100%; }
}
</style>