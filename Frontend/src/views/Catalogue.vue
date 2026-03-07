<template>
  <div class="catalogue-container">
    <!-- En-tête -->
    <div class="catalogue-header">
      <h1>Catalogue des matériels</h1>
      <p>Trouvez le matériel idéal pour vos événements</p>
    </div>

    <!-- Filtres et recherche -->
    <div class="filters-section">
      <div class="filters-row">
        <div class="filter-group">
          <label for="categorie">Catégorie</label>
          <select 
            id="categorie" 
            v-model="selectedCategorie" 
            @change="filterByCategorie" 
            class="filter-select"
          >
            <option value="">Toutes les catégories</option>
            <option 
              v-for="cat in filteredCategories" 
              :key="cat.id" 
              :value="cat.id"
            >
              {{ cat.nom }}
            </option>
          </select>
        </div>

        <div class="filter-group">
          <label for="prix-min">Prix min (€ HT/jour)</label>
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

        <div class="filter-group">
          <label for="prix-max">Prix max (€ HT/jour)</label>
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

        <div class="filter-group search-group">
          <label for="search">Recherche</label>
          <div class="search-wrapper">
            <input 
              id="search" 
              type="text" 
              v-model="searchQuery" 
              placeholder="Rechercher un matériel..." 
              @input="searchMateriels" 
              class="search-input"
            >
            <span class="search-icon">🔍</span>
          </div>
        </div>

        <button @click="resetFilters" class="reset-btn">Réinitialiser</button>
      </div>

      <!-- Statistiques -->
      <div class="stats">
        <span class="stat-item">
          <span class="stat-number">{{ pagination.total || 0 }}</span> matériels
        </span>
        <span v-if="selectedCategorieName" class="stat-item">
          Catégorie : <span class="stat-number">{{ selectedCategorieName }}</span>
        </span>
        <span class="stat-item">
          Page <span class="stat-number">{{ pagination.current_page }}</span> sur {{ pagination.last_page }}
        </span>
      </div>
    </div>

    <!-- Loading -->
    <div v-if="loading" class="loading-indicator">
      <div class="spinner"></div>
      <p>Chargement des matériels...</p>
    </div>

    <!-- Liste des matériels -->
    <div v-else class="materiels-section">
      <!-- Message si aucun résultat -->
      <div v-if="filteredMateriels.length === 0" class="no-results">
        <div class="no-results-icon">😕</div>
        <h3>Aucun matériel trouvé</h3>
        <p>Essayez de modifier vos critères de recherche</p>
        <button @click="resetFilters" class="primary-btn">Voir tous les matériels</button>
      </div>

      <!-- Grille des matériels -->
      <div v-else class="materiels-grid">
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
              @error="(e) => handleImageError(e, materiel)"
            >
            <!-- Badges -->
            <div class="card-badges">
              <div class="stock-badge" :class="{
                'in-stock': materiel.stock_disponible > 10,
                'low-stock': materiel.stock_disponible <= 10 && materiel.stock_disponible > 0,
                'out-of-stock': materiel.stock_disponible === 0
              }">
                {{ materiel.stock_disponible > 0 ? `${materiel.stock_disponible} dispo` : 'Rupture' }}
              </div>
              <div v-if="materiel.is_new" class="new-badge">Nouveau</div>
            </div>
          </div>
          
          <div class="materiel-info">
            <div class="materiel-header">
              <h3 class="materiel-title">{{ materiel.nom || 'Sans nom' }}</h3>
              <span class="categorie-tag">{{ materiel.categorie?.nom || 'Non catégorisé' }}</span>
            </div>
            
            <p class="materiel-description">{{ truncateDescription(materiel.description) }}</p>
            
            <div class="materiel-details">
              <div class="price-section">
                <!-- Prix HT -->
                <div class="prices">
                  <span class="price">{{ formatPrice(getPrixHT(materiel)) }}</span>
                  <span class="price-period"> HT/jour</span>
                </div>
                <!-- Prix TTC -->
                <div class="price-ttc">soit {{ formatPrice(getPrixTTC(materiel)) }} TTC/jour</div>
              </div>
              <span v-if="materiel.dimensions" class="dimensions">📏 {{ materiel.dimensions }}</span>
            </div>

            <div class="materiel-actions">
              <button 
                @click.stop="addToCart(materiel)" 
                :disabled="materiel.stock_disponible === 0 || addingToCart === materiel.id"
                class="add-to-cart-btn" 
                :class="{ 
                  disabled: materiel.stock_disponible === 0,
                  loading: addingToCart === materiel.id
                }"
              >
                <span v-if="addingToCart === materiel.id" class="spinner-small"></span>
                <span v-else>🛒</span>
                {{ getButtonText(materiel) }}
              </button>
              <router-link 
                :to="`/materiels/${materiel.id}`" 
                class="details-btn" 
                @click.stop
              >
                Détails
              </router-link>
            </div>
          </div>
        </div>
      </div>

      <!-- Pagination -->
      <div v-if="pagination.last_page > 1" class="pagination">
        <button 
          @click="prevPage" 
          :disabled="pagination.current_page === 1" 
          class="pagination-btn"
        >
          ← Précédent
        </button>
        
        <div class="page-numbers">
          <button 
            v-for="page in visiblePages" 
            :key="page"
            @click="goToPage(page)"
            class="page-btn" 
            :class="{ active: page === pagination.current_page }"
          >
            {{ page }}
          </button>
          <span v-if="showEllipsis" class="ellipsis">...</span>
        </div>
        
        <button 
          @click="nextPage" 
          :disabled="pagination.current_page === pagination.last_page" 
          class="pagination-btn"
        >
          Suivant →
        </button>
      </div>
    </div>

    <!-- CTA -->
    <div class="cta-section">
      <h2>Besoin d'aide pour choisir ?</h2>
      <p>Notre équipe est à votre disposition pour vous conseiller</p>
      <button @click="contactSupport" class="contact-btn">📞 Nous contacter</button>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, computed } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
import api from '@/services/axios'

const router = useRouter()
const auth = useAuthStore()
const BASE_URL = import.meta.env.VITE_API_BASE_URL || 'http://127.0.0.1:8000'

// États
const categories = ref([])
const materiels = ref([])
const selectedCategorie = ref('')
const searchQuery = ref('')
const prixMin = ref('')
const prixMax = ref('')
const loading = ref(false)
const addingToCart = ref(null)

const pagination = ref({
  current_page: 1,
  last_page: 1,
  total: 0,
  per_page: 12
})

// Computed
const filteredCategories = computed(() => 
  categories.value.filter(c => c?.id && c?.nom)
)

const filteredMateriels = computed(() => 
  materiels.value.filter(m => m?.id)
)

const selectedCategorieName = computed(() =>
  categories.value.find(c => c?.id == selectedCategorie.value)?.nom || ''
)

const visiblePages = computed(() => {
  const cur = pagination.value.current_page
  const last = pagination.value.last_page
  const max = 5
  
  let start = Math.max(1, cur - Math.floor(max / 2))
  let end = Math.min(last, start + max - 1)
  
  if (end - start + 1 < max) {
    start = Math.max(1, end - max + 1)
  }
  
  const pages = []
  for (let i = start; i <= end; i++) pages.push(i)
  return pages
})

const showEllipsis = computed(() => 
  pagination.value.last_page > visiblePages.value.length
)

// ==================== GESTION DES PRIX ====================

const getPrixHT = (m) => {
  if (!m) return 0
  return parseFloat(m?.prix_journalier_ht ?? m?.prix_journalier ?? 0) || 0
}

const getPrixTTC = (m) => {
  if (!m) return 0
  const ht = getPrixHT(m)
  const tva = parseFloat(m?.taux_tva ?? 20) / 100
  return ht * (1 + tva)
}

const formatPrice = (v) => {
  if (v == null || isNaN(v)) return '0,00 €'
  return new Intl.NumberFormat('fr-FR', { 
    style: 'currency', 
    currency: 'EUR', 
    minimumFractionDigits: 2 
  }).format(v)
}

// ==================== GESTION DES IMAGES ====================

const getInitialsImage = (nom = '') => {
  const initial = nom.charAt(0).toUpperCase() || '?'
  let hash = 0
  for (let i = 0; i < nom.length; i++) {
    hash = nom.charCodeAt(i) + ((hash << 5) - hash)
  }
  const color = `hsl(${Math.abs(hash % 360)}, 70%, 80%)`
  
  return `data:image/svg+xml;utf8,${encodeURIComponent(`
    <svg xmlns='http://www.w3.org/2000/svg' width='200' height='200'>
      <rect width='200' height='200' fill='${color}'/>
      <text x='100' y='130' font-size='80' text-anchor='middle' fill='#333' font-family='Arial'>${initial}</text>
    </svg>
  `)}`
}

const buildUrl = (path) => {
  if (!path) return null
  const p = String(path).trim()
  if (p.startsWith('http')) return p
  if (p.startsWith('/')) return `${BASE_URL}${p}`
  return `${BASE_URL}/storage/${p.replace(/^storage\//, '')}`
}

const getPhotoUrl = (m) => {
  if (!m) return getInitialsImage()
  
  if (m.main_photo) return m.main_photo
  
  if (Array.isArray(m.photos) && m.photos.length) {
    for (const p of m.photos) {
      const url = buildUrl(p?.url_photo || p?.chemin_fichier || (typeof p === 'string' ? p : null))
      if (url) return url
    }
  }
  
  if (m.photo) return buildUrl(m.photo)
  
  return getInitialsImage(m.nom)
}

const handleImageError = (e, m) => {
  if (e.target.src.startsWith('data:')) return
  e.target.onerror = null
  e.target.src = getInitialsImage(m?.nom)
}

// ==================== CHARGEMENT DES DONNÉES ====================

const loadCategories = async () => {
  try {
    const res = await api.get('/categories-materiel')
    const raw = res.data?.data ?? res.data
    categories.value = (Array.isArray(raw) ? raw : []).filter(c => c?.id && c?.nom)
  } catch (e) { 
    console.error('❌ Erreur chargement catégories:', e) 
    categories.value = [] 
  }
}

const loadMateriels = async (page = 1) => {
  loading.value = true
  try {
    let url = `/materiels?page=${page}&per_page=${pagination.value.per_page}`
    if (selectedCategorie.value) url += `&categorie_id=${selectedCategorie.value}`
    if (prixMin.value !== '') url += `&prix_min=${prixMin.value}`
    if (prixMax.value !== '') url += `&prix_max=${prixMax.value}`

    const res = await api.get(url)
    const data = res.data

    let items = []
    let pag = { current_page: 1, last_page: 1, total: 0, per_page: 12 }

    if (data?.success && Array.isArray(data.data)) {
      items = data.data
      pag = { ...pag, ...(data.pagination ?? {}) }
    } else if (Array.isArray(data?.data)) {
      items = data.data
      pag = { 
        current_page: data.current_page ?? 1, 
        last_page: data.last_page ?? 1, 
        total: data.total ?? items.length, 
        per_page: data.per_page ?? 12 
      }
    } else if (Array.isArray(data)) {
      items = data
      pag.total = items.length
    }

    materiels.value = items.filter(m => m?.id)
    pagination.value = pag
    
    console.log('✅ Matériels chargés:', materiels.value.length)
  } catch (e) {
    console.error('❌ Erreur chargement matériels:', e)
    materiels.value = []
  } finally {
    loading.value = false
  }
}

// ==================== FILTRES ====================

const filterByCategorie = () => { 
  pagination.value.current_page = 1 
  loadMateriels() 
}

const filterByPrice = () => {
  clearTimeout(window._pfT)
  window._pfT = setTimeout(() => { 
    pagination.value.current_page = 1 
    loadMateriels() 
  }, 500)
}

const resetFilters = () => {
  selectedCategorie.value = ''
  searchQuery.value = ''
  prixMin.value = ''
  prixMax.value = ''
  pagination.value.current_page = 1
  loadMateriels()
}

const searchMateriels = async () => {
  clearTimeout(window._stT)
  if (!searchQuery.value) { 
    loadMateriels() 
    return 
  }
  if (searchQuery.value.length < 2) return
  
  window._stT = setTimeout(async () => {
    try {
      const res = await api.get(`/materiels/search?q=${encodeURIComponent(searchQuery.value)}`)
      const raw = res.data?.data ?? res.data
      materiels.value = (Array.isArray(raw) ? raw : []).filter(m => m?.id)
      pagination.value = { 
        current_page: 1, 
        last_page: 1, 
        total: materiels.value.length, 
        per_page: 12 
      }
    } catch (e) { 
      console.error('❌ Erreur recherche:', e) 
    }
  }, 400)
}

// ==================== PAGINATION ====================

const nextPage = () => { 
  if (pagination.value.current_page < pagination.value.last_page) { 
    pagination.value.current_page++
    loadMateriels(pagination.value.current_page)
    window.scrollTo(0, 0)
  } 
}

const prevPage = () => { 
  if (pagination.value.current_page > 1) { 
    pagination.value.current_page--
    loadMateriels(pagination.value.current_page)
    window.scrollTo(0, 0)
  } 
}

const goToPage = (p) => { 
  if (p !== pagination.value.current_page) { 
    pagination.value.current_page = p
    loadMateriels(p)
    window.scrollTo(0, 0)
  } 
}

// ==================== ACTIONS ====================

const truncateDescription = (d) => {
  if (!d) return 'Aucune description'
  return d.length > 80 ? d.slice(0, 80) + '...' : d
}

const goToDetail = (id) => {
  if (id) router.push(`/materiels/${id}`)
}

// ==================== AJOUT AU PANIER CORRIGÉ ====================

const getButtonText = (materiel) => {
  if (materiel.stock_disponible === 0) return 'Indisponible'
  if (addingToCart.value === materiel.id) return 'Ajout...'
  return 'Ajouter'
}

const addToCart = async (materiel) => {
  // Vérifications préalables
  if (!materiel?.stock_disponible) {
    alert('Ce matériel n\'est pas disponible')
    return
  }
  
  // Vérifier si l'utilisateur est connecté
  if (!auth.isAuthenticated) {
    alert('Veuillez vous connecter pour ajouter des articles au panier')
    router.push('/login')
    return
  }
  
  // Empêcher les doubles clics
  if (addingToCart.value === materiel.id) return
  
  try {
    addingToCart.value = materiel.id
    console.log('🛒 Ajout au panier:', {
      materiel_id: materiel.id,
      nom: materiel.nom,
      quantite: 1
    })
    
    // ✅ CORRECTION : Utiliser POST /api/panier au lieu de /api/panier/ajouter
    const response = await api.post('/panier', {
      materiel_id: materiel.id,
      quantite: 1
    })
    
    console.log('📦 Réponse:', response.data)
    
    if (response.data.success) {
      // Mettre à jour le compteur du panier dans App.vue
      window.dispatchEvent(new CustomEvent('cartUpdated'))
      
      // Feedback utilisateur
      alert(`✅ ${materiel.nom} ajouté au panier !`)
      
      // Animation sur le bouton
      const btn = document.activeElement
      btn?.classList.add('added')
      setTimeout(() => btn?.classList.remove('added'), 500)
    }
  } catch (error) {
    console.error('❌ Erreur ajout panier:', {
      status: error.response?.status,
      data: error.response?.data,
      message: error.message
    })
    
    // Gestion des erreurs
    if (error.response?.status === 401) {
      alert('Votre session a expiré. Veuillez vous reconnecter.')
      router.push('/login')
    } else if (error.response?.status === 422) {
      const msg = error.response.data?.message || 'Données invalides'
      alert(msg)
    } else if (error.response?.data?.message) {
      alert(error.response.data.message)
    } else if (error.response?.data?.stock_disponible !== undefined) {
      alert(`Stock insuffisant. Disponible: ${error.response.data.stock_disponible}`)
    } else {
      alert('Erreur lors de l\'ajout au panier. Veuillez réessayer.')
    }
  } finally {
    addingToCart.value = null
  }
}

const contactSupport = () => {
  router.push('/contact')
}

// ==================== INITIALISATION ====================

onMounted(() => {
  console.log('🚀 Initialisation Catalogue')
  console.log('🔐 Auth status:', auth.isAuthenticated)
  loadCategories()
  loadMateriels()
})
</script>

<style scoped>
/* Styles identiques à ceux fournis précédemment */
.catalogue-container {
  max-width: 1400px;
  margin: 0 auto;
  padding: 20px;
  min-height: 100vh;
}

.catalogue-header {
  text-align: center;
  margin-bottom: 40px;
  padding: 30px 20px;
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  border-radius: 15px;
  color: white;
}

.catalogue-header h1 {
  font-size: 2.5rem;
  margin-bottom: 10px;
  font-weight: 700;
}

.catalogue-header p {
  font-size: 1.1rem;
  opacity: 0.9;
  max-width: 600px;
  margin: 0 auto;
}

.filters-section {
  background: white;
  border-radius: 12px;
  padding: 25px;
  margin-bottom: 30px;
  box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
}

.filters-row {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
  gap: 20px;
  margin-bottom: 20px;
  align-items: end;
}

.filter-group {
  display: flex;
  flex-direction: column;
  gap: 8px;
}

.filter-group label {
  font-weight: 600;
  color: #374151;
  font-size: 0.9rem;
}

.filter-select,
.filter-input {
  padding: 12px 15px;
  border: 2px solid #e5e7eb;
  border-radius: 8px;
  font-size: 1rem;
  transition: border-color 0.3s;
  width: 100%;
}

.filter-select:focus,
.filter-input:focus {
  outline: none;
  border-color: #667eea;
}

.search-group {
  grid-column: span 2;
}

.search-wrapper {
  position: relative;
}

.search-input {
  padding-right: 45px;
  width: 100%;
}

.search-icon {
  position: absolute;
  right: 15px;
  top: 50%;
  transform: translateY(-50%);
  color: #9ca3af;
  font-size: 1.1rem;
}

.reset-btn {
  padding: 12px 24px;
  background: #f3f4f6;
  color: #374151;
  border: 2px solid #e5e7eb;
  border-radius: 8px;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.3s;
  white-space: nowrap;
}

.reset-btn:hover {
  background: #e5e7eb;
}

.stats {
  display: flex;
  gap: 30px;
  padding-top: 20px;
  border-top: 1px solid #f3f4f6;
  font-size: 0.9rem;
  color: #6b7280;
}

.stat-item {
  display: flex;
  align-items: center;
  gap: 5px;
}

.stat-number {
  font-weight: 700;
  color: #374151;
}

.loading-indicator {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  padding: 60px 20px;
}

.spinner {
  width: 50px;
  height: 50px;
  border: 4px solid #f3f4f6;
  border-top: 4px solid #667eea;
  border-radius: 50%;
  animation: spin 1s linear infinite;
  margin-bottom: 20px;
}

.spinner-small {
  display: inline-block;
  width: 16px;
  height: 16px;
  border: 2px solid rgba(255,255,255,0.3);
  border-top-color: white;
  border-radius: 50%;
  animation: spin 0.8s linear infinite;
  margin-right: 5px;
}

@keyframes spin {
  0% { transform: rotate(0deg); }
  100% { transform: rotate(360deg); }
}

.no-results {
  text-align: center;
  padding: 80px 20px;
}

.no-results-icon {
  font-size: 4rem;
  margin-bottom: 20px;
  opacity: 0.5;
}

.no-results h3 {
  font-size: 1.5rem;
  color: #374151;
  margin-bottom: 10px;
}

.no-results p {
  color: #6b7280;
  margin-bottom: 30px;
}

.primary-btn {
  padding: 14px 28px;
  background: #667eea;
  color: white;
  border: none;
  border-radius: 8px;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.3s;
}

.primary-btn:hover {
  background: #5a6fd8;
  transform: translateY(-2px);
  box-shadow: 0 4px 12px rgba(102, 126, 234, 0.3);
}

.materiels-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
  gap: 30px;
  margin-bottom: 50px;
}

.materiel-card {
  background: white;
  border-radius: 12px;
  overflow: hidden;
  box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
  transition: all 0.3s ease;
  cursor: pointer;
  border: 1px solid #f3f4f6;
}

.materiel-card:hover {
  transform: translateY(-8px);
  box-shadow: 0 12px 30px rgba(0, 0, 0, 0.15);
}

.materiel-image {
  position: relative;
  height: 200px;
  overflow: hidden;
}

.materiel-image img {
  width: 100%;
  height: 100%;
  object-fit: cover;
  transition: transform 0.5s;
}

.materiel-card:hover .materiel-image img {
  transform: scale(1.05);
}

.card-badges {
  position: absolute;
  top: 15px;
  left: 15px;
  right: 15px;
  display: flex;
  justify-content: space-between;
}

.stock-badge {
  padding: 6px 12px;
  border-radius: 20px;
  font-size: 0.75rem;
  font-weight: 700;
  color: white;
  text-transform: uppercase;
  letter-spacing: 0.5px;
}

.stock-badge.in-stock {
  background: #10b981;
}

.stock-badge.low-stock {
  background: #f59e0b;
}

.stock-badge.out-of-stock {
  background: #ef4444;
}

.new-badge {
  padding: 6px 12px;
  background: #8b5cf6;
  color: white;
  border-radius: 20px;
  font-size: 0.75rem;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: 0.5px;
}

.materiel-info {
  padding: 20px;
}

.materiel-header {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  margin-bottom: 10px;
}

.materiel-title {
  font-size: 1.2rem;
  font-weight: 700;
  color: #1f2937;
  margin: 0;
  line-height: 1.3;
  flex: 1;
}

.categorie-tag {
  background: #f3f4f6;
  color: #6b7280;
  padding: 4px 10px;
  border-radius: 20px;
  font-size: 0.75rem;
  font-weight: 600;
  margin-left: 10px;
  white-space: nowrap;
}

.materiel-description {
  color: #6b7280;
  line-height: 1.6;
  margin-bottom: 15px;
  font-size: 0.9rem;
}

.materiel-details {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 20px;
  padding-bottom: 15px;
  border-bottom: 1px solid #f3f4f6;
}

.price-section {
  display: flex;
  flex-direction: column;
  gap: 5px;
}

.prices {
  display: flex;
  align-items: baseline;
  gap: 5px;
}

.price {
  font-size: 1.5rem;
  font-weight: 700;
  color: #10b981;
}

.price-period {
  font-size: 0.85rem;
  color: #9ca3af;
}

.price-ttc {
  font-size: 0.8rem;
  color: #6b7280;
}

.dimensions {
  font-size: 0.85rem;
  color: #6b7280;
  display: flex;
  align-items: center;
  gap: 5px;
}

.materiel-actions {
  display: flex;
  gap: 10px;
}

.add-to-cart-btn,
.details-btn {
  flex: 1;
  padding: 12px;
  border: none;
  border-radius: 8px;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.3s;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 8px;
  text-decoration: none;
  font-size: 0.9rem;
}

.add-to-cart-btn {
  background: #3b82f6;
  color: white;
}

.add-to-cart-btn:hover:not(.disabled):not(.loading) {
  background: #2563eb;
  transform: translateY(-2px);
}

.add-to-cart-btn.disabled {
  background: #9ca3af;
  cursor: not-allowed;
  transform: none;
}

.add-to-cart-btn.loading {
  background: #93c5fd;
  cursor: wait;
}

.add-to-cart-btn.added {
  background: #10b981;
}

.details-btn {
  background: #f3f4f6;
  color: #374151;
  border: none;
  text-align: center;
}

.details-btn:hover {
  background: #e5e7eb;
  transform: translateY(-2px);
}

.pagination {
  display: flex;
  justify-content: center;
  align-items: center;
  gap: 15px;
  margin-top: 40px;
  padding: 20px 0;
}

.pagination-btn {
  padding: 10px 20px;
  background: #f3f4f6;
  color: #374151;
  border: none;
  border-radius: 8px;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.3s;
  min-width: 120px;
}

.pagination-btn:hover:not(:disabled) {
  background: #e5e7eb;
}

.pagination-btn:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}

.page-numbers {
  display: flex;
  gap: 5px;
  align-items: center;
}

.page-btn {
  width: 40px;
  height: 40px;
  display: flex;
  align-items: center;
  justify-content: center;
  background: #f3f4f6;
  color: #374151;
  border: none;
  border-radius: 8px;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.3s;
}

.page-btn:hover:not(.active) {
  background: #e5e7eb;
}

.page-btn.active {
  background: #667eea;
  color: white;
}

.ellipsis {
  padding: 0 10px;
  color: #9ca3af;
}

.cta-section {
  text-align: center;
  padding: 60px 20px;
  background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%);
  border-radius: 15px;
  margin-top: 40px;
}

.cta-section h2 {
  font-size: 2rem;
  color: #1f2937;
  margin-bottom: 15px;
}

.cta-section p {
  color: #6b7280;
  font-size: 1.1rem;
  margin-bottom: 30px;
  max-width: 600px;
  margin-left: auto;
  margin-right: auto;
}

.contact-btn {
  padding: 16px 32px;
  background: #10b981;
  color: white;
  border: none;
  border-radius: 10px;
  font-size: 1.1rem;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.3s;
  display: inline-flex;
  align-items: center;
  gap: 10px;
}

.contact-btn:hover {
  background: #0da271;
  transform: translateY(-3px);
  box-shadow: 0 8px 20px rgba(16, 185, 129, 0.3);
}

@media (max-width: 768px) {
  .catalogue-header h1 {
    font-size: 2rem;
  }
  
  .filters-row {
    grid-template-columns: 1fr;
  }
  
  .search-group {
    grid-column: span 1;
  }
  
  .materiels-grid {
    grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
    gap: 20px;
  }
  
  .pagination {
    flex-direction: column;
    gap: 10px;
  }
  
  .page-numbers {
    order: 2;
  }
  
  .pagination-btn {
    min-width: 100px;
  }
}
</style>