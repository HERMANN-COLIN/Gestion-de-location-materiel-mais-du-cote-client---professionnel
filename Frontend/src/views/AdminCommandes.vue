<template>
  <div class="admin-commandes">
    <!-- Barre de navigation Admin -->
    <div class="admin-nav-tabs">
      <router-link to="/admin/produits" class="admin-tab">
        📦 Produits & Stocks
      </router-link>
      <router-link to="/admin/commandes" class="admin-tab active">
        🛒 Commandes
      </router-link>
      <router-link to="/admin/statistiques" class="admin-tab">
        📊 Statistiques
      </router-link>
      <router-link to="/" class="admin-tab back-to-site">
        🏠 Retour au site
      </router-link>
    </div>

    <div class="page-header">
      <div>
        <h1>Gestion des commandes</h1>
        <p>Suivez et gérez toutes les commandes clients</p>
      </div>
    </div>

    <!-- Filtres par statut -->
    <div class="status-tabs">
      <button
        v-for="tab in statutTabs"
        :key="tab.value"
        @click="changeFilter(tab.value)"
        class="status-tab"
        :class="{ active: filterStatut === tab.value }"
      >
        {{ tab.label }}
        <span class="tab-count">{{ counts[tab.value || 'all'] || 0 }}</span>
      </button>
    </div>

    <!-- Recherche -->
    <div class="search-bar">
      <input
        v-model="searchQuery"
        type="text"
        placeholder="Rechercher par numéro ou email client..."
        @input="debouncedSearch"
      >
    </div>

    <!-- Liste des commandes -->
    <div class="commandes-list">
      <div v-if="loading && commandes.length === 0" class="loading-state">
        <p>Chargement des commandes...</p>
      </div>

      <div v-else-if="commandes.length === 0" class="empty-state">
        <span class="empty-icon">📭</span>
        <p>Aucune commande trouvée</p>
      </div>

      <div
        v-for="commande in commandes"
        :key="commande.id"
        class="commande-card"
        :class="{ 'is-updating': updatingId === commande.id }"
      >
        <div class="commande-header">
          <div class="commande-info">
            <span class="commande-number">{{ commande.numero_commande }}</span>
            <span class="commande-date">
              {{ formatDate(commande.date_commande || commande.created_at) }}
            </span>
          </div>
          <span
            class="commande-status"
            :class="getStatusClass(commande.statut_id)"
          >
            {{ commande.statut_libelle || getStatusLabel(commande.statut_id) }}
          </span>
        </div>

        <div class="commande-body">
          <div class="commande-client">
            <div class="client-avatar">👤</div>
            <div>
              <div class="client-name">{{ commande.user?.email || 'Client' }}</div>
              <div class="client-type">
                {{ commande.user?.type?.type || 'Particulier' }}
              </div>
            </div>
          </div>

          <div class="commande-details">
            <div class="detail-item">
              <span class="detail-label">Période</span>
              <span class="detail-value">
                {{ formatDate(commande.date_debut) }} → {{ formatDate(commande.date_fin) }}
              </span>
            </div>
            <div class="detail-item">
              <span class="detail-label">Montant</span>
              <span class="detail-value price">
                {{ formatPrice(commande.montant_total) }}
              </span>
            </div>
            <div class="detail-item">
              <span class="detail-label">Articles</span>
              <span class="detail-value">
                {{ commande.details?.length || 0 }} article(s)
              </span>
            </div>
          </div>
        </div>

        <div class="commande-actions">
          <select
            :value="commande.statut_id"
            @change="updateStatut(commande, Number($event.target.value))"
            :disabled="updatingId === commande.id"
            class="statut-select"
          >
            <option :value="1">En attente</option>
            <option :value="2">Confirmée</option>
            <option :value="3">Livrée</option>
            <option :value="4">Annulée</option>
          </select>
          <button
            @click="showDetails(commande)"
            :disabled="loadingDetails"
            class="btn-secondary"
          >
            👁️ Détails
          </button>
        </div>
      </div>
    </div>

    <!-- Pagination -->
    <div v-if="pagination.last_page > 1" class="pagination">
      <button
        @click="goToPage(pagination.current_page - 1)"
        :disabled="pagination.current_page === 1 || loading"
      >
        ←
      </button>
      <span>Page {{ pagination.current_page }} sur {{ pagination.last_page }}</span>
      <button
        @click="goToPage(pagination.current_page + 1)"
        :disabled="pagination.current_page === pagination.last_page || loading"
      >
        →
      </button>
    </div>

    <!-- Modal détails -->
    <div
      v-if="selectedCommande"
      class="modal-overlay"
      @click.self="selectedCommande = null"
    >
      <div class="modal-content">
        <div class="modal-header">
          <h2>Commande {{ selectedCommande.numero_commande }}</h2>
          <button @click="selectedCommande = null" class="close-btn">×</button>
        </div>

        <div v-if="loadingDetails" class="modal-loading">
          Chargement des détails...
        </div>

        <div v-else class="modal-body">
          <!-- Infos client -->
          <div class="modal-info-row">
            <strong>Client :</strong> {{ selectedCommande.user?.email || 'N/A' }}
            <span class="badge-type">
              {{ selectedCommande.user?.type?.type || 'Particulier' }}
            </span>
          </div>

          <!-- Statut + dates -->
          <div class="modal-info-row">
            <strong>Statut :</strong>
            <span
              class="commande-status"
              :class="getStatusClass(selectedCommande.statut_id)"
            >
              {{ selectedCommande.statut_libelle || getStatusLabel(selectedCommande.statut_id) }}
            </span>
          </div>

          <div class="modal-info-row">
            <strong>Période :</strong>
            {{ formatDate(selectedCommande.date_debut) }} →
            {{ formatDate(selectedCommande.date_fin) }}
            ({{ selectedCommande.nombre_jours || '?' }} jour(s))
          </div>

          <div class="modal-info-row" v-if="selectedCommande.adresse_livraison">
            <strong>Adresse :</strong> {{ selectedCommande.adresse_livraison }}
          </div>

          <div class="modal-info-row" v-if="selectedCommande.notes">
            <strong>Notes :</strong> {{ selectedCommande.notes }}
          </div>

          <!-- Articles -->
          <h3>Articles</h3>
          <div class="articles-list">
            <div
              v-for="item in selectedCommande.details"
              :key="item.id"
              class="article-item"
            >
              <div class="article-info">
                <span class="article-name">{{ item.materiel?.nom || 'N/A' }}</span>
                <span class="article-qty">× {{ item.quantite }}</span>
              </div>
              <span class="article-price">{{ formatPrice(item.sous_total) }}</span>
            </div>
            <div v-if="!selectedCommande.details?.length" class="empty-row">
              Aucun article
            </div>
          </div>

          <!-- Totaux -->
          <div class="totaux-block">
            <div class="total-row" v-if="selectedCommande.frais_livraison > 0">
              <span>Frais livraison</span>
              <span>{{ formatPrice(selectedCommande.frais_livraison) }}</span>
            </div>
            <div class="total-row" v-if="selectedCommande.frais_retour > 0">
              <span>Frais retour</span>
              <span>{{ formatPrice(selectedCommande.frais_retour) }}</span>
            </div>
            <div class="total-row total-final">
              <span>Total TTC</span>
              <span class="total-amount">
                {{ formatPrice(selectedCommande.montant_total) }}
              </span>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import api from '@/services/axios'

const commandes = ref([])
const filterStatut = ref('')
const searchQuery = ref('')
const selectedCommande = ref(null)
const counts = ref({})
const loading = ref(false)
const loadingDetails = ref(false)
const updatingId = ref(null) // id de la commande en cours de mise à jour

const pagination = ref({
  current_page: 1,
  last_page: 1,
  total: 0
})

const statutTabs = [
  { value: '',  label: 'Toutes' },
  { value: '1', label: 'En attente' },
  { value: '2', label: 'Confirmées' },
  { value: '3', label: 'Livrées' },
  { value: '4', label: 'Annulées' }
]

let searchTimeout = null

const debouncedSearch = () => {
  clearTimeout(searchTimeout)
  searchTimeout = setTimeout(() => loadCommandes(1), 300)
}

const changeFilter = (value) => {
  filterStatut.value = value
  loadCommandes(1)
}

/**
 * Charge la liste des commandes (avec pagination, filtre, recherche)
 */
const loadCommandes = async (page = 1) => {
  loading.value = true
  try {
    let url = `/admin/commandes?page=${page}`
    if (filterStatut.value) url += `&statut_id=${filterStatut.value}`
    if (searchQuery.value.trim()) url += `&search=${encodeURIComponent(searchQuery.value.trim())}`

    const response = await api.get(url)
    const data = response.data

    commandes.value = data.data || []
    pagination.value = {
      current_page: data.current_page || 1,
      last_page: data.last_page || 1,
      total: data.total || 0
    }
    counts.value = data.counts || {}
  } catch (error) {
    console.error('Erreur chargement commandes:', error)
    alert(error.response?.data?.message || 'Erreur lors du chargement des commandes')
  } finally {
    loading.value = false
  }
}

/**
 * Met à jour le statut d'une commande avec rollback en cas d'échec
 */
const updateStatut = async (commande, newStatut) => {
  const ancienStatut = commande.statut_id

  // Optimistic update
  commande.statut_id = newStatut
  commande.statut_libelle = getStatusLabel(newStatut)
  updatingId.value = commande.id

  try {
    await api.put(`/admin/commandes/${commande.id}/statut`, {
      statut_id: newStatut
    })

    // Recalculer les compteurs localement
    if (ancienStatut !== newStatut) {
      if (counts.value[String(ancienStatut)] > 0) {
        counts.value[String(ancienStatut)]--
      }
      counts.value[String(newStatut)] = (counts.value[String(newStatut)] || 0) + 1
    }

    // Si un filtre est actif et que la commande ne correspond plus, retirer de la liste
    if (filterStatut.value && String(newStatut) !== filterStatut.value) {
      commandes.value = commandes.value.filter(c => c.id !== commande.id)
    }
  } catch (error) {
    // Rollback
    commande.statut_id = ancienStatut
    commande.statut_libelle = getStatusLabel(ancienStatut)

    console.error('Erreur update statut:', error)
    alert(
      error.response?.data?.message ||
      'Erreur lors du changement de statut. La modification a été annulée.'
    )
  } finally {
    updatingId.value = null
  }
}

/**
 * Ouvre le modal détails et recharge les infos complètes depuis l'API
 */
const showDetails = async (commande) => {
  // Ouvrir le modal immédiatement avec les données déjà connues
  selectedCommande.value = { ...commande }
  loadingDetails.value = true

  try {
    const response = await api.get(`/admin/commandes/${commande.id}`)
    if (response.data?.success && response.data?.data) {
      selectedCommande.value = response.data.data
    }
  } catch (error) {
    console.error('Erreur chargement détails:', error)
    alert('Impossible de charger les détails complets')
  } finally {
    loadingDetails.value = false
  }
}

const goToPage = (page) => {
  if (page >= 1 && page <= pagination.value.last_page && !loading.value) {
    loadCommandes(page)
  }
}

const formatDate = (date) => {
  if (!date) return '—'
  try {
    return new Date(date).toLocaleDateString('fr-FR')
  } catch {
    return date
  }
}

const formatPrice = (price) => {
  return new Intl.NumberFormat('fr-FR', {
    style: 'currency',
    currency: 'EUR'
  }).format(Number(price) || 0)
}

const getStatusLabel = (statusId) => {
  const labels = {
    1: 'En attente',
    2: 'Confirmée',
    3: 'Livrée',
    4: 'Annulée'
  }
  return labels[Number(statusId)] || 'Inconnu'
}

const getStatusClass = (statusId) => {
  const classes = {
    1: 'pending',
    2: 'confirmed',
    3: 'delivered',
    4: 'cancelled'
  }
  return classes[Number(statusId)] || ''
}

onMounted(() => {
  loadCommandes()
})
</script>

<style scoped>
/* Styles complémentaires (à fusionner avec ton CSS existant) */
.commande-card.is-updating {
  opacity: 0.6;
  pointer-events: none;
  transition: opacity 0.2s;
}

.modal-loading {
  padding: 2rem;
  text-align: center;
  color: #666;
}

.modal-info-row {
  margin-bottom: 0.75rem;
  font-size: 0.95rem;
}

.badge-type {
  display: inline-block;
  background: #eef2ff;
  color: #4338ca;
  padding: 2px 8px;
  border-radius: 4px;
  font-size: 0.75rem;
  margin-left: 0.5rem;
  text-transform: capitalize;
}

.totaux-block {
  margin-top: 1rem;
  padding-top: 1rem;
  border-top: 1px solid #eee;
}

.total-final {
  font-weight: bold;
  font-size: 1.1rem;
  margin-top: 0.5rem;
}

.empty-row {
  text-align: center;
  color: #999;
  padding: 1rem;
}

.admin-commandes {
  display: flex;
  flex-direction: column;
  gap: 25px;
  padding: 20px;
  max-width: 1400px;
  margin: 0 auto;
}

/* Navigation Admin */
.admin-nav-tabs {
  display: flex;
  gap: 10px;
  background: white;
  padding: 10px;
  border-radius: 12px;
  box-shadow: 0 2px 8px rgba(0,0,0,0.05);
  margin-bottom: 10px;
}

.admin-tab {
  padding: 10px 20px;
  text-decoration: none;
  color: #64748b;
  font-weight: 600;
  border-radius: 8px;
  transition: all 0.3s;
}

.admin-tab:hover {
  background: #f1f5f9;
  color: #1e293b;
}

.admin-tab.active {
  background: #3b82f6;
  color: white;
}

.admin-tab.back-to-site {
  margin-left: auto;
  color: #10b981;
  background: #f0fdf4;
}
.admin-tab.back-to-site:hover { background: #dcfce7; }

.page-header h1 {
  font-size: 1.8rem;
  color: #1e293b;
  margin: 0;
}

.page-header p {
  color: #64748b;
  margin: 5px 0 0;
}

.status-tabs {
  display: flex;
  gap: 10px;
  flex-wrap: wrap;
  background: white;
  padding: 8px;
  border-radius: 12px;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
}

.status-tab {
  padding: 10px 18px;
  background: transparent;
  border: none;
  border-radius: 8px;
  cursor: pointer;
  font-weight: 500;
  color: #64748b;
  display: flex;
  align-items: center;
  gap: 8px;
  transition: all 0.2s;
}

.status-tab:hover { background: #f1f5f9; }

.status-tab.active {
  background: #3b82f6;
  color: white;
}

.tab-count {
  background: rgba(255, 255, 255, 0.3);
  padding: 2px 8px;
  border-radius: 10px;
  font-size: 0.75rem;
  font-weight: 700;
}

.status-tab:not(.active) .tab-count {
  background: #e2e8f0;
  color: #64748b;
}

.search-bar input {
  width: 100%;
  padding: 12px 18px;
  border: 2px solid #e2e8f0;
  border-radius: 10px;
  font-size: 0.95rem;
  background: white;
}

.search-bar input:focus {
  outline: none;
  border-color: #3b82f6;
}

.commandes-list {
  display: flex;
  flex-direction: column;
  gap: 15px;
}

.commande-card {
  background: white;
  border-radius: 12px;
  padding: 20px;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
  display: flex;
  flex-direction: column;
  gap: 15px;
}

.commande-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding-bottom: 15px;
  border-bottom: 1px solid #f1f5f9;
}

.commande-info {
  display: flex;
  flex-direction: column;
  gap: 3px;
}

.commande-number {
  font-weight: 700;
  font-size: 1.1rem;
  color: #1e293b;
}

.commande-date {
  font-size: 0.85rem;
  color: #94a3b8;
}

.commande-status {
  padding: 6px 14px;
  border-radius: 20px;
  font-size: 0.8rem;
  font-weight: 600;
}

.commande-status.pending { background: #fef3c7; color: #d97706; }
.commande-status.confirmed { background: #dbeafe; color: #2563eb; }
.commande-status.delivered { background: #dcfce7; color: #16a34a; }
.commande-status.cancelled { background: #fee2e2; color: #dc2626; }

.commande-body {
  display: grid;
  grid-template-columns: 200px 1fr;
  gap: 20px;
  align-items: center;
}

.commande-client {
  display: flex;
  align-items: center;
  gap: 12px;
}

.client-avatar {
  width: 45px;
  height: 45px;
  background: #f1f5f9;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 1.5rem;
}

.client-name {
  font-weight: 600;
  color: #1e293b;
  font-size: 0.9rem;
}

.client-type {
  font-size: 0.8rem;
  color: #94a3b8;
  text-transform: capitalize;
}

.commande-details {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
  gap: 15px;
}

.detail-item {
  display: flex;
  flex-direction: column;
  gap: 3px;
}

.detail-label {
  font-size: 0.75rem;
  color: #94a3b8;
  text-transform: uppercase;
  letter-spacing: 0.5px;
}

.detail-value {
  font-weight: 600;
  color: #1e293b;
  font-size: 0.95rem;
}

.detail-value.price {
  color: #10b981;
  font-size: 1.1rem;
}

.commande-actions {
  display: flex;
  gap: 10px;
  justify-content: flex-end;
  padding-top: 15px;
  border-top: 1px solid #f1f5f9;
}

.statut-select {
  padding: 8px 12px;
  border: 2px solid #e2e8f0;
  border-radius: 8px;
  background: white;
  font-weight: 500;
  cursor: pointer;
}

.btn-secondary {
  padding: 8px 16px;
  background: #f1f5f9;
  color: #475569;
  border: none;
  border-radius: 8px;
  font-weight: 600;
  cursor: pointer;
}

.btn-secondary:hover { background: #e2e8f0; }

.empty-state, .loading-state {
  text-align: center;
  padding: 60px 20px;
  color: #94a3b8;
  background: white;
  border-radius: 12px;
}

.empty-icon { font-size: 3rem; display: block; margin-bottom: 15px; }

.pagination { display: flex; justify-content: center; align-items: center; gap: 15px; }
.pagination button { padding: 8px 16px; background: white; border: 1px solid #e2e8f0; border-radius: 8px; cursor: pointer; }
.pagination button:disabled { opacity: 0.5; cursor: not-allowed; }

.modal-overlay {
  position: fixed;
  inset: 0;
  background: rgba(0, 0, 0, 0.5);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 1000;
  padding: 20px;
}

.modal-content {
  background: white;
  border-radius: 12px;
  width: 100%;
  max-width: 600px;
  max-height: 90vh;
  overflow-y: auto;
}

.modal-header {
  padding: 20px;
  display: flex;
  justify-content: space-between;
  align-items: center;
  border-bottom: 1px solid #e2e8f0;
}

.modal-header h2 {
  margin: 0;
  font-size: 1.2rem;
}

.close-btn {
  background: none;
  border: none;
  font-size: 1.8rem;
  cursor: pointer;
  color: #64748b;
}

.modal-body { padding: 20px; }

.modal-body h3 {
  font-size: 1rem;
  color: #64748b;
  text-transform: uppercase;
  letter-spacing: 0.5px;
  margin: 0 0 15px;
}

.articles-list {
  display: flex;
  flex-direction: column;
  gap: 10px;
}

.article-item {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 12px;
  background: #f8fafc;
  border-radius: 8px;
}

.article-info {
  display: flex;
  align-items: center;
  gap: 10px;
}

.article-name {
  font-weight: 600;
  color: #1e293b;
}

.article-qty {
  background: #e2e8f0;
  padding: 2px 8px;
  border-radius: 10px;
  font-size: 0.8rem;
  font-weight: 600;
  color: #475569;
}

.article-price {
  font-weight: 700;
  color: #10b981;
}

.total-row {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-top: 20px;
  padding-top: 20px;
  border-top: 2px solid #e2e8f0;
  font-size: 1.1rem;
  font-weight: 700;
}

.total-amount {
  color: #10b981;
  font-size: 1.4rem;
}

@media (max-width: 768px) {
  .commande-body {
    grid-template-columns: 1fr;
  }
}
</style>