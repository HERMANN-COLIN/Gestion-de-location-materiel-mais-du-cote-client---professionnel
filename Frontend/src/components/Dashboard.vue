<template>
  <div class="dashboard-container">
    <!-- En-tête du dashboard -->
    <div class="dashboard-header">
      <div class="header-content">
        <h1>Tableau de bord</h1>
        <p>Bienvenue {{ userName }}, voici un aperçu de votre activité</p>
      </div>
      <div class="header-date">
        <span class="date-icon">📅</span>
        {{ currentDate }}
      </div>
    </div>

    <!-- Statistiques principales -->
    <div class="stats-grid">
      <div class="stat-card">
        <div class="stat-icon commandes">📦</div>
        <div class="stat-content">
          <div class="stat-value">{{ stats.commandes }}</div>
          <div class="stat-label">Commandes</div>
        </div>
      </div>

      <div class="stat-card">
        <div class="stat-icon en-cours">🔄</div>
        <div class="stat-content">
          <div class="stat-value">{{ stats.commandes_en_cours }}</div>
          <div class="stat-label">En cours</div>
        </div>
      </div>

      <div class="stat-card">
        <div class="stat-icon total">💰</div>
        <div class="stat-content">
          <div class="stat-value">{{ formatPrice(stats.total_depenses) }}</div>
          <div class="stat-label">Total dépensé</div>
        </div>
      </div>

      <div class="stat-card">
        <div class="stat-icon favoris">❤️</div>
        <div class="stat-content">
          <div class="stat-value">{{ stats.favoris }}</div>
          <div class="stat-label">Favoris</div>
        </div>
      </div>
    </div>

    <!-- Grille principale -->
    <div class="dashboard-grid">
      <!-- Dernières commandes -->
      <div class="dashboard-card">
        <div class="card-header">
          <h2>📋 Dernières commandes</h2>
          <RouterLink to="/commandes" class="card-link">Voir tout →</RouterLink>
        </div>
        
        <div v-if="loading.commandes" class="card-loading">
          <div class="loading-spinner"></div>
          <p>Chargement des commandes...</p>
        </div>

        <div v-else-if="dernieresCommandes.length === 0" class="card-empty">
          <div class="empty-icon">📦</div>
          <p>Aucune commande pour le moment</p>
          <RouterLink to="/catalogue" class="empty-link">
            Commencer mes achats
          </RouterLink>
        </div>

        <div v-else class="card-content">
          <div 
            v-for="commande in dernieresCommandes" 
            :key="commande.id" 
            class="commande-item"
          >
            <div class="commande-info">
              <span class="commande-numero">#{{ commande.numero_commande }}</span>
              <span class="commande-date">{{ formatDate(commande.date_commande) }}</span>
            </div>
            <div class="commande-status" :class="getStatusClass(commande.statut)">
              {{ getStatusLabel(commande.statut) }}
            </div>
            <div class="commande-montant">{{ formatPrice(commande.montant_total) }}</div>
          </div>
        </div>
      </div>

      <!-- Articles populaires / récents -->
      <div class="dashboard-card">
        <div class="card-header">
          <h2>🔥 Articles populaires</h2>
          <RouterLink to="/catalogue" class="card-link">Explorer →</RouterLink>
        </div>
        
        <div v-if="loading.articles" class="card-loading">
          <div class="loading-spinner"></div>
          <p>Chargement des articles...</p>
        </div>

        <div v-else class="card-content">
          <div 
            v-for="article in articlesPopulaires" 
            :key="article.id" 
            class="article-item"
          >
            <div class="article-image">
              <img 
                :src="getArticleImage(article)" 
                :alt="article.nom"
                @error="(e) => e.target.src = '/placeholder.jpg'"
              >
            </div>
            <div class="article-info">
              <h3>{{ article.nom }}</h3>
              <p>{{ article.categorie?.nom || 'Non catégorisé' }}</p>
            </div>
            <div class="article-prix">
              {{ formatPrice(article.prix_journalier_ht) }}<span class="prix-period">/jour</span>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Actions rapides -->
    <div class="quick-actions">
      <h2>⚡ Actions rapides</h2>
      <div class="actions-grid">
        <RouterLink to="/catalogue" class="action-card">
          <span class="action-icon">🔍</span>
          <span class="action-text">Parcourir le catalogue</span>
        </RouterLink>

    

        <RouterLink to="/profil" class="action-card">
          <span class="action-icon">👤</span>
          <span class="action-text">Modifier mon profil</span>
        </RouterLink>

        <RouterLink to="/panier" class="action-card">
          <span class="action-icon">🛒</span>
          <span class="action-text">
            Voir mon panier
            <span v-if="panierCount > 0" class="action-badge">{{ panierCount }}</span>
          </span>
        </RouterLink>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useAuthStore } from '@/stores/auth'
import api from '@/services/axios'
import { useRouter } from 'vue-router'

const auth = useAuthStore()
const router = useRouter()

// États de chargement
const loading = ref({
  commandes: true,
  articles: true
})

// Données
const dernieresCommandes = ref([])
const articlesPopulaires = ref([])
const panierCount = ref(0)

// Statistiques calculées
const stats = computed(() => {
  const commandes = dernieresCommandes.value
  return {
    commandes: commandes.length,
    commandes_en_cours: commandes.filter(c => [1, 2, 3].includes(c.statut)).length,
    total_depenses: commandes.reduce((sum, c) => sum + (c.montant_total || 0), 0),
    favoris: 0 // À implémenter avec une vraie liste de favoris
  }
})

// Nom de l'utilisateur
const userName = computed(() => {
  return auth.user?.nom || auth.user?.prenom || auth.user?.email || 'Utilisateur'
})

// Date du jour
const currentDate = computed(() => {
  return new Date().toLocaleDateString('fr-FR', {
    weekday: 'long',
    year: 'numeric',
    month: 'long',
    day: 'numeric'
  })
})

// Charger les dernières commandes
const loadDernieresCommandes = async () => {
  loading.value.commandes = true
  try {
    const response = await api.get('/commandes?limit=5')
    if (response.data.success) {
      dernieresCommandes.value = response.data.data || []
    }
  } catch (error) {
    console.error('Erreur chargement commandes:', error)
  } finally {
    loading.value.commandes = false
  }
}

// Charger les articles populaires
const loadArticlesPopulaires = async () => {
  loading.value.articles = true
  try {
    const response = await api.get('/materiels/populaires')
    if (response.data.success) {
      articlesPopulaires.value = response.data.data || []
    }
  } catch (error) {
    console.error('Erreur chargement articles:', error)
  } finally {
    loading.value.articles = false
  }
}

// Charger le nombre d'articles dans le panier
const loadPanierCount = async () => {
  try {
    const response = await api.get('/panier/compter')
    if (response.data.success) {
      panierCount.value = response.data.count
    }
  } catch (error) {
    console.error('Erreur chargement panier:', error)
  }
}

// Obtenir l'image d'un article
const getArticleImage = (article) => {
  if (article.photos && article.photos.length > 0) {
    return article.photos[0].url_photo || `/storage/${article.photos[0].chemin_fichier}`
  }
  if (article.main_photo) {
    return article.main_photo
  }
  return '/placeholder.jpg'
}

// Formater la date
const formatDate = (dateString) => {
  if (!dateString) return ''
  return new Date(dateString).toLocaleDateString('fr-FR', {
    day: '2-digit',
    month: '2-digit',
    year: 'numeric'
  })
}

// Formater le prix
const formatPrice = (price) => {
  if (price === undefined || price === null) return '0 €'
  return new Intl.NumberFormat('fr-FR', {
    style: 'currency',
    currency: 'EUR',
    minimumFractionDigits: 2
  }).format(price)
}

// Obtenir le label du statut
const getStatusLabel = (statut) => {
  const statuses = {
    1: 'En attente',
    2: 'Confirmée',
    3: 'En cours',
    4: 'Terminée',
    5: 'Annulée'
  }
  return statuses[statut] || 'Inconnu'
}

// Obtenir la classe CSS du statut
const getStatusClass = (statut) => {
  const classes = {
    1: 'status-pending',
    2: 'status-confirmed',
    3: 'status-progress',
    4: 'status-completed',
    5: 'status-cancelled'
  }
  return classes[statut] || 'status-unknown'
}

// Initialisation
onMounted(() => {
  loadDernieresCommandes()
  loadArticlesPopulaires()
  loadPanierCount()
})
</script>

<style scoped>
.dashboard-container {
  max-width: 1200px;
  margin: 0 auto;
  padding: 20px;
}

/* En-tête */
.dashboard-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 30px;
  padding: 20px;
  background: white;
  border-radius: 15px;
  box-shadow: 0 2px 10px rgba(0,0,0,0.05);
}

.header-content h1 {
  font-size: 2rem;
  color: #2d3748;
  margin: 0 0 5px 0;
}

.header-content p {
  color: #718096;
  margin: 0;
}

.header-date {
  display: flex;
  align-items: center;
  gap: 8px;
  padding: 10px 20px;
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  color: white;
  border-radius: 10px;
  font-size: 0.95rem;
  text-transform: capitalize;
}

.date-icon {
  font-size: 1.2rem;
}

/* Statistiques */
.stats-grid {
  display: grid;
  grid-template-columns: repeat(4, 1fr);
  gap: 20px;
  margin-bottom: 30px;
}

.stat-card {
  display: flex;
  align-items: center;
  gap: 20px;
  padding: 20px;
  background: white;
  border-radius: 15px;
  box-shadow: 0 2px 10px rgba(0,0,0,0.05);
  transition: all 0.3s ease;
}

.stat-card:hover {
  transform: translateY(-3px);
  box-shadow: 0 8px 20px rgba(0,0,0,0.1);
}

.stat-icon {
  width: 50px;
  height: 50px;
  display: flex;
  align-items: center;
  justify-content: center;
  border-radius: 12px;
  font-size: 1.5rem;
}

.stat-icon.commandes {
  background: #e3f2fd;
  color: #1976d2;
}

.stat-icon.en-cours {
  background: #fff3e0;
  color: #f57c00;
}

.stat-icon.total {
  background: #e8f5e9;
  color: #388e3c;
}

.stat-icon.favoris {
  background: #fce4ec;
  color: #c2185b;
}

.stat-content {
  flex: 1;
}

.stat-value {
  font-size: 1.8rem;
  font-weight: 700;
  color: #2d3748;
  line-height: 1.2;
}

.stat-label {
  color: #718096;
  font-size: 0.9rem;
}

/* Grille principale */
.dashboard-grid {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 20px;
  margin-bottom: 30px;
}

.dashboard-card {
  background: white;
  border-radius: 15px;
  box-shadow: 0 2px 10px rgba(0,0,0,0.05);
  overflow: hidden;
}

.card-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 20px;
  border-bottom: 1px solid #e2e8f0;
}

.card-header h2 {
  font-size: 1.2rem;
  color: #2d3748;
  margin: 0;
}

.card-link {
  color: #667eea;
  text-decoration: none;
  font-size: 0.9rem;
  font-weight: 500;
  transition: all 0.3s;
}

.card-link:hover {
  color: #5a67d8;
  text-decoration: underline;
}

.card-loading {
  padding: 40px;
  text-align: center;
  color: #718096;
}

.loading-spinner {
  width: 40px;
  height: 40px;
  margin: 0 auto 15px;
  border: 3px solid #e2e8f0;
  border-top-color: #667eea;
  border-radius: 50%;
  animation: spin 1s linear infinite;
}

@keyframes spin {
  to { transform: rotate(360deg); }
}

.card-empty {
  padding: 40px;
  text-align: center;
  color: #718096;
}

.empty-icon {
  font-size: 3rem;
  margin-bottom: 15px;
  opacity: 0.5;
}

.empty-link {
  display: inline-block;
  margin-top: 15px;
  padding: 10px 20px;
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  color: white;
  text-decoration: none;
  border-radius: 8px;
  font-weight: 500;
  transition: all 0.3s;
}

.empty-link:hover {
  transform: translateY(-2px);
  box-shadow: 0 4px 15px rgba(102,126,234,0.3);
}

.card-content {
  padding: 20px;
}

/* Commandes */
.commande-item {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 12px 0;
  border-bottom: 1px solid #f1f5f9;
}

.commande-item:last-child {
  border-bottom: none;
}

.commande-info {
  flex: 1;
}

.commande-numero {
  display: block;
  font-weight: 600;
  color: #2d3748;
  margin-bottom: 3px;
}

.commande-date {
  font-size: 0.85rem;
  color: #718096;
}

.commande-status {
  padding: 4px 12px;
  border-radius: 20px;
  font-size: 0.8rem;
  font-weight: 600;
  margin: 0 15px;
}

.status-pending {
  background: #fef3c7;
  color: #92400e;
}

.status-confirmed {
  background: #dbeafe;
  color: #1e40af;
}

.status-progress {
  background: #e0e7ff;
  color: #5b21b6;
}

.status-completed {
  background: #d1fae5;
  color: #065f46;
}

.status-cancelled {
  background: #fee2e2;
  color: #991b1b;
}

.commande-montant {
  font-weight: 600;
  color: #2d3748;
  min-width: 100px;
  text-align: right;
}

/* Articles */
.article-item {
  display: flex;
  align-items: center;
  gap: 15px;
  padding: 12px 0;
  border-bottom: 1px solid #f1f5f9;
}

.article-item:last-child {
  border-bottom: none;
}

.article-image {
  width: 60px;
  height: 60px;
  border-radius: 10px;
  overflow: hidden;
  background: #f7fafc;
  flex-shrink: 0;
}

.article-image img {
  width: 100%;
  height: 100%;
  object-fit: cover;
}

.article-info {
  flex: 1;
}

.article-info h3 {
  font-size: 1rem;
  color: #2d3748;
  margin: 0 0 3px 0;
}

.article-info p {
  font-size: 0.85rem;
  color: #718096;
  margin: 0;
}

.article-prix {
  font-weight: 600;
  color: #10b981;
  text-align: right;
  white-space: nowrap;
}

.prix-period {
  font-size: 0.8rem;
  font-weight: 400;
  color: #718096;
  margin-left: 2px;
}

/* Actions rapides */
.quick-actions {
  margin-top: 30px;
}

.quick-actions h2 {
  font-size: 1.2rem;
  color: #2d3748;
  margin: 0 0 15px 0;
}

.actions-grid {
  display: grid;
  grid-template-columns: repeat(4, 1fr);
  gap: 20px;
}

.action-card {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 10px;
  padding: 20px;
  background: white;
  border-radius: 15px;
  box-shadow: 0 2px 10px rgba(0,0,0,0.05);
  text-decoration: none;
  transition: all 0.3s ease;
}

.action-card:hover {
  transform: translateY(-3px);
  box-shadow: 0 8px 20px rgba(0,0,0,0.1);
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
}

.action-card:hover .action-icon,
.action-card:hover .action-text {
  color: white;
}

.action-icon {
  font-size: 2rem;
  color: #667eea;
  transition: color 0.3s;
}

.action-text {
  font-size: 0.95rem;
  color: #2d3748;
  font-weight: 500;
  text-align: center;
  transition: color 0.3s;
}

.action-badge {
  display: inline-block;
  margin-left: 5px;
  padding: 2px 6px;
  background: white;
  color: #667eea;
  border-radius: 10px;
  font-size: 0.75rem;
  font-weight: 600;
}

.action-card:hover .action-badge {
  background: white;
  color: #667eea;
}

/* Responsive */
@media (max-width: 1024px) {
  .stats-grid,
  .actions-grid {
    grid-template-columns: repeat(2, 1fr);
  }
  
  .dashboard-grid {
    grid-template-columns: 1fr;
  }
}

@media (max-width: 768px) {
  .dashboard-header {
    flex-direction: column;
    gap: 15px;
    text-align: center;
  }
  
  .stats-grid,
  .actions-grid {
    grid-template-columns: 1fr;
  }
  
  .commande-item {
    flex-direction: column;
    align-items: flex-start;
    gap: 10px;
  }
  
  .commande-status {
    margin: 0;
  }
  
  .commande-montant {
    text-align: left;
  }
}
</style>