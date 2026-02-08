<template>
  <div class="catalogue-container">
    <!-- Hero Section -->
    <div class="hero-section">
      <div class="hero-content">
        <h1 class="hero-title">Catalogue des matériels</h1>
        <p class="hero-subtitle">Trouvez le matériel idéal pour rendre vos événements inoubliables</p>
        <div class="hero-stats">
          <div class="stat-item">
            <span class="stat-number">{{ categoriesCount }}</span>
            <span class="stat-label">Catégories</span>
          </div>
          <div class="stat-divider">•</div>
          <div class="stat-item">
            <span class="stat-number">{{ totalMaterials }}</span>
            <span class="stat-label">Matériels</span>
          </div>
          <div class="stat-divider">•</div>
          <div class="stat-item">
            <span class="stat-number">24h</span>
            <span class="stat-label">Livraison</span>
          </div>
        </div>
      </div>
      <div class="hero-decoration">
        <div class="decoration-circle circle-1"></div>
        <div class="decoration-circle circle-2"></div>
        <div class="decoration-circle circle-3"></div>
      </div>
    </div>

    <!-- Filtres et recherche -->
    <div class="filters-card">
      <div class="filters-header">
        <h3><span class="icon">🔍</span> Affinez votre recherche</h3>
        <p>Trouvez exactement ce dont vous avez besoin</p>
      </div>
      
      <div class="filters-grid">
        <div class="filter-group">
          <label for="categorie" class="filter-label">
            <span class="filter-icon">📁</span>
            Catégorie
          </label>
          <div class="select-wrapper">
            <select 
              id="categorie" 
              v-model="selectedCategorie" 
              @change="filterByCategorie"
              class="filter-select"
            >
              <option value="">Toutes les catégories</option>
              <option 
                v-for="categorie in filteredCategories" 
                :key="categorie.id" 
                :value="categorie.id"
              >
                {{ categorie.nom }}
              </option>
            </select>
            <span class="select-arrow">▼</span>
          </div>
        </div>

        <div class="filter-group">
          <label for="prix-min" class="filter-label">
            <span class="filter-icon">💰</span>
            Prix min
          </label>
          <div class="input-wrapper">
            <span class="input-prefix">€</span>
            <input 
              id="prix-min" 
              type="number" 
              v-model.number="prixMin" 
              placeholder="0"
              min="0"
              class="filter-input"
              @input="filterByPrice"
            >
          </div>
        </div>

        <div class="filter-group">
          <label for="prix-max" class="filter-label">
            <span class="filter-icon">💎</span>
            Prix max
          </label>
          <div class="input-wrapper">
            <span class="input-prefix">€</span>
            <input 
              id="prix-max" 
              type="number" 
              v-model.number="prixMax" 
              placeholder="100"
              min="0"
              class="filter-input"
              @input="filterByPrice"
            >
          </div>
        </div>

       

        <div class="filter-actions">
          <button @click="resetFilters" class="reset-btn">
            <span class="btn-icon">🔄</span>
            Réinitialiser
          </button>
          <button @click="applyFilters" class="apply-btn">
            <span class="btn-icon">✨</span>
            Appliquer
          </button>
        </div>
      </div>

      <!-- Filtres rapides par catégorie -->
      <div class="quick-filters" v-if="!selectedCategorie">
        <h4>Parcourir par catégorie :</h4>
        <div class="category-chips">
          <button 
            v-for="categorie in filteredCategories" 
            :key="categorie.id"
            @click="selectCategory(categorie.id)"
            class="category-chip"
          >
            <span class="chip-icon">{{ getCategoryIcon(categorie.nom) }}</span>
            {{ categorie.nom }}
          </button>
        </div>
      </div>

      <!-- Statistiques -->
      <div class="stats-container">
        <div class="stat-card">
          <div class="stat-icon">📊</div>
          <div class="stat-content">
            <div class="stat-number">{{ pagination.total || 0 }}</div>
            <div class="stat-label">Matériels trouvés</div>
          </div>
        </div>
        <div class="stat-card" v-if="selectedCategorie && selectedCategorieName">
          <div class="stat-icon">🏷️</div>
          <div class="stat-content">
            <div class="stat-number">{{ selectedCategorieName }}</div>
            <div class="stat-label">Catégorie sélectionnée</div>
          </div>
        </div>
        <div class="stat-card">
          <div class="stat-icon">📄</div>
          <div class="stat-content">
            <div class="stat-number">{{ pagination.current_page }}</div>
            <div class="stat-label">Page sur {{ pagination.last_page }}</div>
          </div>
        </div>
      </div>
    </div>

    <!-- Loading -->
    <div v-if="loading" class="loading-container">
      <div class="loading-spinner">
        <div class="spinner-ring"></div>
        <div class="spinner-ring"></div>
        <div class="spinner-ring"></div>
      </div>
      <p class="loading-text">Chargement des matériels...</p>
      <p class="loading-subtext">Patience, ça arrive !</p>
    </div>

    <!-- Liste des matériels -->
    <div v-else class="materiels-section">
      <!-- Message si aucun résultat -->
      <div v-if="materiels.length === 0" class="no-results-container">
        <div class="no-results-illustration">
          <div class="illustration">🔍</div>
        </div>
        <div class="no-results-content">
          <h3 class="no-results-title">Oups ! Aucun matériel trouvé</h3>
          <p class="no-results-description">
            Essayez de modifier vos critères de recherche ou consultez toutes nos catégories
          </p>
          <div class="no-results-actions">
            <button @click="resetFilters" class="primary-btn">
              <span class="btn-icon">📦</span>
              Voir tous les matériels
            </button>
            <button @click="contactSupport" class="secondary-btn">
              <span class="btn-icon">💬</span>
              Demander de l'aide
            </button>
          </div>
        </div>
      </div>

      <!-- Grille des matériels -->
      <div v-else>
        <div class="results-header">
          <h2 class="results-title">Nos matériels disponibles</h2>
          <div class="sort-options">
            <span class="sort-label">Trier par :</span>
            <select v-model="sortBy" @change="applySort" class="sort-select">
              <option value="nom">Nom (A-Z)</option>
              <option value="prix_journalier-asc">Prix (Croissant)</option>
              <option value="prix_journalier-desc">Prix (Décroissant)</option>
              <option value="stock_disponible-desc">Disponibilité</option>
              <option value="created_at-desc">Nouveautés</option>
            </select>
          </div>
        </div>

        <div class="materiels-grid">
          <div 
            v-for="materiel in filteredMateriels"
            :key="materiel.id" 
            class="materiel-card"
            @click="goToDetail(materiel.id)"
          >
            <div class="materiel-image">
              <img 
                :src="getPhotoUrl(materiel)" 
                :alt="materiel.nom"
                @error="setDefaultImage"
                class="product-image"
              >
              
              <!-- Badges -->
              <div class="card-badges">
                <div class="stock-badge" :class="{ 
                  'in-stock': materiel.stock_disponible > 0, 
                  'low-stock': materiel.stock_disponible < 10 && materiel.stock_disponible > 0,
                  'out-of-stock': materiel.stock_disponible === 0 
                }">
                  <span class="badge-icon">
                    <span v-if="materiel.stock_disponible > 0">✓</span>
                    <span v-else>✗</span>
                  </span>
                  {{ materiel.stock_disponible > 0 ? `${materiel.stock_disponible} dispo` : 'Rupture' }}
                </div>
                <div v-if="isNew(materiel)" class="new-badge">
                  <span class="badge-icon">🎉</span>
                  Nouveau
                </div>
                <div v-if="materiel.prix_journalier < 5" class="promo-badge">
                  <span class="badge-icon">🔥</span>
                  Bon plan
                </div>
              </div>
              
              <!-- Quick Actions -->
              <div class="quick-actions">
                <button 
                  @click.stop="addToCart(materiel)" 
                  :disabled="!materiel.stock_disponible || materiel.stock_disponible === 0"
                  class="quick-cart-btn"
                  :title="materiel.stock_disponible > 0 ? 'Ajouter au panier' : 'Indisponible'"
                >
                  <span class="quick-icon">🛒</span>
                </button>
                <button 
                  @click.stop="toggleFavorite(materiel)"
                  class="quick-fav-btn"
                  :class="{ 'favorited': isFavorite(materiel.id) }"
                  title="Ajouter aux favoris"
                >
                  <span class="quick-icon">{{ isFavorite(materiel.id) ? '❤️' : '🤍' }}</span>
                </button>
              </div>
            </div>
            
            <div class="materiel-info">
              <div class="materiel-header">
                <h3 class="materiel-title">{{ materiel.nom || 'Sans nom' }}</h3>
                <span class="categorie-tag">
                  <span class="tag-icon">{{ getCategoryIcon(materiel.categorie?.nom) }}</span>
                  {{ materiel.categorie?.nom || 'Non catégorisé' }}
                </span>
              </div>
              
              <p class="materiel-description">
                {{ truncateDescription(materiel.description) }}
              </p>
              
              <div class="materiel-details">
                <div class="price-section">
                  <div class="price-wrapper">
                    <span class="price">{{ formatPrice(materiel.prix_journalier) }}</span>
                    <span class="price-period">/jour</span>
                  </div>
                  <div v-if="materiel.prix_journalier < 10" class="price-note">
                    ⭐ Location économique
                  </div>
                </div>
                <span v-if="materiel.dimensions" class="dimensions">
                  <span class="dim-icon">📏</span>
                  {{ materiel.dimensions }}
                </span>
              </div>

              <div class="materiel-actions">
                <button 
                  @click.stop="addToCart(materiel)" 
                  :disabled="!materiel.stock_disponible || materiel.stock_disponible === 0"
                  class="add-to-cart-btn"
                  :class="{ 'disabled': !materiel.stock_disponible || materiel.stock_disponible === 0 }"
                >
                  <span class="cart-icon">🛒</span>
                  {{ materiel.stock_disponible > 0 ? 'Ajouter au panier' : 'Indisponible' }}
                </button>
                
                <router-link 
                  v-if="materiel.id"
                  :to="`/materiels/${materiel.id}`" 
                  class="details-btn"
                  @click.stop
                >
                  <span class="details-icon">👁️</span>
                  Voir détails
                </router-link>
              </div>
            </div>
          </div>
        </div>

        <!-- Pagination -->
        <div v-if="pagination.last_page > 1" class="pagination-container">
          <div class="pagination">
            <button 
              @click="prevPage" 
              :disabled="pagination.current_page === 1"
              class="pagination-btn prev-btn"
            >
              <span class="btn-icon">←</span>
              Précédent
            </button>
            
            <div class="page-numbers">
              <button 
                v-for="page in visiblePages" 
                :key="page"
                @click="goToPage(page)"
                class="page-btn"
                :class="{ 'active': page === pagination.current_page }"
              >
                {{ page }}
              </button>
              <span v-if="showEllipsis" class="ellipsis">...</span>
            </div>
            
            <button 
              @click="nextPage" 
              :disabled="pagination.current_page === pagination.last_page"
              class="pagination-btn next-btn"
            >
              Suivant
              <span class="btn-icon">→</span>
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- CTA Section -->
    <div class="cta-section">
      <div class="cta-content">
        <div class="cta-text">
          <h2 class="cta-title">Besoin d'aide pour choisir ?</h2>
          <p class="cta-description">
            Notre équipe d'experts est à votre disposition pour vous conseiller 
            et vous accompagner dans l'organisation de votre événement.
          </p>
          <div class="cta-features">
            <div class="feature">
              <span class="feature-icon">🤝</span>
              <span>Conseil personnalisé</span>
            </div>
            <div class="feature">
              <span class="feature-icon">🚚</span>
              <span>Livraison rapide</span>
            </div>
            <div class="feature">
              <span class="feature-icon">💬</span>
              <span>Support 7j/7</span>
            </div>
          </div>
        </div>
        <div class="cta-actions">
          <button @click="contactSupport" class="contact-btn">
            <span class="btn-icon">📞</span>
            Nous contacter
          </button>
          <button @click="showHelpModal" class="help-btn">
            <span class="btn-icon">❓</span>
            Demander conseil
          </button>
        </div>
      </div>
    </div>

    <!-- Newsletter -->
    <div class="newsletter-section">
      <div class="newsletter-content">
        <div class="newsletter-icon">📧</div>
        <div class="newsletter-text">
          <h3>Ne manquez pas nos nouveautés</h3>
          <p>Inscrivez-vous à notre newsletter pour recevoir les offres exclusives</p>
        </div>
        <div class="newsletter-form">
          <input type="email" placeholder="Votre email" class="newsletter-input">
          <button class="newsletter-btn">S'inscrire</button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, computed } from 'vue'
import { useRouter } from 'vue-router'
import api from '@/services/axios'
import { getMaterielPhotoUrl } from '@/utils/imageHelper'

const router = useRouter()

// Réactifs
const categories = ref([])
const materiels = ref([])
const selectedCategorie = ref('')
const searchQuery = ref('')
const prixMin = ref('')
const prixMax = ref('')
const loading = ref(false)
const sortBy = ref('nom')
const favorites = ref(new Set())

const pagination = ref({
  current_page: 1,
  last_page: 1,
  total: 0,
  per_page: 12
})

// Computed properties
const filteredCategories = computed(() => {
  return categories.value.filter(cat => cat && cat.id && cat.nom)
})

const categoriesCount = computed(() => {
  return filteredCategories.value.length
})

const totalMaterials = computed(() => {
  return pagination.value.total
})

const selectedCategorieName = computed(() => {
  if (!selectedCategorie.value || !categories.value.length) return ''
  const categorie = categories.value.find(c => c && c.id == selectedCategorie.value)
  return categorie ? categorie.nom : ''
})

const filteredMateriels = computed(() => {
  return materiels.value.filter(m => m && m.id)
})

// Pages visibles dans la pagination
const visiblePages = computed(() => {
  const pages = []
  const current = pagination.value.current_page
  const last = pagination.value.last_page
  const maxVisible = 5

  let start = Math.max(1, current - Math.floor(maxVisible / 2))
  let end = Math.min(last, start + maxVisible - 1)

  if (end - start + 1 < maxVisible) {
    start = Math.max(1, end - maxVisible + 1)
  }

  for (let i = start; i <= end; i++) {
    pages.push(i)
  }

  return pages
})

const showEllipsis = computed(() => {
  return pagination.value.last_page > visiblePages.value.length
})

// Charger les catégories
const loadCategories = async () => {
  try {
    const response = await api.get('/categories-materiel')
    
    let categoriesData = []
    
    if (response.data && response.data.success && Array.isArray(response.data.data)) {
      categoriesData = response.data.data
    } else if (Array.isArray(response.data)) {
      categoriesData = response.data
    } else if (response.data && response.data.data && Array.isArray(response.data.data)) {
      categoriesData = response.data.data
    }
    
    categories.value = categoriesData.filter(cat => cat && cat.id && cat.nom)
  } catch (error) {
    console.error('Erreur chargement catégories:', error)
    categories.value = []
  }
}

// Charger les matériels
const loadMateriels = async (page = 1) => {
  loading.value = true
  try {
    let url = `/materiels?page=${page}&per_page=${pagination.value.per_page}`
    
    if (selectedCategorie.value) {
      url += `&categorie_id=${selectedCategorie.value}`
    }
    if (prixMin.value) {
      url += `&prix_min=${prixMin.value}`
    }
    if (prixMax.value) {
      url += `&prix_max=${prixMax.value}`
    }
    
    // Ajouter le tri
    const [sortField, sortOrder] = sortBy.value.split('-')
    url += `&sort=${sortField}&order=${sortOrder || 'asc'}`
    
    const response = await api.get(url)
    const data = response.data
    
    let materielsData = []
    let paginationData = {
      current_page: 1,
      last_page: 1,
      total: 0,
      per_page: 12
    }
    
    if (data && data.success && data.data) {
      materielsData = Array.isArray(data.data) ? data.data : []
      paginationData = {
        current_page: data.current_page || 1,
        last_page: data.last_page || 1,
        total: data.total || 0,
        per_page: data.per_page || 12
      }
    } else if (Array.isArray(data)) {
      materielsData = data
      paginationData = {
        current_page: 1,
        last_page: 1,
        total: data.length,
        per_page: 12
      }
    } else if (data && data.data && Array.isArray(data.data)) {
      materielsData = data.data
      paginationData = {
        current_page: data.current_page || 1,
        last_page: data.last_page || 1,
        total: data.total || data.data.length,
        per_page: data.per_page || 12
      }
    }
    
    materiels.value = materielsData.filter(m => m && m.id)
    pagination.value = paginationData
    
  } catch (error) {
    console.error('Erreur chargement matériels:', error)
    materiels.value = []
    pagination.value = {
      current_page: 1,
      last_page: 1,
      total: 0,
      per_page: 12
    }
  } finally {
    loading.value = false
  }
}

// Filtrage
const filterByCategorie = () => {
  pagination.value.current_page = 1
  loadMateriels()
}

const selectCategory = (categoryId) => {
  selectedCategorie.value = categoryId
  filterByCategorie()
}

const filterByPrice = () => {
  clearTimeout(window.priceFilterTimeout)
  window.priceFilterTimeout = setTimeout(() => {
    pagination.value.current_page = 1
    loadMateriels()
  }, 500)
}

const applyFilters = () => {
  pagination.value.current_page = 1
  loadMateriels()
}

const resetFilters = () => {
  selectedCategorie.value = ''
  searchQuery.value = ''
  prixMin.value = ''
  prixMax.value = ''
  sortBy.value = 'nom'
  pagination.value.current_page = 1
  loadMateriels()
}

// Tri
const applySort = () => {
  pagination.value.current_page = 1
  loadMateriels()
}

// Recherche
const searchMateriels = async () => {
  if (searchQuery.value.length < 2) {
    if (searchQuery.value.length === 0) {
      loadMateriels()
    }
    return
  }

  try {
    const response = await api.get(`/materiels/search?q=${searchQuery.value}`)
    
    let searchResults = []
    
    if (response.data && response.data.success && response.data.data) {
      searchResults = Array.isArray(response.data.data) ? response.data.data : []
    } else if (Array.isArray(response.data)) {
      searchResults = response.data
    } else if (response.data && response.data.data) {
      searchResults = Array.isArray(response.data.data) ? response.data.data : []
    }
    
    materiels.value = searchResults.filter(m => m && m.id)
    pagination.value = {
      current_page: 1,
      last_page: 1,
      total: materiels.value.length
    }
  } catch (error) {
    console.error('Erreur recherche:', error)
  }
}

// Pagination
const nextPage = () => {
  if (pagination.value.current_page < pagination.value.last_page) {
    pagination.value.current_page++
    loadMateriels(pagination.value.current_page)
    window.scrollTo({ top: 0, behavior: 'smooth' })
  }
}

const prevPage = () => {
  if (pagination.value.current_page > 1) {
    pagination.value.current_page--
    loadMateriels(pagination.value.current_page)
    window.scrollTo({ top: 0, behavior: 'smooth' })
  }
}

const goToPage = (page) => {
  if (page !== pagination.value.current_page) {
    pagination.value.current_page = page
    loadMateriels(page)
    window.scrollTo({ top: 0, behavior: 'smooth' })
  }
}

// Utilitaires
const getPhotoUrl = (materiel) => {
  return getMaterielPhotoUrl(materiel)
}

const setDefaultImage = (event) => {
  event.target.src = '/placeholder.jpg'
}

const truncateDescription = (description) => {
  if (!description) return 'Aucune description disponible'
  return description.length > 100 ? description.substring(0, 100) + '...' : description
}

const formatPrice = (price) => {
  if (price === undefined || price === null || isNaN(price)) return '0,00 €'
  return new Intl.NumberFormat('fr-FR', {
    style: 'currency',
    currency: 'EUR',
    minimumFractionDigits: 2
  }).format(price)
}

const isNew = (materiel) => {
  if (!materiel || !materiel.created_at) return false
  try {
    const createdDate = new Date(materiel.created_at)
    const now = new Date()
    const diffDays = Math.floor((now - createdDate) / (1000 * 60 * 60 * 24))
    return diffDays < 30
  } catch (error) {
    return false
  }
}

const getCategoryIcon = (categoryName) => {
  if (!categoryName) return '📦'
  const name = categoryName.toLowerCase()
  if (name.includes('siege')) return '🪑'
  if (name.includes('table')) return '🪟'
  if (name.includes('deco')) return '✨'
  if (name.includes('lumière') || name.includes('lumiere')) return '💡'
  if (name.includes('son')) return '🔊'
  return '📦'
}

const isFavorite = (materielId) => {
  return favorites.value.has(materielId)
}

const toggleFavorite = (materiel) => {
  if (favorites.value.has(materiel.id)) {
    favorites.value.delete(materiel.id)
  } else {
    favorites.value.add(materiel.id)
  }
}

// Navigation
const goToDetail = (id) => {
  if (!id) return
  router.push(`/materiels/${id}`)
}

// Panier
const addToCart = (materiel) => {
  if (!materiel || !materiel.stock_disponible || materiel.stock_disponible === 0) return
  
  const event = new CustomEvent('addToCart', { 
    detail: { 
      materiel,
      quantity: 1 
    } 
  })
  window.dispatchEvent(event)
  
  // Notification visuelle
  showNotification(`${materiel.nom} ajouté au panier!`, 'success')
}

// Notifications
const showNotification = (message, type = 'info') => {
  const event = new CustomEvent('showNotification', {
    detail: { message, type }
  })
  window.dispatchEvent(event)
}

// Modals
const showHelpModal = () => {
  // À implémenter
  console.log('Afficher modal d\'aide')
}

const contactSupport = () => {
  router.push('/contact')
}

// Initialisation
onMounted(() => {
  loadCategories()
  loadMateriels()
})
</script>

<style scoped>
/* Styles globaux améliorés */
.catalogue-container {
  max-width: 1400px;
  margin: 0 auto;
  padding: 20px;
  min-height: 100vh;
}

/* Hero Section */
.hero-section {
  position: relative;
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  border-radius: 20px;
  padding: 60px 40px;
  margin-bottom: 40px;
  overflow: hidden;
  color: white;
}

.hero-content {
  position: relative;
  z-index: 2;
  max-width: 800px;
  margin: 0 auto;
  text-align: center;
}

.hero-title {
  font-size: 3.5rem;
  font-weight: 800;
  margin-bottom: 15px;
  background: linear-gradient(to right, #ffffff, #e2e8ff);
  -webkit-background-clip: text;
  -webkit-text-fill-color: transparent;
  text-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
}

.hero-subtitle {
  font-size: 1.2rem;
  opacity: 0.9;
  margin-bottom: 30px;
  line-height: 1.6;
}

.hero-stats {
  display: flex;
  justify-content: center;
  align-items: center;
  gap: 30px;
  margin-top: 40px;
}

.stat-item {
  display: flex;
  flex-direction: column;
  align-items: center;
}

.stat-number {
  font-size: 2.5rem;
  font-weight: 800;
  line-height: 1;
}

.stat-label {
  font-size: 0.9rem;
  opacity: 0.8;
  margin-top: 5px;
}

.stat-divider {
  opacity: 0.5;
  font-size: 1.5rem;
}

.hero-decoration {
  position: absolute;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  overflow: hidden;
}

.decoration-circle {
  position: absolute;
  border-radius: 50%;
  background: rgba(255, 255, 255, 0.1);
}

.circle-1 {
  width: 300px;
  height: 300px;
  top: -150px;
  right: -100px;
}

.circle-2 {
  width: 200px;
  height: 200px;
  bottom: -80px;
  left: -80px;
}

.circle-3 {
  width: 150px;
  height: 150px;
  top: 50%;
  left: 10%;
}

/* Filtres */
.filters-card {
  background: white;
  border-radius: 20px;
  padding: 30px;
  margin-bottom: 30px;
  box-shadow: 0 10px 40px rgba(0, 0, 0, 0.08);
  border: 1px solid rgba(0, 0, 0, 0.05);
}

.filters-header {
  margin-bottom: 25px;
}

.filters-header h3 {
  font-size: 1.5rem;
  font-weight: 700;
  color: #2d3748;
  margin-bottom: 8px;
  display: flex;
  align-items: center;
  gap: 10px;
}

.filters-header p {
  color: #718096;
  font-size: 0.95rem;
}

.filters-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
  gap: 20px;
  margin-bottom: 25px;
}

.filter-group {
  display: flex;
  flex-direction: column;
  gap: 8px;
}

.filter-label {
  font-weight: 600;
  color: #4a5568;
  font-size: 0.9rem;
  display: flex;
  align-items: center;
  gap: 8px;
}

.select-wrapper,
.input-wrapper,
.search-wrapper {
  position: relative;
}

.filter-select,
.filter-input,
.search-input {
  width: 100%;
  padding: 14px 15px;
  border: 2px solid #e2e8f0;
  border-radius: 12px;
  font-size: 1rem;
  transition: all 0.3s;
  background: white;
  color: #2d3748;
}

.filter-select:focus,
.filter-input:focus,
.search-input:focus {
  outline: none;
  border-color: #667eea;
  box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
}

.filter-select {
  appearance: none;
  padding-right: 40px;
}

.select-arrow {
  position: absolute;
  right: 15px;
  top: 50%;
  transform: translateY(-50%);
  color: #a0aec0;
  pointer-events: none;
}

.input-prefix {
  position: absolute;
  left: 15px;
  top: 50%;
  transform: translateY(-50%);
  color: #a0aec0;
  font-weight: 600;
}

.input-wrapper .filter-input {
  padding-left: 40px;
}

.search-group {
  grid-column: span 2;
}

.search-input {
  padding-right: 50px;
}

.search-btn {
  position: absolute;
  right: 10px;
  top: 50%;
  transform: translateY(-50%);
  background: #667eea;
  color: white;
  border: none;
  width: 36px;
  height: 36px;
  border-radius: 10px;
  display: flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
  transition: all 0.3s;
}

.search-btn:hover {
  background: #5a6fd8;
  transform: translateY(-50%) scale(1.05);
}

.filter-actions {
  grid-column: 1 / -1;
  display: flex;
  gap: 15px;
  margin-top: 10px;
}

.reset-btn,
.apply-btn {
  flex: 1;
  padding: 16px;
  border: none;
  border-radius: 12px;
  font-weight: 600;
  font-size: 1rem;
  cursor: pointer;
  transition: all 0.3s;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 10px;
}

.reset-btn {
  background: #f7fafc;
  color: #4a5568;
  border: 2px solid #e2e8f0;
}

.reset-btn:hover {
  background: #edf2f7;
  border-color: #cbd5e0;
}

.apply-btn {
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  color: white;
}

.apply-btn:hover {
  transform: translateY(-2px);
  box-shadow: 0 8px 25px rgba(102, 126, 234, 0.4);
}

/* Filtres rapides */
.quick-filters {
  margin-top: 25px;
  padding-top: 25px;
  border-top: 1px solid #e2e8f0;
}

.quick-filters h4 {
  font-size: 1rem;
  color: #4a5568;
  margin-bottom: 15px;
  font-weight: 600;
}

.category-chips {
  display: flex;
  flex-wrap: wrap;
  gap: 10px;
}

.category-chip {
  padding: 10px 20px;
  background: #f7fafc;
  border: 2px solid #e2e8f0;
  border-radius: 50px;
  font-size: 0.9rem;
  color: #4a5568;
  cursor: pointer;
  transition: all 0.3s;
  display: flex;
  align-items: center;
  gap: 8px;
}

.category-chip:hover {
  background: #edf2f7;
  border-color: #cbd5e0;
  transform: translateY(-2px);
}

.chip-icon {
  font-size: 1.1rem;
}

/* Statistiques */
.stats-container {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
  gap: 20px;
  margin-top: 30px;
}

.stat-card {
  background: linear-gradient(135deg, #f7fafc 0%, #edf2f7 100%);
  border-radius: 15px;
  padding: 20px;
  display: flex;
  align-items: center;
  gap: 15px;
  border: 1px solid #e2e8f0;
}

.stat-icon {
  font-size: 2rem;
  opacity: 0.8;
}

.stat-content {
  flex: 1;
}

.stat-number {
  font-size: 1.8rem;
  font-weight: 800;
  color: #2d3748;
  line-height: 1;
}

.stat-label {
  font-size: 0.85rem;
  color: #718096;
  margin-top: 5px;
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

@keyframes spin {
  0% { transform: rotate(0deg); }
  100% { transform: rotate(360deg); }
}

.loading-text {
  font-size: 1.2rem;
  color: #4a5568;
  margin-bottom: 5px;
  font-weight: 600;
}

.loading-subtext {
  color: #a0aec0;
  font-size: 0.9rem;
}

/* Résultats */
.no-results-container {
  text-align: center;
  padding: 60px 20px;
  background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%);
  border-radius: 20px;
  border: 2px dashed #cbd5e0;
}

.no-results-illustration {
  margin-bottom: 30px;
}

.illustration {
  font-size: 5rem;
  opacity: 0.5;
  animation: bounce 2s infinite;
}

@keyframes bounce {
  0%, 100% { transform: translateY(0); }
  50% { transform: translateY(-10px); }
}

.no-results-content {
  max-width: 500px;
  margin: 0 auto;
}

.no-results-title {
  font-size: 1.8rem;
  color: #2d3748;
  margin-bottom: 15px;
  font-weight: 700;
}

.no-results-description {
  color: #718096;
  margin-bottom: 30px;
  line-height: 1.6;
}

.no-results-actions {
  display: flex;
  gap: 15px;
  justify-content: center;
}

.primary-btn,
.secondary-btn {
  padding: 14px 28px;
  border-radius: 12px;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.3s;
  display: flex;
  align-items: center;
  gap: 10px;
  border: none;
}

.primary-btn {
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  color: white;
}

.primary-btn:hover {
  transform: translateY(-2px);
  box-shadow: 0 8px 25px rgba(102, 126, 234, 0.4);
}

.secondary-btn {
  background: white;
  color: #4a5568;
  border: 2px solid #e2e8f0;
}

.secondary-btn:hover {
  background: #f7fafc;
  border-color: #cbd5e0;
}

.results-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 30px;
}

.results-title {
  font-size: 1.8rem;
  font-weight: 700;
  color: #2d3748;
}

.sort-options {
  display: flex;
  align-items: center;
  gap: 10px;
}

.sort-label {
  color: #718096;
  font-size: 0.9rem;
}

.sort-select {
  padding: 10px 15px;
  border: 2px solid #e2e8f0;
  border-radius: 10px;
  font-size: 0.9rem;
  color: #4a5568;
  cursor: pointer;
  background: white;
}

/* Grille des matériels */
.materiels-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
  gap: 30px;
  margin-bottom: 50px;
}

.materiel-card {
  background: white;
  border-radius: 20px;
  overflow: hidden;
  box-shadow: 0 10px 30px rgba(0, 0, 0, 0.08);
  transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
  cursor: pointer;
  border: 1px solid rgba(0, 0, 0, 0.05);
}

.materiel-card:hover {
  transform: translateY(-10px) scale(1.02);
  box-shadow: 0 20px 50px rgba(0, 0, 0, 0.15);
}

.materiel-image {
  position: relative;
  height: 220px;
  overflow: hidden;
}

.product-image {
  width: 100%;
  height: 100%;
  object-fit: cover;
  transition: transform 0.6s ease;
}

.materiel-card:hover .product-image {
  transform: scale(1.1);
}

/* Badges améliorés */
.card-badges {
  position: absolute;
  top: 15px;
  left: 15px;
  right: 15px;
  display: flex;
  flex-wrap: wrap;
  gap: 8px;
}

.stock-badge,
.new-badge,
.promo-badge {
  padding: 8px 15px;
  border-radius: 25px;
  font-size: 0.8rem;
  font-weight: 700;
  display: flex;
  align-items: center;
  gap: 6px;
  color: white;
  backdrop-filter: blur(10px);
  border: 1px solid rgba(255, 255, 255, 0.2);
}

.stock-badge.in-stock {
  background: linear-gradient(135deg, rgba(16, 185, 129, 0.9), rgba(16, 185, 129, 0.7));
}

.stock-badge.low-stock {
  background: linear-gradient(135deg, rgba(245, 158, 11, 0.9), rgba(245, 158, 11, 0.7));
}

.stock-badge.out-of-stock {
  background: linear-gradient(135deg, rgba(239, 68, 68, 0.9), rgba(239, 68, 68, 0.7));
}

.new-badge {
  background: linear-gradient(135deg, rgba(139, 92, 246, 0.9), rgba(139, 92, 246, 0.7));
}

.promo-badge {
  background: linear-gradient(135deg, rgba(236, 72, 153, 0.9), rgba(236, 72, 153, 0.7));
}

.badge-icon {
  font-size: 0.9rem;
}

/* Quick Actions */
.quick-actions {
  position: absolute;
  bottom: 15px;
  right: 15px;
  display: flex;
  gap: 8px;
  opacity: 0;
  transform: translateY(10px);
  transition: all 0.3s ease;
}

.materiel-card:hover .quick-actions {
  opacity: 1;
  transform: translateY(0);
}

.quick-cart-btn,
.quick-fav-btn {
  width: 40px;
  height: 40px;
  border-radius: 50%;
  border: none;
  display: flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
  transition: all 0.3s;
  background: rgba(255, 255, 255, 0.95);
  box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
}

.quick-cart-btn:hover {
  background: #667eea;
  color: white;
  transform: scale(1.1);
}

.quick-fav-btn:hover {
  background: #f56565;
  color: white;
  transform: scale(1.1);
}

.quick-fav-btn.favorited {
  background: #f56565;
  color: white;
}

.quick-icon {
  font-size: 1.2rem;
}

/* Informations matériel */
.materiel-info {
  padding: 25px;
}

.materiel-header {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  margin-bottom: 15px;
  gap: 10px;
}

.materiel-title {
  font-size: 1.3rem;
  font-weight: 700;
  color: #2d3748;
  margin: 0;
  line-height: 1.4;
  flex: 1;
}

.categorie-tag {
  background: #f7fafc;
  color: #667eea;
  padding: 6px 12px;
  border-radius: 20px;
  font-size: 0.8rem;
  font-weight: 600;
  display: flex;
  align-items: center;
  gap: 5px;
  white-space: nowrap;
}

.tag-icon {
  font-size: 0.9rem;
}

.materiel-description {
  color: #718096;
  line-height: 1.6;
  margin-bottom: 20px;
  font-size: 0.95rem;
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
}

.materiel-details {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 25px;
  padding-bottom: 20px;
  border-bottom: 1px solid #e2e8f0;
}

.price-section {
  display: flex;
  flex-direction: column;
  gap: 5px;
}

.price-wrapper {
  display: flex;
  align-items: baseline;
  gap: 5px;
}

.price {
  font-size: 1.8rem;
  font-weight: 800;
  color: #10b981;
  background: linear-gradient(135deg, #10b981, #34d399);
  -webkit-background-clip: text;
  -webkit-text-fill-color: transparent;
}

.price-period {
  font-size: 0.9rem;
  color: #a0aec0;
}

.price-note {
  font-size: 0.85rem;
  color: #f59e0b;
  font-weight: 500;
}

.dimensions {
  font-size: 0.9rem;
  color: #718096;
  display: flex;
  align-items: center;
  gap: 5px;
  background: #f7fafc;
  padding: 8px 12px;
  border-radius: 10px;
}

.dim-icon {
  opacity: 0.7;
}

/* Actions */
.materiel-actions {
  display: flex;
  gap: 12px;
}

.add-to-cart-btn,
.details-btn {
  flex: 1;
  padding: 14px;
  border-radius: 12px;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.3s;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 8px;
  text-decoration: none;
  font-size: 0.95rem;
  border: none;
}

.add-to-cart-btn {
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  color: white;
}

.add-to-cart-btn:hover:not(.disabled) {
  transform: translateY(-2px);
  box-shadow: 0 8px 20px rgba(102, 126, 234, 0.4);
}

.add-to-cart-btn.disabled {
  background: #cbd5e0;
  cursor: not-allowed;
  opacity: 0.7;
}

.details-btn {
  background: #f7fafc;
  color: #4a5568;
  border: 2px solid #e2e8f0;
}

.details-btn:hover {
  background: #edf2f7;
  border-color: #cbd5e0;
  transform: translateY(-2px);
}

.cart-icon,
.details-icon {
  font-size: 1.1rem;
}

/* Pagination */
.pagination-container {
  margin-top: 50px;
}

.pagination {
  display: flex;
  justify-content: center;
  align-items: center;
  gap: 20px;
}

.pagination-btn {
  padding: 12px 25px;
  background: white;
  color: #4a5568;
  border: 2px solid #e2e8f0;
  border-radius: 12px;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.3s;
  display: flex;
  align-items: center;
  gap: 8px;
  min-width: 130px;
}

.pagination-btn:hover:not(:disabled) {
  background: #f7fafc;
  border-color: #cbd5e0;
  transform: translateY(-2px);
}

.pagination-btn:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}

.prev-btn {
  justify-content: flex-start;
}

.next-btn {
  justify-content: flex-end;
}

.page-numbers {
  display: flex;
  gap: 8px;
  align-items: center;
}

.page-btn {
  width: 45px;
  height: 45px;
  display: flex;
  align-items: center;
  justify-content: center;
  background: #f7fafc;
  color: #4a5568;
  border: 2px solid #e2e8f0;
  border-radius: 12px;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.3s;
}

.page-btn:hover:not(.active) {
  background: #edf2f7;
  border-color: #cbd5e0;
}

.page-btn.active {
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  color: white;
  border-color: #667eea;
}

.ellipsis {
  padding: 0 10px;
  color: #a0aec0;
}

/* CTA Section */
.cta-section {
  background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%);
  border-radius: 20px;
  padding: 50px;
  margin-top: 60px;
  border: 1px solid #e2e8f0;
}

.cta-content {
  display: grid;
  grid-template-columns: 1fr auto;
  gap: 40px;
  align-items: center;
}

.cta-title {
  font-size: 2rem;
  font-weight: 800;
  color: #2d3748;
  margin-bottom: 15px;
}

.cta-description {
  color: #718096;
  font-size: 1.1rem;
  line-height: 1.6;
  margin-bottom: 25px;
}

.cta-features {
  display: flex;
  gap: 25px;
  margin-top: 20px;
}

.feature {
  display: flex;
  align-items: center;
  gap: 8px;
  color: #4a5568;
  font-weight: 500;
}

.feature-icon {
  font-size: 1.2rem;
}

.cta-actions {
  display: flex;
  flex-direction: column;
  gap: 15px;
  min-width: 200px;
}

.contact-btn,
.help-btn {
  padding: 16px 24px;
  border-radius: 12px;
  font-weight: 600;
  font-size: 1rem;
  cursor: pointer;
  transition: all 0.3s;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 10px;
  border: none;
}

.contact-btn {
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  color: white;
}

.contact-btn:hover {
  transform: translateY(-3px);
  box-shadow: 0 12px 30px rgba(102, 126, 234, 0.4);
}

.help-btn {
  background: white;
  color: #4a5568;
  border: 2px solid #e2e8f0;
}

.help-btn:hover {
  background: #f7fafc;
  border-color: #cbd5e0;
  transform: translateY(-2px);
}

/* Newsletter */
.newsletter-section {
  background: linear-gradient(135deg, #2d3748 0%, #1a202c 100%);
  border-radius: 20px;
  padding: 40px;
  margin-top: 40px;
  color: white;
}

.newsletter-content {
  display: grid;
  grid-template-columns: auto 1fr auto;
  gap: 30px;
  align-items: center;
}

.newsletter-icon {
  font-size: 3rem;
}

.newsletter-text h3 {
  font-size: 1.5rem;
  font-weight: 700;
  margin-bottom: 8px;
}

.newsletter-text p {
  opacity: 0.8;
  font-size: 0.95rem;
}

.newsletter-form {
  display: flex;
  gap: 10px;
}

.newsletter-input {
  padding: 14px 20px;
  border: 2px solid #4a5568;
  border-radius: 12px;
  background: rgba(255, 255, 255, 0.1);
  color: white;
  font-size: 1rem;
  min-width: 250px;
}

.newsletter-input::placeholder {
  color: rgba(255, 255, 255, 0.6);
}

.newsletter-input:focus {
  outline: none;
  border-color: #667eea;
}

.newsletter-btn {
  padding: 14px 28px;
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  color: white;
  border: none;
  border-radius: 12px;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.3s;
}

.newsletter-btn:hover {
  transform: translateY(-2px);
  box-shadow: 0 8px 25px rgba(102, 126, 234, 0.4);
}

/* Responsive */
@media (max-width: 1200px) {
  .materiels-grid {
    grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
  }
}

@media (max-width: 992px) {
  .hero-title {
    font-size: 2.8rem;
  }
  
  .cta-content {
    grid-template-columns: 1fr;
    gap: 30px;
  }
  
  .newsletter-content {
    grid-template-columns: 1fr;
    text-align: center;
    gap: 20px;
  }
  
  .newsletter-form {
    justify-content: center;
  }
}

@media (max-width: 768px) {
  .catalogue-container {
    padding: 15px;
  }
  
  .hero-section {
    padding: 40px 20px;
  }
  
  .hero-title {
    font-size: 2.2rem;
  }
  
  .hero-stats {
    flex-direction: column;
    gap: 20px;
  }
  
  .filters-grid {
    grid-template-columns: 1fr;
  }
  
  .search-group {
    grid-column: span 1;
  }
  
  .materiels-grid {
    grid-template-columns: 1fr;
    gap: 20px;
  }
  
  .pagination {
    flex-direction: column;
    gap: 15px;
  }
  
  .page-numbers {
    order: 2;
  }
  
  .pagination-btn {
    min-width: 100%;
  }
  
  .no-results-actions {
    flex-direction: column;
  }
}

@media (max-width: 480px) {
  .hero-title {
    font-size: 1.8rem;
  }
  
  .filters-card {
    padding: 20px;
  }
  
  .cta-section {
    padding: 30px 20px;
  }
  
  .newsletter-section {
    padding: 30px 20px;
  }
  
  .newsletter-form {
    flex-direction: column;
  }
  
  .newsletter-input {
    min-width: 100%;
  }
}
</style>