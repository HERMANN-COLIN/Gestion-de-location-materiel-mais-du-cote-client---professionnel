<template>
  <div v-if="loading" class="loading-container">
    <div class="loading-spinner"></div>
    <p>Chargement du matériel...</p>
  </div>

  <div v-else-if="error" class="error-container">
    <div class="error-icon">⚠️</div>
    <h2>Matériel non trouvé</h2>
    <p>{{ error }}</p>
    <router-link to="/catalogue" class="back-link">Retour au catalogue</router-link>
  </div>

  <div v-else-if="materiel" class="materiel-detail-container">
    <nav class="breadcrumb">
      <router-link to="/catalogue">Catalogue</router-link>
      <span> › </span>
      <router-link :to="`/catalogue?categorie=${materiel.categorie?.id}`">{{ materiel.categorie?.nom }}</router-link>
      <span> › </span>
      <span class="current">{{ materiel.nom }}</span>
    </nav>

    <div class="materiel-content">
      <!-- Galerie -->
      <div class="photo-gallery">
        <div class="main-photo">
          <img :src="currentPhoto" :alt="materiel.nom" @error="handleMainPhotoError" @click="openLightbox">
          <div class="stock-status" :class="materiel.stock_disponible > 0 ? 'in-stock' : 'out-of-stock'">
            {{ materiel.stock_disponible > 0 ? `${materiel.stock_disponible} disponible(s)` : 'Rupture de stock' }}
          </div>
        </div>
        <div class="photo-thumbnails" v-if="photos.length > 1">
          <div v-for="(photo, i) in photos" :key="i" class="thumbnail"
            :class="{ active: currentPhotoIndex === i }" @click="currentPhotoIndex = i">
            <img :src="photo" :alt="`Vue ${i + 1}`" @error="(e) => { e.target.src = getInitialsImage(materiel.nom) }">
          </div>
        </div>
      </div>

      <!-- Informations -->
      <div class="materiel-info">
        <h1 class="materiel-title">{{ materiel.nom }}</h1>
        <div class="categorie-badge">{{ materiel.categorie?.nom }}</div>

        <!-- ✅ Section prix : lit prix_journalier_ht (champ BDD réel) -->
        <div class="price-section">
          <div class="price-ht">
            <span class="price-value">{{ formatPrice(prixHT) }}</span>
            <span class="price-label"> / jour HT</span>
          </div>
          <div class="price-ttc">
            soit <strong>{{ formatPrice(prixTTC) }}</strong> TTC/jour
            <span class="tva-info">(TVA {{ taux_tva }}%)</span>
          </div>
        </div>

        <div class="specifications">
          <h3>Caractéristiques</h3>
          <div class="spec-grid">
            <div class="spec-item" v-if="materiel.dimensions">
              <span class="spec-icon">📏</span>
              <div class="spec-details">
                <div class="spec-label">Dimensions</div>
                <div class="spec-value">{{ materiel.dimensions }}</div>
              </div>
            </div>
            <div class="spec-item">
              <span class="spec-icon">📦</span>
              <div class="spec-details">
                <div class="spec-label">Stock disponible</div>
                <div class="spec-value" :class="{ 'low-stock': materiel.stock_disponible < 10 }">
                  {{ materiel.stock_disponible }} unité(s)
                </div>
              </div>
            </div>
            <div class="spec-item" v-if="materiel.stock_total">
              <span class="spec-icon">🏭</span>
              <div class="spec-details">
                <div class="spec-label">Stock total</div>
                <div class="spec-value">{{ materiel.stock_total }} unité(s)</div>
              </div>
            </div>
          </div>
        </div>

        <div class="description-section" v-if="materiel.description">
          <h3>Description</h3>
          <p class="description-text">{{ materiel.description }}</p>
        </div>

        <!-- Réservation -->
        <div class="reservation-section">
          <h3>Réserver ce matériel</h3>
          <div class="quantity-selector">
            <label>Quantité :</label>
            <div class="quantity-controls">
              <button @click="decrementQuantity" :disabled="quantity <= 1" class="quantity-btn">−</button>
              <input type="number" v-model.number="quantity" min="1" :max="materiel.stock_disponible" class="quantity-input">
              <button @click="incrementQuantity" :disabled="quantity >= materiel.stock_disponible" class="quantity-btn">+</button>
            </div>
            <div class="quantity-available">Maximum : {{ materiel.stock_disponible }} unités</div>
          </div>

          <!-- ✅ Récapitulatif avec vrais prix HT + TTC -->
          <div class="reservation-summary">
            <div class="summary-row">
              <span>{{ quantity }} × {{ formatPrice(prixHT) }} HT</span>
              <span>{{ formatPrice(prixHT * quantity) }} HT</span>
            </div>
            <div class="summary-row">
              <span>TVA ({{ taux_tva }}%)</span>
              <span>{{ formatPrice((prixTTC - prixHT) * quantity) }}</span>
            </div>
            <div class="summary-row total">
              <span>Total / jour TTC</span>
              <span class="total-price">{{ formatPrice(prixTTC * quantity) }}</span>
            </div>
            <p class="summary-note">ℹ️ Le montant final sera calculé selon la durée de location choisie.</p>
          </div>

          <div class="action-buttons">
            <button @click="addToCart" :disabled="materiel.stock_disponible === 0 || addingToCart"
              class="add-to-cart-btn" :class="{ disabled: materiel.stock_disponible === 0 || addingToCart }">
              <span v-if="addingToCart" class="spinner-tiny"></span>
              <template v-else>🛒 {{ materiel.stock_disponible > 0 ? 'Ajouter au panier' : 'Rupture de stock' }}</template>
            </button>
            <button @click="reserveNow" :disabled="materiel.stock_disponible === 0"
              class="reserve-now-btn" :class="{ disabled: materiel.stock_disponible === 0 }">
              Réserver maintenant
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- Similaires -->
    <div class="similar-materiels" v-if="similaires.length > 0">
      <h2>Matériels similaires</h2>
      <div class="similar-grid">
        <div v-for="s in similaires" :key="s.id" class="similar-card" @click="$router.push(`/materiels/${s.id}`)">
          <div class="similar-image">
            <img :src="getSimilarPhoto(s)" :alt="s.nom" @error="(e) => { e.target.src = getInitialsImage(s.nom) }">
          </div>
          <div class="similar-info">
            <h4>{{ s.nom }}</h4>
            <!-- ✅ même champ pour les similaires -->
            <p class="similar-price">{{ formatPrice(parseFloat(s.prix_journalier_ht ?? s.prix_journalier ?? 0)) }} HT/jour</p>
          </div>
        </div>
      </div>
    </div>

    <!-- Lightbox -->
    <div class="lightbox" v-if="lightboxOpen" @click="closeLightbox">
      <button class="lightbox-close" @click="closeLightbox">×</button>
      <button class="lightbox-nav prev" @click.stop="prevPhoto">‹</button>
      <div class="lightbox-content" @click.stop>
        <img :src="currentPhoto" :alt="materiel.nom">
      </div>
      <button class="lightbox-nav next" @click.stop="nextPhoto">›</button>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, computed, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import api from '@/services/axios'
import { useAuthStore } from '@/stores/auth'

const route   = useRoute()
const router  = useRouter()
const auth    = useAuthStore()
const BASE_URL = import.meta.env.VITE_API_BASE_URL || 'http://127.0.0.1:8000'

const materiel          = ref(null)
const similaires        = ref([])
const loading           = ref(true)
const error             = ref(null)
const currentPhotoIndex = ref(0)
const quantity          = ref(1)
const lightboxOpen      = ref(false)
const materielId        = route.params.id

// ── IMAGES ────────────────────────────────────────────────────────────────────
const getInitialsImage = (nom = '') => {
  const initial = nom.charAt(0).toUpperCase() || '?'
  let hash = 0
  for (let i = 0; i < nom.length; i++) hash = nom.charCodeAt(i) + ((hash << 5) - hash)
  const color = encodeURIComponent(`hsl(${Math.abs(hash % 360)},70%,80%)`)
  return `data:image/svg+xml;utf8,<svg xmlns='http://www.w3.org/2000/svg' width='100' height='100'><rect width='100' height='100' fill='${color}'/><text x='50' y='64' font-size='40' text-anchor='middle' fill='%23333' font-family='Arial'>${initial}</text></svg>`
}
const buildUrl = (path) => {
  if (!path) return null
  const p = String(path).trim()
  if (p.startsWith('http')) return p
  return `${BASE_URL}/storage/${p.replace(/^\/+/, '').replace(/^storage\//, '')}`
}
const extractPhotos = (m) => {
  if (!m) return []
  const urls = []
  if (m.main_photo) urls.push(m.main_photo)
  if (Array.isArray(m.photos)) {
    for (const p of m.photos) {
      const url = buildUrl(p?.url_photo || p?.chemin_fichier || (typeof p === 'string' ? p : null))
      if (url && !urls.includes(url)) urls.push(url)
    }
  }
  if (m.photo) { const url = buildUrl(m.photo); if (url && !urls.includes(url)) urls.push(url) }
  return urls.length ? urls : [getInitialsImage(m.nom)]
}

const photos       = computed(() => extractPhotos(materiel.value))
const currentPhoto = computed(() => photos.value[currentPhotoIndex.value] || getInitialsImage(materiel.value?.nom))

const handleMainPhotoError = (e) => {
  if (e.target.src.startsWith('data:')) return
  e.target.onerror = null
  e.target.src = getInitialsImage(materiel.value?.nom)
}
const getSimilarPhoto = (s) => extractPhotos(s)[0] || getInitialsImage(s.nom)

// ── PRIX ──────────────────────────────────────────────────────────────────────
// Champ BDD : "prix_journalier_ht"  (voir MaterielController::store / update)
// Accessor Laravel : "prix_ttc"     (voir MaterielController::show transform)
// L'ancien MaterielDetail.vue lisait "prix_journalier" → affichait toujours 0.

const prixHT = computed(() =>
  parseFloat(materiel.value?.prix_journalier_ht ?? materiel.value?.prix_journalier ?? 0) || 0
)
const taux_tva = computed(() => parseFloat(materiel.value?.taux_tva ?? 20))
const prixTTC  = computed(() => {
  if (materiel.value?.prix_ttc) return parseFloat(materiel.value.prix_ttc)
  return prixHT.value * (1 + taux_tva.value / 100)
})

const formatPrice = (v) => {
  if (v == null || isNaN(v)) return '0,00 €'
  return new Intl.NumberFormat('fr-FR', { style: 'currency', currency: 'EUR', minimumFractionDigits: 2 }).format(v)
}

// ── CHARGEMENT ────────────────────────────────────────────────────────────────
const loadMateriel = async () => {
  loading.value = true; error.value = null
  try {
    const res  = await api.get(`/materiels/${materielId}`)
    const data = res.data?.data ?? res.data
    if (data?.id || data?.nom) {
      materiel.value  = data
      similaires.value = data.similaires || res.data?.similaires || []
    } else {
      error.value = 'Matériel introuvable.'
    }
  } catch (err) {
    console.error('Erreur matériel:', err.response?.data || err.message)
    error.value = err.response?.data?.message || 'Matériel introuvable.'
  } finally {
    loading.value = false
  }
}

// ── NAVIGATION ────────────────────────────────────────────────────────────────
const nextPhoto     = () => { currentPhotoIndex.value = (currentPhotoIndex.value + 1) % photos.value.length }
const prevPhoto     = () => { currentPhotoIndex.value = currentPhotoIndex.value === 0 ? photos.value.length - 1 : currentPhotoIndex.value - 1 }
const openLightbox  = () => { if (photos.value.length) lightboxOpen.value = true }
const closeLightbox = () => { lightboxOpen.value = false }

const incrementQuantity = () => { if (quantity.value < (materiel.value?.stock_disponible ?? 0)) quantity.value++ }
const decrementQuantity = () => { if (quantity.value > 1) quantity.value-- }
watch(quantity, (v) => {
  if (v < 1) quantity.value = 1
  if (materiel.value && v > materiel.value.stock_disponible) quantity.value = materiel.value.stock_disponible
})

// ── ACTIONS ───────────────────────────────────────────────────────────────────
const addingToCart = ref(false)

const addToCart = async () => {
  if (!materiel.value?.stock_disponible) return

  if (!auth.isAuthenticated) {
    router.push('/login')
    return
  }

  addingToCart.value = true
  try {
    const response = await api.post('/panier', {
      materiel_id: materiel.value.id,
      quantite: quantity.value
    })
    if (response.data?.success) {
      window.dispatchEvent(new CustomEvent('cartUpdated'))
      alert(`${quantity.value} × ${materiel.value.nom} ajouté au panier !`)
    }
  } catch (error) {
    const msg   = error.response?.data?.message
    const stock = error.response?.data?.stock_disponible
    if (stock !== undefined) {
      alert(`Stock insuffisant. Disponible : ${stock}`)
    } else {
      alert(msg || "Erreur lors de l'ajout au panier")
    }
  } finally {
    addingToCart.value = false
  }
}
const reserveNow = () => {
  if (!materiel.value?.stock_disponible) return
  router.push({ path: '/reservation', query: { materielId: materiel.value.id, quantity: quantity.value } })
}

onMounted(() => loadMateriel())
</script>

<style scoped>
.materiel-detail-container { max-width: 1200px; margin: 0 auto; padding: 20px; }
.loading-container, .error-container { display: flex; flex-direction: column; align-items: center; justify-content: center; min-height: 60vh; gap: 20px; text-align: center; }
.loading-spinner { width: 50px; height: 50px; border: 4px solid #f3f4f6; border-top: 4px solid #667eea; border-radius: 50%; animation: spin 1s linear infinite; }
@keyframes spin { to { transform: rotate(360deg); } }
.error-icon { font-size: 4rem; }
.error-container h2 { font-size: 1.8rem; color: #2d3748; }
.error-container p  { color: #718096; }
.back-link { padding: 12px 24px; background: #667eea; color: white; text-decoration: none; border-radius: 10px; font-weight: 600; }
.breadcrumb { display: flex; align-items: center; gap: 8px; margin-bottom: 30px; font-size: .9rem; color: #6b7280; flex-wrap: wrap; }
.breadcrumb a { color: #667eea; text-decoration: none; } .breadcrumb a:hover { text-decoration: underline; }
.breadcrumb .current { color: #374151; font-weight: 600; }
.materiel-content { display: grid; grid-template-columns: 1fr 1fr; gap: 50px; margin-bottom: 60px; }
.photo-gallery { display: flex; flex-direction: column; gap: 15px; }
.main-photo { position: relative; border-radius: 15px; overflow: hidden; height: 400px; background: #f3f4f6; cursor: zoom-in; }
.main-photo img { width: 100%; height: 100%; object-fit: cover; transition: transform .3s; }
.main-photo img[src^="data:image/svg"] { object-fit: contain; padding: 40px; }
.stock-status { position: absolute; top: 15px; right: 15px; padding: 8px 16px; border-radius: 20px; font-weight: 700; font-size: .85rem; }
.stock-status.in-stock { background: #10b981; color: white; } .stock-status.out-of-stock { background: #ef4444; color: white; }
.photo-thumbnails { display: flex; gap: 10px; overflow-x: auto; padding-bottom: 5px; }
.thumbnail { width: 80px; height: 80px; border-radius: 10px; overflow: hidden; cursor: pointer; border: 3px solid transparent; transition: all .3s; flex-shrink: 0; background: #f3f4f6; }
.thumbnail:hover, .thumbnail.active { border-color: #667eea; }
.thumbnail img { width: 100%; height: 100%; object-fit: cover; }
.materiel-info { display: flex; flex-direction: column; gap: 25px; }
.materiel-title { font-size: 2rem; color: #1f2937; margin: 0; font-weight: 700; line-height: 1.2; }
.categorie-badge { display: inline-block; padding: 6px 14px; background: #f3f4f6; color: #6b7280; border-radius: 20px; font-size: .9rem; font-weight: 600; }
.price-section { padding: 20px; background: #f0fdf4; border-radius: 12px; border: 2px solid #bbf7d0; }
.price-ht { display: flex; align-items: baseline; gap: 4px; margin-bottom: 6px; }
.price-value { font-size: 2.5rem; font-weight: 800; color: #10b981; }
.price-label { font-size: 1rem; color: #6b7280; }
.price-ttc { font-size: .95rem; color: #4b5563; }
.tva-info { color: #9ca3af; font-size: .85rem; margin-left: 5px; }
.specifications h3, .description-section h3, .reservation-section h3 { font-size: 1.2rem; color: #1f2937; margin: 0 0 15px; padding-bottom: 10px; border-bottom: 2px solid #f3f4f6; }
.spec-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 15px; }
.spec-item { display: flex; align-items: flex-start; gap: 10px; padding: 12px; background: #f9fafb; border-radius: 10px; }
.spec-icon { font-size: 1.3rem; }
.spec-label { font-size: .8rem; color: #9ca3af; margin-bottom: 3px; }
.spec-value { font-size: .95rem; color: #1f2937; font-weight: 600; }
.spec-value.low-stock { color: #ef4444; }
.description-text { color: #4b5563; line-height: 1.7; margin: 0; }
.quantity-selector { display: flex; flex-direction: column; gap: 10px; margin-bottom: 15px; }
.quantity-selector label { font-weight: 600; color: #374151; }
.quantity-controls { display: flex; align-items: center; border: 2px solid #e5e7eb; border-radius: 10px; overflow: hidden; width: fit-content; }
.quantity-btn { width: 44px; height: 44px; border: none; background: #f3f4f6; color: #374151; font-size: 1.2rem; cursor: pointer; }
.quantity-btn:hover:not(:disabled) { background: #e5e7eb; } .quantity-btn:disabled { opacity: .4; cursor: not-allowed; }
.quantity-input { width: 60px; height: 44px; border: none; border-left: 2px solid #e5e7eb; border-right: 2px solid #e5e7eb; text-align: center; font-size: 1rem; font-weight: 600; }
.quantity-input:focus { outline: none; }
.quantity-available { font-size: .85rem; color: #9ca3af; }
.reservation-summary { padding: 15px; background: #f9fafb; border-radius: 10px; margin-bottom: 20px; }
.summary-row { display: flex; justify-content: space-between; padding: 8px 0; font-size: .95rem; border-bottom: 1px solid #f3f4f6; }
.summary-row:last-of-type { border-bottom: none; }
.summary-row.total { font-weight: 700; font-size: 1.1rem; }
.total-price { color: #10b981; }
.summary-note { font-size: .8rem; color: #9ca3af; margin: 10px 0 0; font-style: italic; }
.action-buttons { display: flex; gap: 15px; }
.add-to-cart-btn, .reserve-now-btn { flex: 1; padding: 15px; border: none; border-radius: 10px; font-weight: 700; font-size: 1rem; cursor: pointer; transition: all .3s; display: flex; align-items: center; justify-content: center; gap: 8px; }
.add-to-cart-btn { background: #3b82f6; color: white; } .add-to-cart-btn:hover:not(.disabled) { background: #2563eb; transform: translateY(-2px); box-shadow: 0 4px 12px rgba(59,130,246,.3); }
.reserve-now-btn { background: #10b981; color: white; } .reserve-now-btn:hover:not(.disabled) { background: #0da271; transform: translateY(-2px); box-shadow: 0 4px 12px rgba(16,185,129,.3); }
.add-to-cart-btn.disabled, .reserve-now-btn.disabled { background: #9ca3af; cursor: not-allowed; }
.spinner-tiny { display: inline-block; width: 16px; height: 16px; border: 2px solid rgba(255,255,255,0.4); border-top-color: white; border-radius: 50%; animation: spin 0.7s linear infinite; }
.similar-materiels { margin-top: 60px; }
.similar-materiels h2 { font-size: 1.8rem; color: #1f2937; margin-bottom: 30px; }
.similar-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)); gap: 20px; }
.similar-card { cursor: pointer; border-radius: 12px; overflow: hidden; border: 1px solid #e5e7eb; transition: all .3s; background: white; }
.similar-card:hover { transform: translateY(-5px); box-shadow: 0 8px 20px rgba(0,0,0,.1); }
.similar-image { height: 150px; overflow: hidden; background: #f3f4f6; }
.similar-image img { width: 100%; height: 100%; object-fit: cover; }
.similar-image img[src^="data:image/svg"] { object-fit: contain; padding: 20px; }
.similar-info { padding: 15px; }
.similar-info h4 { font-size: 1rem; color: #1f2937; margin: 0 0 8px; font-weight: 600; }
.similar-price { font-size: .95rem; color: #10b981; font-weight: 700; margin: 0; }
.lightbox { position: fixed; inset: 0; background: rgba(0,0,0,.9); z-index: 1000; display: flex; align-items: center; justify-content: center; }
.lightbox-content img { max-width: 90vw; max-height: 85vh; object-fit: contain; border-radius: 10px; }
.lightbox-close { position: absolute; top: 20px; right: 20px; background: rgba(255,255,255,.2); border: none; color: white; font-size: 2rem; width: 50px; height: 50px; border-radius: 50%; cursor: pointer; }
.lightbox-nav { position: absolute; top: 50%; transform: translateY(-50%); background: rgba(255,255,255,.2); border: none; color: white; font-size: 3rem; width: 60px; height: 60px; border-radius: 50%; cursor: pointer; }
.lightbox-nav.prev { left: 20px; } .lightbox-nav.next { right: 20px; }
@media (max-width: 768px) {
  .materiel-content { grid-template-columns: 1fr; gap: 30px; }
  .main-photo { height: 300px; }
  .action-buttons { flex-direction: column; }
}
</style>