<template>
  <div class="panier-container">
    <div class="panier-header">
      <h1>🛒 Mon panier</h1>
      <p v-if="items.length > 0">
        {{ totalItems }} article{{ totalItems > 1 ? 's' : '' }} dans votre panier
      </p>
    </div>

    <!-- Loading -->
    <div v-if="loading" class="loading">
      <div class="spinner"></div>
      <p>Chargement du panier...</p>
    </div>

    <!-- Non connecté -->
    <div v-else-if="!isAuthenticated" class="panier-vide">
      <div class="empty-icon">🔒</div>
      <h2>Connectez-vous pour voir votre panier</h2>
      <p>Veuillez vous connecter pour accéder à votre panier et passer commande</p>
      <div class="auth-actions">
        <router-link to="/login" class="btn-login">Se connecter</router-link>
        <router-link to="/register" class="btn-register">Créer un compte</router-link>
      </div>
    </div>

    <!-- Panier vide -->
    <div v-else-if="items.length === 0" class="panier-vide">
      <div class="empty-icon">🛍️</div>
      <h2>Votre panier est vide</h2>
      <p>Vous n'avez pas encore ajouté de matériel à votre panier</p>
      <router-link to="/catalogue" class="btn-catalogue">
        Voir le catalogue
      </router-link>
    </div>

    <!-- Liste des matériels dans le panier -->
    <div v-else class="materiels-list">
      <div v-for="item in items" :key="item.id" class="materiel-card">
        <div class="materiel-image">
          <img 
            :src="getImageUrl(item)" 
            :alt="item.nom" 
            @error="(e) => handleImageError(e, item)"
          >
          <button @click="removeFromCart(item.id)" class="btn-remove" title="Supprimer">
            <span class="remove-icon">✕</span>
          </button>
        </div>
        
        <div class="materiel-info">
          <div class="materiel-header">
            <div class="materiel-title-group">
              <h3>{{ item.nom }}</h3>
              <span class="materiel-categorie">{{ item.categorie }}</span>
            </div>
          </div>
          
          <div class="materiel-details">
            <div class="detail-row">
              <div class="detail-item">
                <span class="detail-label">Prix HT:</span>
                <span class="detail-value prix">{{ formatPrice(item.prix_unitaire_ht) }}</span>
                <span class="detail-period">/jour HT</span>
              </div>
            </div>
            
            <div class="detail-row" v-if="item.taux_tva">
              <div class="detail-item">
                <span class="detail-label">Prix TTC:</span>
                <span class="detail-value">{{ formatPrice(item.prix_unitaire_ttc) }}</span>
                <span class="detail-period">/jour (TVA {{ item.taux_tva }}%)</span>
              </div>
            </div>
            
            <div class="detail-row">
              <div class="quantity-control">
                <span class="detail-label">Quantité:</span>
                <div class="quantity-buttons">
                  <button 
                    @click="decreaseQuantity(item)" 
                    :disabled="updatingId === item.id || item.quantite <= 1"
                    class="qty-btn minus"
                  >−</button>
                  <span class="quantity-value">{{ item.quantite }}</span>
                  <button 
                    @click="increaseQuantity(item)" 
                    :disabled="updatingId === item.id || item.quantite >= item.stock_disponible"
                    class="qty-btn plus"
                  >+</button>
                </div>
                <span class="stock-info" v-if="item.stock_disponible">
                  Stock: {{ item.stock_disponible }}
                </span>
              </div>
            </div>
          </div>
        </div>

        <div class="materiel-total">
          <div class="total-group">
            <span class="total-label">Total HT:</span>
            <span class="total-prix">{{ formatPrice(item.total_ht) }}</span>
          </div>
          <div class="total-group ttc">
            <span class="total-label">Total TTC:</span>
            <span class="total-prix-ttc">{{ formatPrice(item.total_ttc) }}</span>
          </div>
        </div>
      </div>

      <!-- Total général et actions -->
      <div class="panier-footer">
        <div class="panier-resume">
          <div class="resume-item">
            <span class="resume-label">Sous-total HT:</span>
            <span class="resume-value">{{ formatPrice(totalHT) }}</span>
          </div>
          <div class="resume-item">
            <span class="resume-label">TVA:</span>
            <span class="resume-value">{{ formatPrice(totalTTC - totalHT) }}</span>
          </div>
          <div class="resume-item total">
            <span class="resume-label">Total TTC:</span>
            <span class="resume-value total">{{ formatPrice(totalTTC) }}</span>
          </div>
          <div class="resume-item" v-if="totalItems > 0">
            <span class="resume-label">Nombre d'articles:</span>
            <span class="resume-value">{{ totalItems }}</span>
          </div>
        </div>

        <div class="panier-total">
          <div class="total-ligne">
            <span class="total-label">Total panier TTC:</span>
            <span class="total-montant">{{ formatPrice(totalTTC) }}</span>
          </div>
          <div class="total-actions">
            <button @click="clearCart" class="btn-clear" :disabled="clearing">
              <span v-if="!clearing">🗑️ Vider le panier</span>
              <span v-else class="spinner-small"></span>
            </button>
            <button @click="proceedToCheckout" class="btn-checkout" :disabled="checkingOut">
              <span v-if="!checkingOut">💳 Passer la commande</span>
              <span v-else class="spinner-small"></span>
            </button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
import api from '@/services/axios'

const router = useRouter()
const auth = useAuthStore()

const BASE_URL = import.meta.env.VITE_API_BASE_URL || 'http://127.0.0.1:8000'

// État
const items = ref([])
const totalHT = ref(0)
const totalTTC = ref(0)
const loading = ref(true)
const updatingId = ref(null)
const clearing = ref(false)
const checkingOut = ref(false)

// Computed
const isAuthenticated = computed(() => auth.isAuthenticated)

const totalItems = computed(() => {
  return items.value.reduce((total, item) => total + item.quantite, 0)
})

// ==================== GESTION DES IMAGES CORRIGÉE ====================

/**
 * Génère une image SVG avec l'initiale du matériel
 */
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

/**
 * Récupère l'URL de l'image depuis l'objet item
 * Le backend peut renvoyer différents formats
 */
const getImageUrl = (item) => {
  if (!item || !item.photo) {
    return getInitialsImage(item?.nom)
  }

  const photo = item.photo;
  
  // Si le backend a déjà envoyé une URL complète (ce que fait votre contrôleur)
  if (photo.startsWith('http')) {
    return photo;
  }

  // Sécurité si le backend envoie un chemin relatif
  const cleanPath = photo.replace(/^\//, ''); // Enlève le slash au début
  if (cleanPath.startsWith('storage/')) {
    return `${BASE_URL}/${cleanPath}`;
  }
  
  return `${BASE_URL}/storage/${cleanPath}`;
}

/**
 * Gère les erreurs de chargement d'image
 */
const handleImageError = (e, item) => {
  if (e.target.src.startsWith('data:')) return
  console.warn('Erreur chargement image:', e.target.src)
  e.target.onerror = null
  e.target.src = getInitialsImage(item?.nom)
}

// ==================== FORMATAGE PRIX ====================

const formatPrice = (price) => {
  if (price === undefined || price === null || isNaN(price)) return '0,00 €'
  return new Intl.NumberFormat('fr-FR', {
    style: 'currency',
    currency: 'EUR',
    minimumFractionDigits: 2
  }).format(price)
}

// ==================== CHARGEMENT DU PANIER ====================

const loadPanier = async () => {
  if (!isAuthenticated.value) {
    loading.value = false
    return
  }
  
  loading.value = true
  try {
    const response = await api.get('/panier')
    console.log('📦 Réponse panier:', response.data)
    
    if (response.data.success) {
      const data = response.data.data
      
      // Mapping des données avec tous les champs nécessaires
      items.value = (data.items || []).map(item => ({
        id: item.id,
        materiel_id: item.materiel_id,
        nom: item.nom || 'Matériel inconnu',
        quantite: Number(item.quantite) || 1,
        prix_unitaire_ht: Number(item.prix_unitaire_ht) || 0,
        prix_unitaire_ttc: Number(item.prix_unitaire_ttc) || 0,
        taux_tva: Number(item.taux_tva) || 20,
        total_ht: Number(item.total_ht) || 0,
        total_ttc: Number(item.total_ttc) || 0,
        photo: item.photo || null,
        categorie: item.categorie || 'Non catégorisé',
        stock_disponible: Number(item.stock_disponible) || 0
      }))
      
      totalHT.value = Number(data.total_ht) || 0
      totalTTC.value = Number(data.total_ttc) || 0
      
      console.log('✅ Items chargés:', items.value)
    }
  } catch (error) {
    console.error('❌ Erreur chargement panier:', error)
    items.value = []
    totalHT.value = 0
    totalTTC.value = 0
  } finally {
    loading.value = false
  }
}

// ==================== ACTIONS SUR LE PANIER ====================

const updateQuantity = async (itemId, newQuantity) => {
  updatingId.value = itemId
  try {
    const response = await api.put(`/panier/${itemId}`, { quantite: newQuantity })
    if (response.data.success) {
      await loadPanier()
      window.dispatchEvent(new CustomEvent('cartUpdated'))
    }
  } catch (error) {
    console.error('Erreur mise à jour quantité:', error)
    if (error.response?.data?.stock_disponible) {
      alert(`Stock insuffisant. Disponible: ${error.response.data.stock_disponible}`)
    }
    await loadPanier()
  } finally {
    updatingId.value = null
  }
}

const increaseQuantity = (item) => {
  if (item.quantite < item.stock_disponible) {
    updateQuantity(item.id, item.quantite + 1)
  }
}

const decreaseQuantity = (item) => {
  if (item.quantite > 1) {
    updateQuantity(item.id, item.quantite - 1)
  }
}

const removeFromCart = async (itemId) => {
  if (!confirm('Voulez-vous supprimer cet article du panier ?')) return
  
  try {
    const response = await api.delete(`/panier/${itemId}`)
    if (response.data.success) {
      await loadPanier()
      window.dispatchEvent(new CustomEvent('cartUpdated'))
    }
  } catch (error) {
    console.error('Erreur suppression:', error)
    alert('Erreur lors de la suppression')
  }
}

const clearCart = async () => {
  if (!confirm('Voulez-vous vraiment vider votre panier ?')) return
  
  clearing.value = true
  try {
    const response = await api.delete('/panier')
    if (response.data.success) {
      await loadPanier()
      window.dispatchEvent(new CustomEvent('cartUpdated'))
    }
  } catch (error) {
    console.error('Erreur vidage panier:', error)
    alert('Erreur lors du vidage du panier')
  } finally {
    clearing.value = false
  }
}

const proceedToCheckout = async () => {
  if (items.value.length === 0) return
  
  checkingOut.value = true
  try {
    // Vérification rapide des stocks
    const stockOk = items.value.every(item => 
      item.quantite <= item.stock_disponible
    )
    
    if (!stockOk) {
      alert('Certains articles ne sont plus disponibles en quantité suffisante.')
      await loadPanier()
      return
    }
    
    router.push('/checkout')
  } catch (error) {
    console.error('Erreur:', error)
    router.push('/checkout')
  } finally {
    checkingOut.value = false
  }
}

// ==================== ÉVÉNEMENTS ====================

const handleCartUpdate = () => loadPanier()
const handleAuthChange = () => loadPanier()

onMounted(() => {
  loadPanier()
  window.addEventListener('cartUpdated', handleCartUpdate)
  window.addEventListener('authChanged', handleAuthChange)
})

onUnmounted(() => {
  window.removeEventListener('cartUpdated', handleCartUpdate)
  window.removeEventListener('authChanged', handleAuthChange)
})
</script>


<style scoped>
/* Styles existants + ajouts */

.panier-container {
  max-width: 1000px;
  margin: 0 auto;
  padding: 20px;
  animation: fadeIn 0.3s ease;
}

@keyframes fadeIn {
  from { opacity: 0; transform: translateY(10px); }
  to { opacity: 1; transform: translateY(0); }
}

.panier-header {
  text-align: center;
  margin-bottom: 40px;
}

.panier-header h1 {
  font-size: 2.5rem;
  color: #2d3748;
  margin-bottom: 10px;
  font-weight: 700;
}

.panier-header p {
  color: #718096;
  font-size: 1.1rem;
}

/* Loading */
.loading {
  text-align: center;
  padding: 60px 20px;
}

.spinner {
  width: 50px;
  height: 50px;
  border: 4px solid #f3f4f6;
  border-top: 4px solid #667eea;
  border-radius: 50%;
  animation: spin 1s linear infinite;
  margin: 0 auto 20px;
}

@keyframes spin {
  0% { transform: rotate(0deg); }
  100% { transform: rotate(360deg); }
}

.spinner-small {
  display: inline-block;
  width: 20px;
  height: 20px;
  border: 2px solid rgba(255,255,255,0.3);
  border-top-color: white;
  border-radius: 50%;
  animation: spin 0.8s linear infinite;
}

/* Panier vide */
.panier-vide {
  text-align: center;
  padding: 80px 20px;
  background: white;
  border-radius: 15px;
  box-shadow: 0 4px 20px rgba(0, 0, 0, 0.05);
}

.empty-icon {
  font-size: 5rem;
  margin-bottom: 20px;
  opacity: 0.5;
  animation: bounce 2s infinite;
}

@keyframes bounce {
  0%, 100% { transform: translateY(0); }
  50% { transform: translateY(-10px); }
}

.panier-vide h2 {
  font-size: 1.8rem;
  color: #2d3748;
  margin-bottom: 10px;
}

.panier-vide p {
  color: #718096;
  margin-bottom: 30px;
}

.auth-actions {
  display: flex;
  gap: 15px;
  justify-content: center;
}

.btn-login,
.btn-register,
.btn-catalogue {
  display: inline-block;
  padding: 12px 24px;
  border-radius: 10px;
  font-weight: 600;
  text-decoration: none;
  transition: all 0.3s;
}

.btn-login {
  background: #667eea;
  color: white;
}

.btn-login:hover {
  transform: translateY(-2px);
  box-shadow: 0 8px 20px rgba(102, 126, 234, 0.3);
}

.btn-register {
  background: white;
  color: #667eea;
  border: 2px solid #667eea;
}

.btn-register:hover {
  background: #f0f4ff;
  transform: translateY(-2px);
}

.btn-catalogue {
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  color: white;
}

.btn-catalogue:hover {
  transform: translateY(-2px);
  box-shadow: 0 8px 20px rgba(102, 126, 234, 0.3);
}

/* Liste des matériels */
.materiels-list {
  display: flex;
  flex-direction: column;
  gap: 20px;
}

.materiel-card {
  display: grid;
  grid-template-columns: 120px 1fr auto;
  gap: 20px;
  padding: 20px;
  background: white;
  border-radius: 12px;
  box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
  border: 1px solid #e2e8f0;
  transition: all 0.3s;
  position: relative;
}

.materiel-card:hover {
  box-shadow: 0 8px 20px rgba(0, 0, 0, 0.1);
  border-color: #cbd5e0;
}

.materiel-image {
  position: relative;
  width: 120px;
  height: 120px;
  border-radius: 10px;
  overflow: hidden;
  background: #f8fafc;
}

.materiel-image img {
  width: 100%;
  height: 100%;
  object-fit: cover;
}

.btn-remove {
  position: absolute;
  top: -8px;
  right: -8px;
  width: 30px;
  height: 30px;
  background: #fee2e2;
  color: #dc2626;
  border: none;
  border-radius: 50%;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 1.1rem;
  transition: all 0.3s;
  opacity: 0;
}

.materiel-card:hover .btn-remove {
  opacity: 1;
}

.btn-remove:hover {
  background: #fecaca;
  transform: scale(1.1);
}

.btn-remove:disabled {
  opacity: 0.3;
  cursor: not-allowed;
}

.remove-icon {
  font-weight: bold;
}

.materiel-info {
  display: flex;
  flex-direction: column;
  gap: 15px;
}

.materiel-header {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
}

.materiel-title-group {
  display: flex;
  flex-direction: column;
  gap: 8px;
}

.materiel-header h3 {
  font-size: 1.2rem;
  color: #2d3748;
  margin: 0;
  font-weight: 600;
}

.materiel-categorie {
  background: #f7fafc;
  color: #4a5568;
  padding: 4px 12px;
  border-radius: 15px;
  font-size: 0.85rem;
  font-weight: 600;
  display: inline-block;
  align-self: flex-start;
}

.materiel-details {
  display: flex;
  flex-direction: column;
  gap: 12px;
}

.detail-row {
  display: flex;
  align-items: center;
}

.detail-item {
  display: flex;
  align-items: baseline;
  gap: 8px;
}

.detail-label {
  color: #718096;
  font-size: 0.9rem;
}

.detail-value {
  font-weight: 600;
  color: #2d3748;
}

.detail-value.prix {
  color: #667eea;
  font-size: 1.1rem;
}

.detail-period {
  color: #a0aec0;
  font-size: 0.8rem;
}

/* Contrôle quantité */
.quantity-control {
  display: flex;
  align-items: center;
  gap: 15px;
  flex-wrap: wrap;
}

.quantity-buttons {
  display: flex;
  align-items: center;
  gap: 8px;
}

.qty-btn {
  width: 32px;
  height: 32px;
  border: 2px solid #e2e8f0;
  background: white;
  border-radius: 8px;
  font-size: 1.2rem;
  cursor: pointer;
  transition: all 0.3s;
  display: flex;
  align-items: center;
  justify-content: center;
}

.qty-btn:hover:not(:disabled) {
  background: #667eea;
  color: white;
  border-color: #667eea;
}

.qty-btn:disabled {
  opacity: 0.3;
  cursor: not-allowed;
}

.qty-btn.minus {
  color: #ef4444;
}

.qty-btn.plus {
  color: #10b981;
}

.quantity-value {
  font-weight: 700;
  font-size: 1.1rem;
  min-width: 30px;
  text-align: center;
  color: #2d3748;
}

.stock-info {
  color: #718096;
  font-size: 0.85rem;
  background: #f7fafc;
  padding: 4px 10px;
  border-radius: 12px;
}

/* Total par article */
.materiel-total {
  display: flex;
  flex-direction: column;
  align-items: flex-end;
  justify-content: center;
  gap: 8px;
  padding-left: 20px;
  border-left: 1px solid #e2e8f0;
  min-width: 150px;
}

.total-group {
  display: flex;
  align-items: baseline;
  gap: 8px;
}

.total-group.ttc {
  border-top: 1px dashed #e2e8f0;
  padding-top: 8px;
  margin-top: 4px;
}

.total-label {
  color: #718096;
  font-size: 0.85rem;
}

.total-prix {
  font-size: 1.2rem;
  font-weight: 700;
  color: #4a5568;
}

.total-prix-ttc {
  font-size: 1.4rem;
  font-weight: 700;
  color: #10b981;
}

/* Footer panier */
.panier-footer {
  margin-top: 30px;
  background: white;
  border-radius: 12px;
  border: 1px solid #e2e8f0;
  overflow: hidden;
}

.panier-resume {
  padding: 20px;
  background: #f8fafc;
  border-bottom: 1px solid #e2e8f0;
}

.resume-item {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 10px;
}

.resume-item:last-child {
  margin-bottom: 0;
}

.resume-item.total {
  border-top: 1px solid #e2e8f0;
  margin-top: 10px;
  padding-top: 10px;
  font-weight: 700;
}

.resume-label {
  color: #4a5568;
  font-weight: 500;
}

.resume-value {
  font-weight: 600;
  color: #2d3748;
}

.resume-value.total {
  color: #10b981;
  font-size: 1.2rem;
}

.panier-total {
  padding: 20px;
  display: flex;
  justify-content: space-between;
  align-items: center;
  flex-wrap: wrap;
  gap: 20px;
}

.total-ligne {
  display: flex;
  align-items: baseline;
  gap: 15px;
}

.total-label {
  font-size: 1.2rem;
  font-weight: 600;
  color: #2d3748;
}

.total-montant {
  font-size: 2rem;
  font-weight: 800;
  color: #10b981;
}

.total-actions {
  display: flex;
  gap: 15px;
}

.btn-clear {
  padding: 12px 24px;
  background: white;
  color: #dc2626;
  border: 2px solid #fee2e2;
  border-radius: 10px;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.3s;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 8px;
  min-width: 150px;
}

.btn-clear:hover:not(:disabled) {
  background: #fee2e2;
  border-color: #fecaca;
  transform: translateY(-2px);
}

.btn-clear:disabled {
  opacity: 0.3;
  cursor: not-allowed;
}

.btn-checkout {
  padding: 12px 32px;
  background: linear-gradient(135deg, #10b981 0%, #34d399 100%);
  color: white;
  border: none;
  border-radius: 10px;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.3s;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 8px;
  min-width: 180px;
}

.btn-checkout:hover:not(:disabled) {
  transform: translateY(-2px);
  box-shadow: 0 8px 20px rgba(16, 185, 129, 0.3);
}

.btn-checkout:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}

/* Responsive */
@media (max-width: 768px) {
  .panier-container {
    padding: 15px;
  }
  
  .panier-header h1 {
    font-size: 2rem;
  }
  
  .materiel-card {
    grid-template-columns: 1fr;
    gap: 15px;
  }
  
  .materiel-image {
    width: 100%;
    height: 200px;
  }
  
  .btn-remove {
    opacity: 1;
    top: 10px;
    right: 10px;
  }
  
  .materiel-total {
    border-left: none;
    border-top: 1px solid #e2e8f0;
    padding: 15px 0 0 0;
    margin-top: 15px;
    flex-direction: row;
    justify-content: space-between;
    align-items: center;
  }
  
  .panier-total {
    flex-direction: column;
    align-items: stretch;
  }
  
  .total-ligne {
    justify-content: space-between;
  }
  
  .total-actions {
    flex-direction: column;
  }
  
  .btn-clear,
  .btn-checkout {
    width: 100%;
  }
  
  .auth-actions {
    flex-direction: column;
    gap: 10px;
  }
  
  .btn-login,
  .btn-register {
    width: 100%;
    text-align: center;
  }
}

@media (max-width: 480px) {
  .detail-row {
    flex-direction: column;
    align-items: flex-start;
    gap: 10px;
  }
  
  .quantity-control {
    flex-direction: column;
    align-items: flex-start;
    width: 100%;
  }
  
  .quantity-buttons {
    width: 100%;
    justify-content: space-between;
  }
  
  .stock-info {
    align-self: flex-start;
  }
  
  .materiel-total {
    flex-direction: column;
    align-items: flex-start;
    gap: 10px;
  }
}
</style>