<template>
  <div class="materiel-detail-container" v-if="materiel">
    <!-- Fil d'Ariane amélioré -->
    <nav class="breadcrumb-nav">
      <div class="breadcrumb-content">
        <router-link to="/catalogue" class="breadcrumb-link">
          <span class="breadcrumb-icon">🏠</span>
          Catalogue
        </router-link>
        <span class="breadcrumb-divider">›</span>
        <router-link :to="`/catalogue?categorie=${materiel.categorie?.id}`" class="breadcrumb-link">
          <span class="breadcrumb-icon">{{ getCategoryIcon(materiel.categorie?.nom) }}</span>
          {{ materiel.categorie?.nom }}
        </router-link>
        <span class="breadcrumb-divider">›</span>
        <span class="breadcrumb-current">
          <span class="current-icon">📦</span>
          {{ materiel.nom }}
        </span>
      </div>
    </nav>

    <div class="materiel-content">
      <!-- Galerie photos améliorée -->
      <div class="photo-gallery-section">
        <div class="gallery-header">
          <h3 class="gallery-title">📸 Galerie photos</h3>
          <div class="photo-counter" v-if="photos.length > 1">
            {{ currentPhotoIndex + 1 }} / {{ photos.length }}
          </div>
        </div>
        
        <div class="gallery-container">
          <!-- Photo principale avec effets -->
          <div class="main-photo-wrapper" @click="openLightbox">
            <div class="main-photo">
              <img 
                :src="currentPhoto?.url_photo || '/placeholder.jpg'" 
                :alt="materiel.nom"
                @error="(e) => e.target.src = '/placeholder.jpg'"
                class="product-image"
                :class="{ 'loading': imageLoading }"
                @load="imageLoading = false"
                @loadstart="imageLoading = true"
              >
              <div class="image-overlay">
                <div class="zoom-indicator">
                  <span class="zoom-icon">🔍</span>
                  Cliquez pour zoomer
                </div>
              </div>
            </div>
            
            <!-- Badges améliorés -->
            <div class="product-badges">
              <div class="stock-badge" :class="{ 
                'in-stock': materiel.stock_disponible > 0, 
                'low-stock': materiel.stock_disponible < 10 && materiel.stock_disponible > 0,
                'out-of-stock': materiel.stock_disponible === 0 
              }">
                <span class="badge-icon">
                  <span v-if="materiel.stock_disponible > 0">✓</span>
                  <span v-else>✗</span>
                </span>
                <span class="badge-text">
                  {{ materiel.stock_disponible > 0 ? `${materiel.stock_disponible} disponible(s)` : 'Rupture de stock' }}
                </span>
              </div>
              
              <div v-if="materiel.is_new" class="new-badge">
                <span class="badge-icon">🎉</span>
                <span class="badge-text">Nouveau !</span>
              </div>
              
              <div v-if="materiel.prix_journalier < 5" class="promo-badge">
                <span class="badge-icon">🔥</span>
                <span class="badge-text">Bon plan</span>
              </div>
            </div>
          </div>

          <!-- Miniatures améliorées -->
          <div class="thumbnails-container" v-if="photos.length > 1">
            <div class="thumbnails-scroll">
              <div 
                v-for="(photo, index) in photosWithUrls" 
                :key="photo.id || index" 
                class="thumbnail-card"
                :class="{ 'active': currentPhotoIndex === index }"
                @click="changePhoto(index)"
              >
                <div class="thumbnail-wrapper">
                  <img 
                    :src="photo.url_photo" 
                    :alt="`${materiel.nom} - vue ${index + 1}`" 
                    @error="(e) => e.target.src = '/placeholder.jpg'"
                  >
                  <div class="thumbnail-overlay" :class="{ 'active': currentPhotoIndex === index }">
                    <span class="view-icon">👁️</span>
                  </div>
                </div>
              </div>
            </div>
            <div class="thumbnails-controls">
              <button 
                @click="scrollThumbnails(-1)" 
                class="thumb-btn prev-thumb"
                :disabled="thumbnailScroll === 0"
              >
                ‹
              </button>
              <button 
                @click="scrollThumbnails(1)" 
                class="thumb-btn next-thumb"
                :disabled="thumbnailScroll >= photos.length - 4"
              >
                ›
              </button>
            </div>
          </div>
        </div>
      </div>

      <!-- Informations du matériel améliorées -->
      <div class="materiel-info-section">
        <!-- En-tête produit -->
        <div class="product-header">
          <div class="title-wrapper">
            <h1 class="materiel-title">{{ materiel.nom }}</h1>
            <div class="product-rating" v-if="materiel.rating">
              <div class="stars">
                <span v-for="n in 5" :key="n" class="star" :class="{ 'filled': n <= materiel.rating }">
                  ★
                </span>
              </div>
              <span class="rating-text">({{ materiel.review_count || 0 }} avis)</span>
            </div>
          </div>
          
          <div class="category-wrapper">
            <div class="categorie-tag">
              <span class="tag-icon">{{ getCategoryIcon(materiel.categorie?.nom) }}</span>
              <span class="tag-text">{{ materiel.categorie?.nom }}</span>
            </div>
            <div class="product-id">
              <span class="id-label">Réf:</span>
              <span class="id-value">{{ materiel.id }}</span>
            </div>
          </div>
        </div>

        <!-- Prix section améliorée -->
        <div class="price-card">
          <div class="price-content">
            <div class="price-display">
              <span class="price-currency">€</span>
              <span class="price-amount">{{ formatPriceNumber(materiel.prix_journalier) }}</span>
            </div>
            <div class="price-details">
              <span class="price-period">/ jour de location</span>
              <div v-if="materiel.prix_journalier < 10" class="price-note">
                <span class="note-icon">⭐</span>
                <span class="note-text">Tarif économique</span>
              </div>
            </div>
          </div>
          
          <div class="saving-badge" v-if="quantity > 1">
            <span class="saving-icon">💸</span>
            <span class="saving-text">
              Économisez {{ formatPrice((quantity - 1) * materiel.prix_journalier * 0.1) }} sur cette commande
            </span>
          </div>
        </div>

        <!-- Caractéristiques améliorées -->
        <div class="specs-card">
          <div class="specs-header">
            <h3 class="specs-title">
              <span class="title-icon">⚙️</span>
              Caractéristiques techniques
            </h3>
            <button class="specs-expand-btn" @click="toggleSpecs">
              {{ specsExpanded ? 'Réduire' : 'Voir plus' }}
            </button>
          </div>
          
          <div class="specs-grid" :class="{ 'expanded': specsExpanded }">
            <div class="spec-item" v-if="materiel.dimensions">
              <div class="spec-icon-box">
                <span class="spec-icon">📏</span>
              </div>
              <div class="spec-content">
                <div class="spec-label">Dimensions</div>
                <div class="spec-value">{{ materiel.dimensions }}</div>
              </div>
            </div>
            
            <div class="spec-item">
              <div class="spec-icon-box">
                <span class="spec-icon">📦</span>
              </div>
              <div class="spec-content">
                <div class="spec-label">Stock disponible</div>
                <div class="spec-value" :class="{ 
                  'low-stock': materiel.stock_disponible < 10 && materiel.stock_disponible > 0,
                  'out-of-stock': materiel.stock_disponible === 0 
                }">
                  {{ materiel.stock_disponible }} unité(s)
                  <div v-if="materiel.stock_disponible < 10 && materiel.stock_disponible > 0" class="stock-warning">
                    ⚠️ Stock limité
                  </div>
                </div>
              </div>
            </div>
            
            <div class="spec-item" v-if="materiel.stock_total">
              <div class="spec-icon-box">
                <span class="spec-icon">🏭</span>
              </div>
              <div class="spec-content">
                <div class="spec-label">Stock total</div>
                <div class="spec-value">{{ materiel.stock_total }} unité(s)</div>
              </div>
            </div>
            
            <div class="spec-item" v-if="materiel.created_at">
              <div class="spec-icon-box">
                <span class="spec-icon">📅</span>
              </div>
              <div class="spec-content">
                <div class="spec-label">Ajouté le</div>
                <div class="spec-value">{{ formatDate(materiel.created_at) }}</div>
              </div>
            </div>
            
            <div class="spec-item" v-if="materiel.weight">
              <div class="spec-icon-box">
                <span class="spec-icon">⚖️</span>
              </div>
              <div class="spec-content">
                <div class="spec-label">Poids</div>
                <div class="spec-value">{{ materiel.weight }}</div>
              </div>
            </div>
            
            <div class="spec-item" v-if="materiel.color">
              <div class="spec-icon-box">
                <span class="spec-icon">🎨</span>
              </div>
              <div class="spec-content">
                <div class="spec-label">Couleur</div>
                <div class="spec-value">{{ materiel.color }}</div>
              </div>
            </div>
          </div>
        </div>

        <!-- Description améliorée -->
        <div class="description-card">
          <h3 class="description-title">
            <span class="title-icon">📝</span>
            Description détaillée
          </h3>
          <div class="description-content">
            <p class="description-text">{{ materiel.description || 'Aucune description disponible pour ce produit.' }}</p>
            <div class="description-features" v-if="features.length > 0">
              <div class="feature-item" v-for="(feature, index) in features" :key="index">
                <span class="feature-icon">✓</span>
                <span class="feature-text">{{ feature }}</span>
              </div>
            </div>
          </div>
        </div>

        <!-- Réservation améliorée -->
        <div class="reservation-card">
          <div class="reservation-header">
            <h3 class="reservation-title">
              <span class="title-icon">🛒</span>
              Réserver ce matériel
            </h3>
            <div class="delivery-info">
              <span class="delivery-icon">🚚</span>
              <span class="delivery-text">Livraison sous 24-48h</span>
            </div>
          </div>
          
          <div class="reservation-body">
            <!-- Sélecteur quantité amélioré -->
            <div class="quantity-selector-card">
              <div class="quantity-header">
                <label class="quantity-label">Quantité :</label>
                <div class="quantity-badges">
                  <span 
                    v-for="qty in [1, 3, 5, 10]" 
                    :key="qty"
                    class="quantity-badge"
                    :class="{ 'active': quantity === qty }"
                    @click="setQuantity(qty)"
                    :disabled="qty > materiel.stock_disponible"
                  >
                    {{ qty }}
                  </span>
                </div>
              </div>
              
              <div class="quantity-controls">
                <div class="quantity-stepper">
                  <button 
                    @click="decrementQuantity" 
                    :disabled="quantity <= 1"
                    class="stepper-btn minus-btn"
                    aria-label="Réduire la quantité"
                  >
                    <span class="stepper-icon">−</span>
                  </button>
                  
                  <div class="quantity-input-wrapper">
                    <input 
                      type="number" 
                      id="quantity" 
                      v-model.number="quantity" 
                      min="1" 
                      :max="materiel.stock_disponible"
                      class="quantity-input"
                      @change="validateQuantity"
                      @input="validateQuantity"
                    >
                    <div class="quantity-units">unité(s)</div>
                  </div>
                  
                  <button 
                    @click="incrementQuantity" 
                    :disabled="quantity >= materiel.stock_disponible"
                    class="stepper-btn plus-btn"
                    aria-label="Augmenter la quantité"
                  >
                    <span class="stepper-icon">+</span>
                  </button>
                </div>
                
                <div class="quantity-limits">
                  <span class="limit-min">Minimum: 1</span>
                  <span class="limit-max">Maximum: {{ materiel.stock_disponible }}</span>
                </div>
              </div>
            </div>

            <!-- Récapitulatif amélioré -->
            <div class="summary-card">
              <div class="summary-header">
                <h4 class="summary-title">Récapitulatif de commande</h4>
              </div>
              
              <div class="summary-items">
                <div class="summary-item">
                  <span class="item-label">{{ quantity }} × {{ materiel.nom }}</span>
                  <span class="item-value">{{ prixFormate }}</span>
                </div>
                
                <div class="summary-item" v-if="quantity > 3">
                  <span class="item-label">Remise volume (10%)</span>
                  <span class="item-value discount">−{{ formatPrice(discountAmount) }}</span>
                </div>
                
                <div class="summary-item" v-if="quantity > 1">
                  <span class="item-label">
                    <span class="savings-icon">💰</span>
                    Économies
                  </span>
                  <span class="item-value savings">+{{ formatPrice(savingsAmount) }}</span>
                </div>
              </div>
              
              <div class="summary-total">
                <div class="total-label">
                  <span class="total-icon">💳</span>
                  Total estimé
                </div>
                <div class="total-amount">
                  <span class="total-price">{{ prixTotalFormate }}</span>
                  <span class="total-period">pour {{ quantity }} jour(s)</span>
                </div>
              </div>
              
              <div class="summary-note" v-if="quantity > 5">
                <span class="note-icon">🎯</span>
                <span class="note-text">Commande importante ! Contactez-nous pour une remise spéciale.</span>
              </div>
            </div>

            <!-- Actions améliorées -->
            <div class="action-buttons-grid">
              <button 
                @click="addToCart"
                :disabled="materiel.stock_disponible === 0 || addingToCart"
                class="action-btn cart-btn"
                :class="{ 
                  'disabled': materiel.stock_disponible === 0,
                  'loading': addingToCart
                }"
              >
                <span v-if="!addingToCart" class="btn-icon">🛒</span>
                <span v-else class="loading-spinner-small"></span>
                <span class="btn-text">
                  {{ materiel.stock_disponible > 0 ? 'Ajouter au panier' : 'Rupture de stock' }}
                </span>
              </button>
              
              <button 
                @click="addToWishlist"
                class="action-btn wishlist-btn"
                :class="{ 'active': inWishlist }"
              >
                <span class="btn-icon">{{ inWishlist ? '❤️' : '🤍' }}</span>
                <span class="btn-text">{{ inWishlist ? 'Dans la liste' : 'Liste de souhaits' }}</span>
              </button>
              
              <button 
                @click="reserveNow"
                :disabled="materiel.stock_disponible === 0"
                class="action-btn reserve-btn"
                :class="{ 'disabled': materiel.stock_disponible === 0 }"
              >
                <span class="btn-icon">⚡</span>
                <span class="btn-text">Réserver maintenant</span>
              </button>
              
              <button 
                @click="shareProduct"
                class="action-btn share-btn"
              >
                <span class="btn-icon">📤</span>
                <span class="btn-text">Partager</span>
              </button>
            </div>
            
            <div class="safety-info">
              <div class="safety-item">
                <span class="safety-icon">🛡️</span>
                <span class="safety-text">Paiement 100% sécurisé</span>
              </div>
              <div class="safety-item">
                <span class="safety-icon">↩️</span>
                <span class="safety-text">Retour gratuit sous 14 jours</span>
              </div>
              <div class="safety-item">
                <span class="safety-icon">📞</span>
                <span class="safety-text">Support client 7j/7</span>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Matériels similaires améliorés -->
    <div class="similar-products-section" v-if="similaires && similaires.length > 0">
      <div class="section-header">
        <h2 class="section-title">
          <span class="title-icon">✨</span>
          Vous aimerez aussi
        </h2>
        <router-link to="/catalogue" class="view-all-link">
          Voir tout le catalogue
          <span class="link-arrow">→</span>
        </router-link>
      </div>
      
      <div class="similar-products-grid">
        <div 
          v-for="similar in similaires" 
          :key="similar.id" 
          class="similar-product-card"
          @click="$router.push(`/materiels/${similar.id}`)"
        >
          <div class="product-image-wrapper">
            <img 
              :src="similar.main_photo || getImageUrl(similar.photos?.[0]?.url_photo) || '/placeholder.jpg'" 
              :alt="similar.nom" 
              @error="(e) => e.target.src = '/placeholder.jpg'"
              class="similar-product-image"
            >
            <div class="product-overlay">
              <button class="quick-view-btn">
                <span class="view-icon">👁️</span>
                Voir
              </button>
            </div>
            <div class="product-badge" v-if="similar.is_new">
              Nouveau
            </div>
          </div>
          
          <div class="product-info">
            <h4 class="product-title">{{ similar.nom }}</h4>
            <div class="product-category">{{ similar.categorie?.nom }}</div>
            
            <div class="product-price">
              <span class="price-amount">{{ similar.prix_formatted || formatPrice(similar.prix_journalier) }}</span>
              <span class="price-period">/jour</span>
            </div>
            
            <div class="product-stock" :class="{ 
              'in-stock': similar.stock_disponible > 0, 
              'out-of-stock': similar.stock_disponible === 0 
            }">
              {{ similar.stock_disponible > 0 ? `${similar.stock_disponible} dispo` : 'Épuisé' }}
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- FAQ Section -->
    <div class="faq-section">
      <div class="section-header">
        <h2 class="section-title">
          <span class="title-icon">❓</span>
          Questions fréquentes
        </h2>
      </div>
      
      <div class="faq-grid">
        <div class="faq-item">
          <div class="faq-question" @click="toggleFaq(0)">
            <span class="question-icon">💡</span>
            <span class="question-text">Quelle est la durée minimale de location ?</span>
            <span class="toggle-icon">{{ activeFaq === 0 ? '−' : '+' }}</span>
          </div>
          <div class="faq-answer" :class="{ 'active': activeFaq === 0 }">
            La durée minimale de location est de 1 jour. Pour les événements de longue durée, contactez-nous pour des tarifs préférentiels.
          </div>
        </div>
        
        <div class="faq-item">
          <div class="faq-question" @click="toggleFaq(1)">
            <span class="question-icon">🚚</span>
            <span class="question-text">Livrez-vous partout en France ?</span>
            <span class="toggle-icon">{{ activeFaq === 1 ? '−' : '+' }}</span>
          </div>
          <div class="faq-answer" :class="{ 'active': activeFaq === 1 }">
            Oui, nous livrons dans toute la France métropolitaine. Les frais de livraison sont calculés en fonction de votre localisation.
          </div>
        </div>
        
        <div class="faq-item">
          <div class="faq-question" @click="toggleFaq(2)">
            <span class="question-icon">🔄</span>
            <span class="question-text">Puis-je annuler ma réservation ?</span>
            <span class="toggle-icon">{{ activeFaq === 2 ? '−' : '+' }}</span>
          </div>
          <div class="faq-answer" :class="{ 'active': activeFaq === 2 }">
            Vous pouvez annuler gratuitement jusqu'à 48h avant la date de début de location. Passé ce délai, des frais d'annulation peuvent s'appliquer.
          </div>
        </div>
      </div>
    </div>

    <!-- Lightbox améliorée -->
    <div class="lightbox-overlay" v-if="lightboxOpen" @click="closeLightbox">
      <div class="lightbox-container" @click.stop>
        <button class="lightbox-close-btn" @click="closeLightbox">
          <span class="close-icon">✕</span>
        </button>
        
        <button class="lightbox-nav prev-nav" @click.stop="prevPhoto">
          <span class="nav-icon">‹</span>
        </button>
        
        <div class="lightbox-content">
          <img 
            :src="currentPhoto?.url_photo" 
            :alt="materiel.nom" 
            class="lightbox-image"
          />
          <div class="lightbox-info">
            <div class="image-counter">
              {{ currentPhotoIndex + 1 }} / {{ photos.length }}
            </div>
            <div class="image-description" v-if="photos[currentPhotoIndex]?.description">
              {{ photos[currentPhotoIndex]?.description }}
            </div>
          </div>
        </div>
        
        <button class="lightbox-nav next-nav" @click.stop="nextPhoto">
          <span class="nav-icon">›</span>
        </button>
        
        <div class="lightbox-thumbnails" v-if="photos.length > 1">
          <div 
            v-for="(photo, index) in photosWithUrls" 
            :key="photo.id || index"
            class="lightbox-thumbnail"
            :class="{ 'active': currentPhotoIndex === index }"
            @click="changePhoto(index)"
          >
            <img :src="photo.url_photo" :alt="`Vue ${index + 1}`" />
          </div>
        </div>
      </div>
    </div>

    <!-- Loading overlay -->
    <div v-if="loading" class="loading-overlay">
      <div class="loading-content">
        <div class="loading-spinner-large">
          <div class="spinner-ring"></div>
          <div class="spinner-ring"></div>
          <div class="spinner-ring"></div>
        </div>
        <p class="loading-message">Chargement des détails du produit...</p>
        <p class="loading-submessage">Un instant, c'est presque prêt !</p>
      </div>
    </div>
  </div>

  <!-- Écran d'erreur amélioré -->
  <div v-else-if="error" class="error-screen">
    <div class="error-content">
      <div class="error-illustration">
        <span class="error-emoji">😞</span>
        <div class="error-shape"></div>
      </div>
      <h2 class="error-title">Oups ! Produit introuvable</h2>
      <p class="error-message">{{ error }}</p>
      <div class="error-actions">
        <router-link to="/catalogue" class="error-btn primary-btn">
          <span class="btn-icon">🏠</span>
          Retour au catalogue
        </router-link>
        <button @click="retryLoad" class="error-btn secondary-btn">
          <span class="btn-icon">🔄</span>
          Réessayer
        </button>
      </div>
    </div>
  </div>

  <!-- Loading initial -->
  <div v-else class="initial-loading">
    <div class="loading-shimmer">
      <div class="shimmer-header"></div>
      <div class="shimmer-content">
        <div class="shimmer-gallery"></div>
        <div class="shimmer-info"></div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, computed, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import api from '@/services/axios'
import { getImageUrl } from '@/utils/imageHelper'

const route = useRoute()
const router = useRouter()

const materiel = ref(null)
const photos = ref([])
const similaires = ref([])
const loading = ref(true)
const error = ref(null)
const addingToCart = ref(false)
const imageLoading = ref(false)
const inWishlist = ref(false)

const currentPhotoIndex = ref(0)
const quantity = ref(1)
const lightboxOpen = ref(false)
const specsExpanded = ref(false)
const activeFaq = ref(null)
const thumbnailScroll = ref(0)

// Récupérer l'ID depuis la route
const materielId = route.params.id

// Photo courante avec URL complète
const currentPhoto = computed(() => {
  const photo = photos.value[currentPhotoIndex.value]
  if (!photo) return null
  return {
    ...photo,
    url_photo: getImageUrl(photo.url_photo)
  }
})

// Photos avec URLs complètes
const photosWithUrls = computed(() => {
  return photos.value.map(photo => ({
    ...photo,
    url_photo: getImageUrl(photo.url_photo)
  }))
})

// Caractéristiques extraites de la description
const features = computed(() => {
  if (!materiel.value?.description) return []
  const desc = materiel.value.description
  const features = desc.match(/[^.!?]+[.!?]/g) || []
  return features.slice(0, 4) // Limiter à 4 caractéristiques
})

// Prix formaté
const prixFormate = computed(() => {
  if (!materiel.value) return '0,00 €'
  return formatPrice(materiel.value.prix_journalier)
})

// Prix total formaté (avec remise)
const prixTotalFormate = computed(() => {
  if (!materiel.value) return '0,00 €'
  const total = materiel.value.prix_journalier * quantity.value
  const discounted = quantity.value > 3 ? total * 0.9 : total
  return formatPrice(discounted)
})

// Montant de la remise
const discountAmount = computed(() => {
  if (!materiel.value || quantity.value <= 3) return 0
  return materiel.value.prix_journalier * quantity.value * 0.1
})

// Économies
const savingsAmount = computed(() => {
  if (!materiel.value || quantity.value <= 1) return 0
  return materiel.value.prix_journalier * (quantity.value - 1) * 0.1
})

// Formater un prix numérique (sans symbole €)
const formatPriceNumber = (price) => {
  if (price === undefined || price === null || isNaN(price)) return '0,00'
  return new Intl.NumberFormat('fr-FR', {
    minimumFractionDigits: 2,
    maximumFractionDigits: 2
  }).format(price)
}

// Formater un prix complet
const formatPrice = (price) => {
  return new Intl.NumberFormat('fr-FR', {
    style: 'currency',
    currency: 'EUR',
    minimumFractionDigits: 2
  }).format(price)
}

// Formater une date
const formatDate = (dateString) => {
  if (!dateString) return ''
  const date = new Date(dateString)
  return date.toLocaleDateString('fr-FR', {
    day: 'numeric',
    month: 'long',
    year: 'numeric'
  })
}

// Obtenir l'icône de catégorie
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

// Charger les données du matériel
const loadMateriel = async () => {
  loading.value = true
  error.value = null
  
  try {
    const response = await api.get(`/materiels/${materielId}`)
    
    let materielData = null
    let similairesData = []
    
    if (response.data && response.data.success) {
      materielData = response.data.data
      similairesData = response.data.similaires || []
    } else if (response.data) {
      materielData = response.data
    }
    
    if (!materielData) {
      throw new Error('Matériel non trouvé')
    }
    
    materiel.value = materielData
    photos.value = materielData.photos || []
    similaires.value = similairesData
    
    // Si aucune photo, ajouter une photo par défaut
    if (photos.value.length === 0) {
      photos.value = [{ 
        id: 0, 
        url_photo: getDefaultImage(materielData.categorie_id),
        is_default: true 
      }]
    }
    
    // Vérifier si dans la wishlist
    inWishlist.value = localStorage.getItem(`wishlist_${materielId}`) === 'true'
    
    // Réinitialiser la quantité à 1
    quantity.value = 1
    
  } catch (err) {
    console.error('Erreur chargement matériel:', err)
    error.value = err.response?.data?.message || 'Matériel introuvable. Veuillez réessayer.'
  } finally {
    loading.value = false
  }
}

// Image par défaut
const getDefaultImage = (categorieId) => {
  switch(categorieId) {
    case 1: return '/images/materiels/default-chair.jpg'
    case 2: return '/images/materiels/default-table.jpg'
    case 3: return '/images/materiels/default-deco.jpg'
    default: return '/placeholder.jpg'
  }
}

// Navigation photos
const changePhoto = (index) => {
  if (index >= 0 && index < photos.value.length) {
    currentPhotoIndex.value = index
  }
}

const nextPhoto = () => {
  currentPhotoIndex.value = (currentPhotoIndex.value + 1) % photos.value.length
}

const prevPhoto = () => {
  currentPhotoIndex.value = currentPhotoIndex.value === 0 
    ? photos.value.length - 1 
    : currentPhotoIndex.value - 1
}

// Navigation miniatures
const scrollThumbnails = (direction) => {
  const newScroll = thumbnailScroll.value + direction
  if (newScroll >= 0 && newScroll <= photos.value.length - 4) {
    thumbnailScroll.value = newScroll
  }
}

// Lightbox
const openLightbox = () => {
  if (photos.value.length > 0) {
    lightboxOpen.value = true
  }
}

const closeLightbox = () => {
  lightboxOpen.value = false
}

// Gestion quantité
const incrementQuantity = () => {
  if (quantity.value < materiel.value.stock_disponible) {
    quantity.value++
  }
}

const decrementQuantity = () => {
  if (quantity.value > 1) {
    quantity.value--
  }
}

const setQuantity = (qty) => {
  if (qty >= 1 && qty <= materiel.value.stock_disponible) {
    quantity.value = qty
  }
}

const validateQuantity = () => {
  if (quantity.value < 1) {
    quantity.value = 1
  }
  if (materiel.value && quantity.value > materiel.value.stock_disponible) {
    quantity.value = materiel.value.stock_disponible
  }
}

// Gérer les changements de quantité
watch(quantity, (newValue) => {
  validateQuantity()
})

// Panier
const addToCart = async () => {
  if (materiel.value.stock_disponible === 0 || quantity.value > materiel.value.stock_disponible) return
  
  addingToCart.value = true
  
  try {
    const item = {
      materiel_id: materiel.value.id,
      nom: materiel.value.nom,
      prix_journalier: materiel.value.prix_journalier,
      quantite: quantity.value,
      photo: currentPhoto.value?.url_photo,
      categorie: materiel.value.categorie?.nom
    }
    
    // Animation d'ajout
    await new Promise(resolve => setTimeout(resolve, 800))
    
    // Émettre un événement
    window.dispatchEvent(new CustomEvent('cartUpdated', {
      detail: { item }
    }))
    
    // Notification
    showNotification(`🎉 ${quantity.value} × "${materiel.value.nom}" ajouté au panier !`, 'success')
    
  } catch (err) {
    console.error('Erreur ajout panier:', err)
    showNotification('❌ Erreur lors de l\'ajout au panier', 'error')
  } finally {
    addingToCart.value = false
  }
}

// Wishlist
const addToWishlist = () => {
  inWishlist.value = !inWishlist.value
  localStorage.setItem(`wishlist_${materielId}`, inWishlist.value)
  
  if (inWishlist.value) {
    showNotification('❤️ Ajouté à votre liste de souhaits', 'success')
  } else {
    showNotification('💔 Retiré de votre liste de souhaits', 'info')
  }
}

// Réservation
const reserveNow = () => {
  if (materiel.value.stock_disponible === 0 || quantity.value > materiel.value.stock_disponible) return
  
  router.push({
    path: '/reservation',
    query: { 
      materielId: materiel.value.id,
      quantity: quantity.value 
    }
  })
}

// Partage
const shareProduct = () => {
  if (navigator.share) {
    navigator.share({
      title: materiel.value.nom,
      text: `Découvrez "${materiel.value.nom}" sur notre catalogue de location !`,
      url: window.location.href
    })
  } else {
    navigator.clipboard.writeText(window.location.href)
    showNotification('📋 Lien copié dans le presse-papiers !', 'success')
  }
}

// FAQ
const toggleFaq = (index) => {
  activeFaq.value = activeFaq.value === index ? null : index
}

// Spécifications
const toggleSpecs = () => {
  specsExpanded.value = !specsExpanded.value
}

// Réessayer
const retryLoad = () => {
  error.value = null
  loadMateriel()
}

// Notification
const showNotification = (message, type = 'info') => {
  const event = new CustomEvent('showNotification', {
    detail: { message, type }
  })
  window.dispatchEvent(event)
}

// Initialisation
onMounted(() => {
  loadMateriel()
})
</script>

<style scoped>
/* Styles globaux améliorés */
.materiel-detail-container {
  max-width: 1400px;
  margin: 0 auto;
  padding: 0 20px 60px;
  min-height: 100vh;
  animation: fadeIn 0.5s ease;
}

@keyframes fadeIn {
  from { opacity: 0; transform: translateY(20px); }
  to { opacity: 1; transform: translateY(0); }
}

/* Fil d'Ariane amélioré */
.breadcrumb-nav {
  background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%);
  border-radius: 12px;
  padding: 15px 25px;
  margin-bottom: 30px;
  box-shadow: 0 2px 15px rgba(0, 0, 0, 0.05);
}

.breadcrumb-content {
  display: flex;
  align-items: center;
  flex-wrap: wrap;
  gap: 10px;
  font-size: 14px;
}

.breadcrumb-link {
  display: flex;
  align-items: center;
  gap: 8px;
  color: #667eea;
  text-decoration: none;
  font-weight: 500;
  padding: 6px 12px;
  border-radius: 8px;
  transition: all 0.3s;
}

.breadcrumb-link:hover {
  background: rgba(102, 126, 234, 0.1);
  transform: translateX(3px);
}

.breadcrumb-icon {
  font-size: 16px;
}

.breadcrumb-divider {
  color: #a0aec0;
  font-size: 18px;
  font-weight: 300;
}

.breadcrumb-current {
  display: flex;
  align-items: center;
  gap: 8px;
  color: #2d3748;
  font-weight: 600;
  padding: 6px 12px;
  background: white;
  border-radius: 8px;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
}

.current-icon {
  color: #667eea;
}

/* Contenu principal */
.materiel-content {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 40px;
  margin-bottom: 80px;
}

@media (max-width: 992px) {
  .materiel-content {
    grid-template-columns: 1fr;
    gap: 30px;
  }
}

/* Galerie photos améliorée */
.photo-gallery-section {
  background: white;
  border-radius: 20px;
  overflow: hidden;
  box-shadow: 0 10px 40px rgba(0, 0, 0, 0.08);
  border: 1px solid rgba(0, 0, 0, 0.05);
}

.gallery-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 20px 25px;
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  color: white;
}

.gallery-title {
  font-size: 1.2rem;
  font-weight: 600;
  margin: 0;
  display: flex;
  align-items: center;
  gap: 10px;
}

.photo-counter {
  background: rgba(255, 255, 255, 0.2);
  padding: 4px 12px;
  border-radius: 20px;
  font-size: 0.9rem;
  font-weight: 500;
}

.gallery-container {
  padding: 25px;
}

.main-photo-wrapper {
  position: relative;
  margin-bottom: 20px;
}

.main-photo {
  position: relative;
  background: linear-gradient(135deg, #f7fafc 0%, #edf2f7 100%);
  border-radius: 15px;
  overflow: hidden;
  cursor: zoom-in;
  transition: transform 0.3s ease;
}

.main-photo:hover {
  transform: scale(1.01);
}

.main-photo:hover .image-overlay {
  opacity: 1;
}

.product-image {
  width: 100%;
  height: 400px;
  object-fit: contain;
  display: block;
  transition: transform 0.5s ease;
}

.product-image.loading {
  opacity: 0.5;
}

.image-overlay {
  position: absolute;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: rgba(0, 0, 0, 0.3);
  display: flex;
  align-items: center;
  justify-content: center;
  opacity: 0;
  transition: opacity 0.3s ease;
}

.zoom-indicator {
  background: rgba(255, 255, 255, 0.9);
  padding: 12px 20px;
  border-radius: 30px;
  display: flex;
  align-items: center;
  gap: 10px;
  font-weight: 500;
  color: #2d3748;
  transform: translateY(10px);
  transition: transform 0.3s ease;
}

.main-photo:hover .zoom-indicator {
  transform: translateY(0);
}

.zoom-icon {
  font-size: 1.2rem;
}

/* Badges produits */
.product-badges {
  position: absolute;
  top: 20px;
  left: 20px;
  display: flex;
  flex-direction: column;
  gap: 10px;
  z-index: 10;
}

.stock-badge,
.new-badge,
.promo-badge {
  display: flex;
  align-items: center;
  gap: 8px;
  padding: 10px 16px;
  border-radius: 25px;
  font-size: 0.9rem;
  font-weight: 600;
  color: white;
  backdrop-filter: blur(10px);
  border: 1px solid rgba(255, 255, 255, 0.2);
  box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
  transform: translateX(-10px);
  opacity: 0;
  animation: slideInLeft 0.5s ease forwards;
}

@keyframes slideInLeft {
  to {
    transform: translateX(0);
    opacity: 1;
  }
}

.stock-badge {
  animation-delay: 0.1s;
}

.new-badge {
  animation-delay: 0.2s;
}

.promo-badge {
  animation-delay: 0.3s;
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
  font-size: 1rem;
}

.badge-text {
  white-space: nowrap;
}

/* Miniatures améliorées */
.thumbnails-container {
  position: relative;
}

.thumbnails-scroll {
  display: flex;
  gap: 12px;
  overflow-x: auto;
  padding: 10px 0;
  scroll-behavior: smooth;
  scrollbar-width: none;
}

.thumbnails-scroll::-webkit-scrollbar {
  display: none;
}

.thumbnail-card {
  flex-shrink: 0;
  width: 100px;
  height: 100px;
  cursor: pointer;
  transition: all 0.3s ease;
}

.thumbnail-card.active {
  transform: scale(1.05);
}

.thumbnail-wrapper {
  position: relative;
  width: 100%;
  height: 100%;
  border-radius: 12px;
  overflow: hidden;
  border: 3px solid transparent;
  transition: all 0.3s ease;
}

.thumbnail-card.active .thumbnail-wrapper {
  border-color: #667eea;
  box-shadow: 0 4px 20px rgba(102, 126, 234, 0.3);
}

.thumbnail-wrapper img {
  width: 100%;
  height: 100%;
  object-fit: cover;
}

.thumbnail-overlay {
  position: absolute;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: rgba(102, 126, 234, 0);
  display: flex;
  align-items: center;
  justify-content: center;
  transition: all 0.3s ease;
}

.thumbnail-overlay.active {
  background: rgba(102, 126, 234, 0.7);
}

.thumbnail-card:hover .thumbnail-overlay {
  background: rgba(102, 126, 234, 0.5);
}

.view-icon {
  color: white;
  font-size: 1.2rem;
  opacity: 0;
  transform: scale(0.8);
  transition: all 0.3s ease;
}

.thumbnail-card:hover .view-icon,
.thumbnail-card.active .view-icon {
  opacity: 1;
  transform: scale(1);
}

.thumbnails-controls {
  display: flex;
  justify-content: center;
  gap: 20px;
  margin-top: 15px;
}

.thumb-btn {
  width: 40px;
  height: 40px;
  border: 2px solid #e2e8f0;
  background: white;
  border-radius: 50%;
  font-size: 1.2rem;
  cursor: pointer;
  transition: all 0.3s ease;
  display: flex;
  align-items: center;
  justify-content: center;
}

.thumb-btn:hover:not(:disabled) {
  background: #667eea;
  color: white;
  border-color: #667eea;
  transform: scale(1.1);
}

.thumb-btn:disabled {
  opacity: 0.3;
  cursor: not-allowed;
}

/* Informations produit améliorées */
.materiel-info-section {
  display: flex;
  flex-direction: column;
  gap: 25px;
}

.product-header {
  background: white;
  border-radius: 15px;
  padding: 25px;
  box-shadow: 0 4px 20px rgba(0, 0, 0, 0.05);
}

.title-wrapper {
  margin-bottom: 20px;
}

.materiel-title {
  font-size: 2.2rem;
  font-weight: 800;
  color: #2d3748;
  margin: 0 0 10px 0;
  line-height: 1.2;
  background: linear-gradient(135deg, #2d3748 0%, #4a5568 100%);
  -webkit-background-clip: text;
  -webkit-text-fill-color: transparent;
}

.product-rating {
  display: flex;
  align-items: center;
  gap: 10px;
}

.stars {
  display: flex;
  gap: 2px;
}

.star {
  color: #e2e8f0;
  font-size: 1.2rem;
}

.star.filled {
  color: #f59e0b;
}

.rating-text {
  color: #718096;
  font-size: 0.9rem;
}

.category-wrapper {
  display: flex;
  justify-content: space-between;
  align-items: center;
  flex-wrap: wrap;
  gap: 15px;
}

.categorie-tag {
  display: flex;
  align-items: center;
  gap: 10px;
  background: linear-gradient(135deg, #f0f4ff 0%, #e6edff 100%);
  padding: 10px 20px;
  border-radius: 25px;
  color: #667eea;
  font-weight: 600;
}

.tag-icon {
  font-size: 1.2rem;
}

.product-id {
  display: flex;
  align-items: center;
  gap: 8px;
  color: #718096;
  font-size: 0.9rem;
}

.id-label {
  font-weight: 500;
}

.id-value {
  background: #f7fafc;
  padding: 4px 10px;
  border-radius: 6px;
  font-family: 'Courier New', monospace;
  font-weight: 600;
}

/* Carte prix améliorée */
.price-card {
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  border-radius: 15px;
  padding: 30px;
  color: white;
  box-shadow: 0 10px 30px rgba(102, 126, 234, 0.3);
}

.price-content {
  display: flex;
  align-items: baseline;
  gap: 10px;
  margin-bottom: 15px;
}

.price-display {
  display: flex;
  align-items: baseline;
  gap: 5px;
}

.price-currency {
  font-size: 1.8rem;
  font-weight: 600;
  opacity: 0.9;
}

.price-amount {
  font-size: 3.5rem;
  font-weight: 800;
  line-height: 1;
}

.price-details {
  display: flex;
  flex-direction: column;
  gap: 5px;
}

.price-period {
  font-size: 1.1rem;
  opacity: 0.9;
}

.price-note {
  display: flex;
  align-items: center;
  gap: 8px;
  font-size: 0.9rem;
  background: rgba(255, 255, 255, 0.2);
  padding: 6px 12px;
  border-radius: 20px;
  backdrop-filter: blur(10px);
}

.note-icon {
  font-size: 1rem;
}

.saving-badge {
  display: flex;
  align-items: center;
  gap: 10px;
  background: rgba(255, 255, 255, 0.9);
  color: #2d3748;
  padding: 12px 20px;
  border-radius: 12px;
  font-weight: 600;
  animation: pulse 2s infinite;
}

@keyframes pulse {
  0%, 100% { transform: scale(1); }
  50% { transform: scale(1.02); }
}

.saving-icon {
  color: #10b981;
  font-size: 1.2rem;
}

/* Carte caractéristiques */
.specs-card,
.description-card,
.reservation-card {
  background: white;
  border-radius: 15px;
  padding: 25px;
  box-shadow: 0 4px 20px rgba(0, 0, 0, 0.05);
  border: 1px solid rgba(0, 0, 0, 0.05);
}

.specs-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 20px;
}

.specs-title {
  font-size: 1.3rem;
  font-weight: 700;
  color: #2d3748;
  margin: 0;
  display: flex;
  align-items: center;
  gap: 10px;
}

.title-icon {
  font-size: 1.2rem;
}

.specs-expand-btn {
  background: #f7fafc;
  color: #4a5568;
  border: 2px solid #e2e8f0;
  padding: 8px 16px;
  border-radius: 8px;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.3s;
}

.specs-expand-btn:hover {
  background: #edf2f7;
  border-color: #cbd5e0;
}

.specs-grid {
  display: grid;
  grid-template-columns: repeat(2, 1fr);
  gap: 15px;
  max-height: 300px;
  overflow: hidden;
  transition: max-height 0.5s ease;
}

.specs-grid.expanded {
  max-height: 1000px;
}

@media (max-width: 768px) {
  .specs-grid {
    grid-template-columns: 1fr;
  }
}

.spec-item {
  display: flex;
  align-items: center;
  gap: 15px;
  padding: 15px;
  background: #f8fafc;
  border-radius: 12px;
  border: 1px solid #e2e8f0;
  transition: all 0.3s ease;
}

.spec-item:hover {
  background: #edf2f7;
  transform: translateX(5px);
  border-color: #cbd5e0;
}

.spec-icon-box {
  width: 50px;
  height: 50px;
  background: white;
  border-radius: 12px;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 1.5rem;
  box-shadow: 0 4px 15px rgba(0, 0, 0, 0.05);
}

.spec-content {
  flex: 1;
}

.spec-label {
  font-size: 0.8rem;
  color: #718096;
  text-transform: uppercase;
  letter-spacing: 0.5px;
  margin-bottom: 4px;
  font-weight: 600;
}

.spec-value {
  font-size: 1.1rem;
  color: #2d3748;
  font-weight: 600;
}

.spec-value.low-stock {
  color: #f59e0b;
}

.spec-value.out-of-stock {
  color: #ef4444;
}

.stock-warning {
  font-size: 0.8rem;
  color: #f59e0b;
  margin-top: 4px;
  display: flex;
  align-items: center;
  gap: 5px;
}

/* Description */
.description-title {
  font-size: 1.3rem;
  font-weight: 700;
  color: #2d3748;
  margin: 0 0 20px 0;
  display: flex;
  align-items: center;
  gap: 10px;
}

.description-content {
  line-height: 1.7;
  color: #4a5568;
}

.description-features {
  margin-top: 20px;
  padding-top: 20px;
  border-top: 1px solid #e2e8f0;
}

.feature-item {
  display: flex;
  align-items: center;
  gap: 10px;
  margin-bottom: 10px;
  color: #4a5568;
}

.feature-icon {
  color: #10b981;
  font-weight: bold;
}

/* Réservation améliorée */
.reservation-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 25px;
  flex-wrap: wrap;
  gap: 15px;
}

.reservation-title {
  font-size: 1.3rem;
  font-weight: 700;
  color: #2d3748;
  margin: 0;
  display: flex;
  align-items: center;
  gap: 10px;
}

.delivery-info {
  display: flex;
  align-items: center;
  gap: 8px;
  background: #f0f7ff;
  color: #2575fc;
  padding: 8px 16px;
  border-radius: 20px;
  font-weight: 500;
}

.delivery-icon {
  font-size: 1.1rem;
}

.reservation-body {
  display: flex;
  flex-direction: column;
  gap: 25px;
}

/* Sélecteur quantité */
.quantity-selector-card {
  background: #f8fafc;
  border-radius: 15px;
  padding: 20px;
  border: 2px solid #e2e8f0;
}

.quantity-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 20px;
  flex-wrap: wrap;
  gap: 15px;
}

.quantity-label {
  font-size: 1.1rem;
  font-weight: 600;
  color: #2d3748;
}

.quantity-badges {
  display: flex;
  gap: 10px;
}

.quantity-badge {
  width: 40px;
  height: 40px;
  display: flex;
  align-items: center;
  justify-content: center;
  background: white;
  border: 2px solid #e2e8f0;
  border-radius: 10px;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.3s;
}

.quantity-badge:hover:not(:disabled),
.quantity-badge.active {
  background: #667eea;
  color: white;
  border-color: #667eea;
  transform: scale(1.1);
}

.quantity-badge:disabled {
  opacity: 0.3;
  cursor: not-allowed;
}

.quantity-controls {
  display: flex;
  flex-direction: column;
  gap: 15px;
}

.quantity-stepper {
  display: flex;
  align-items: center;
  gap: 10px;
  max-width: 300px;
}

.stepper-btn {
  width: 50px;
  height: 50px;
  border: 2px solid #e2e8f0;
  background: white;
  border-radius: 12px;
  font-size: 1.5rem;
  cursor: pointer;
  transition: all 0.3s;
  display: flex;
  align-items: center;
  justify-content: center;
}

.stepper-btn:hover:not(:disabled) {
  background: #667eea;
  color: white;
  border-color: #667eea;
  transform: scale(1.05);
}

.stepper-btn:disabled {
  opacity: 0.3;
  cursor: not-allowed;
}

.minus-btn {
  color: #ef4444;
}

.plus-btn {
  color: #10b981;
}

.quantity-input-wrapper {
  flex: 1;
  position: relative;
}

.quantity-input {
  width: 100%;
  height: 50px;
  border: 2px solid #e2e8f0;
  border-radius: 12px;
  text-align: center;
  font-size: 1.5rem;
  font-weight: 700;
  color: #2d3748;
  background: white;
}

.quantity-input:focus {
  outline: none;
  border-color: #667eea;
  box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
}

.quantity-units {
  position: absolute;
  right: 15px;
  top: 50%;
  transform: translateY(-50%);
  color: #a0aec0;
  font-size: 0.9rem;
  pointer-events: none;
}

.quantity-limits {
  display: flex;
  justify-content: space-between;
  color: #718096;
  font-size: 0.9rem;
}

/* Récapitulatif */
.summary-card {
  background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%);
  border-radius: 15px;
  padding: 25px;
  border: 2px dashed #cbd5e0;
}

.summary-header {
  margin-bottom: 20px;
}

.summary-title {
  font-size: 1.2rem;
  font-weight: 700;
  color: #2d3748;
  margin: 0;
  display: flex;
  align-items: center;
  gap: 10px;
}

.summary-items {
  display: flex;
  flex-direction: column;
  gap: 15px;
  margin-bottom: 20px;
}

.summary-item {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 12px 0;
  border-bottom: 1px solid #e2e8f0;
}

.summary-item:last-child {
  border-bottom: none;
}

.item-label {
  color: #4a5568;
  display: flex;
  align-items: center;
  gap: 8px;
}

.item-value {
  font-weight: 600;
  color: #2d3748;
}

.item-value.discount {
  color: #ef4444;
}

.item-value.savings {
  color: #10b981;
}

.savings-icon {
  color: #10b981;
}

.summary-total {
  background: white;
  border-radius: 12px;
  padding: 20px;
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin: 20px 0;
}

.total-label {
  font-size: 1.1rem;
  color: #2d3748;
  display: flex;
  align-items: center;
  gap: 10px;
}

.total-icon {
  font-size: 1.3rem;
  color: #667eea;
}

.total-amount {
  display: flex;
  flex-direction: column;
  align-items: flex-end;
}

.total-price {
  font-size: 2rem;
  font-weight: 800;
  color: #10b981;
  line-height: 1;
}

.total-period {
  font-size: 0.9rem;
  color: #718096;
  margin-top: 5px;
}

.summary-note {
  display: flex;
  align-items: center;
  gap: 10px;
  background: rgba(245, 158, 11, 0.1);
  color: #d97706;
  padding: 12px 20px;
  border-radius: 10px;
  font-size: 0.9rem;
  border-left: 4px solid #f59e0b;
}

.note-icon {
  font-size: 1.1rem;
}

/* Actions améliorées */
.action-buttons-grid {
  display: grid;
  grid-template-columns: repeat(2, 1fr);
  gap: 15px;
}

@media (max-width: 768px) {
  .action-buttons-grid {
    grid-template-columns: 1fr;
  }
}

.action-btn {
  padding: 18px;
  border: none;
  border-radius: 12px;
  font-size: 1rem;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.3s ease;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 12px;
  position: relative;
  overflow: hidden;
}

.action-btn::before {
  content: '';
  position: absolute;
  top: 0;
  left: -100%;
  width: 100%;
  height: 100%;
  background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.2), transparent);
  transition: left 0.5s;
}

.action-btn:hover::before {
  left: 100%;
}

.cart-btn {
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  color: white;
  grid-column: span 2;
}

@media (max-width: 768px) {
  .cart-btn {
    grid-column: span 1;
  }
}

.cart-btn:hover:not(.disabled):not(.loading) {
  transform: translateY(-3px);
  box-shadow: 0 12px 30px rgba(102, 126, 234, 0.4);
}

.wishlist-btn {
  background: white;
  color: #4a5568;
  border: 2px solid #e2e8f0;
}

.wishlist-btn:hover,
.wishlist-btn.active {
  background: linear-gradient(135deg, #f56565 0%, #ed64a6 100%);
  color: white;
  border-color: #f56565;
  transform: translateY(-2px);
}

.reserve-btn {
  background: linear-gradient(135deg, #10b981 0%, #34d399 100%);
  color: white;
}

.reserve-btn:hover:not(.disabled) {
  transform: translateY(-3px);
  box-shadow: 0 12px 30px rgba(16, 185, 129, 0.4);
}

.share-btn {
  background: white;
  color: #4a5568;
  border: 2px solid #e2e8f0;
}

.share-btn:hover {
  background: #667eea;
  color: white;
  border-color: #667eea;
  transform: translateY(-2px);
}

.action-btn.disabled {
  background: #cbd5e0;
  cursor: not-allowed;
  opacity: 0.7;
  transform: none !important;
  box-shadow: none !important;
}

.action-btn.disabled::before {
  display: none;
}

.loading-spinner-small {
  width: 20px;
  height: 20px;
  border: 2px solid rgba(255, 255, 255, 0.3);
  border-radius: 50%;
  border-top-color: white;
  animation: spin 0.8s linear infinite;
}

@keyframes spin {
  to { transform: rotate(360deg); }
}

.safety-info {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: 15px;
  margin-top: 20px;
}

@media (max-width: 768px) {
  .safety-info {
    grid-template-columns: 1fr;
  }
}

.safety-item {
  display: flex;
  align-items: center;
  gap: 10px;
  background: #f8fafc;
  padding: 12px;
  border-radius: 10px;
  color: #4a5568;
  font-size: 0.9rem;
}

.safety-icon {
  font-size: 1.2rem;
}

/* Produits similaires améliorés */
.similar-products-section,
.faq-section {
  margin-top: 60px;
  padding-top: 40px;
  border-top: 1px solid #e2e8f0;
}

.section-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 30px;
  flex-wrap: wrap;
  gap: 20px;
}

.section-title {
  font-size: 1.8rem;
  font-weight: 800;
  color: #2d3748;
  margin: 0;
  display: flex;
  align-items: center;
  gap: 10px;
}

.view-all-link {
  display: flex;
  align-items: center;
  gap: 8px;
  color: #667eea;
  text-decoration: none;
  font-weight: 600;
  padding: 10px 20px;
  background: #f0f4ff;
  border-radius: 25px;
  transition: all 0.3s;
}

.view-all-link:hover {
  background: #667eea;
  color: white;
  transform: translateX(5px);
}

.link-arrow {
  font-size: 1.2rem;
  transition: transform 0.3s;
}

.view-all-link:hover .link-arrow {
  transform: translateX(3px);
}

.similar-products-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
  gap: 25px;
}

.similar-product-card {
  background: white;
  border-radius: 15px;
  overflow: hidden;
  box-shadow: 0 5px 20px rgba(0, 0, 0, 0.08);
  cursor: pointer;
  transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
  position: relative;
}

.similar-product-card:hover {
  transform: translateY(-10px) scale(1.02);
  box-shadow: 0 20px 40px rgba(0, 0, 0, 0.15);
}

.product-image-wrapper {
  position: relative;
  height: 180px;
  overflow: hidden;
}

.similar-product-image {
  width: 100%;
  height: 100%;
  object-fit: cover;
  transition: transform 0.6s ease;
}

.similar-product-card:hover .similar-product-image {
  transform: scale(1.1);
}

.product-overlay {
  position: absolute;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: rgba(0, 0, 0, 0.5);
  display: flex;
  align-items: center;
  justify-content: center;
  opacity: 0;
  transition: opacity 0.3s ease;
}

.similar-product-card:hover .product-overlay {
  opacity: 1;
}

.quick-view-btn {
  background: white;
  color: #2d3748;
  border: none;
  padding: 10px 20px;
  border-radius: 25px;
  font-weight: 600;
  display: flex;
  align-items: center;
  gap: 8px;
  cursor: pointer;
  transform: translateY(20px);
  transition: all 0.3s ease;
}

.similar-product-card:hover .quick-view-btn {
  transform: translateY(0);
}

.product-badge {
  position: absolute;
  top: 15px;
  left: 15px;
  background: linear-gradient(135deg, #8b5cf6 0%, #a78bfa 100%);
  color: white;
  padding: 6px 12px;
  border-radius: 20px;
  font-size: 0.8rem;
  font-weight: 700;
  z-index: 2;
}

.product-info {
  padding: 20px;
}

.product-title {
  font-size: 1.1rem;
  font-weight: 700;
  color: #2d3748;
  margin: 0 0 8px 0;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

.product-category {
  font-size: 0.9rem;
  color: #718096;
  margin-bottom: 10px;
}

.product-price {
  display: flex;
  align-items: baseline;
  gap: 5px;
  margin-bottom: 10px;
}

.price-amount {
  font-size: 1.3rem;
  font-weight: 800;
  color: #10b981;
}

.price-period {
  font-size: 0.9rem;
  color: #a0aec0;
}

.product-stock {
  display: inline-block;
  padding: 4px 10px;
  border-radius: 15px;
  font-size: 0.8rem;
  font-weight: 700;
}

.product-stock.in-stock {
  background: #d1fae5;
  color: #065f46;
}

.product-stock.out-of-stock {
  background: #fee2e2;
  color: #991b1b;
}

/* FAQ Section */
.faq-grid {
  display: flex;
  flex-direction: column;
  gap: 15px;
}

.faq-item {
  background: white;
  border-radius: 12px;
  overflow: hidden;
  box-shadow: 0 4px 15px rgba(0, 0, 0, 0.05);
  border: 1px solid #e2e8f0;
}

.faq-question {
  padding: 20px;
  display: flex;
  align-items: center;
  justify-content: space-between;
  cursor: pointer;
  transition: all 0.3s;
}

.faq-question:hover {
  background: #f8fafc;
}

.question-icon {
  font-size: 1.2rem;
  margin-right: 12px;
}

.question-text {
  flex: 1;
  font-weight: 600;
  color: #2d3748;
}

.toggle-icon {
  font-size: 1.5rem;
  color: #667eea;
  font-weight: 300;
  transition: transform 0.3s;
}

.faq-question:hover .toggle-icon {
  transform: scale(1.2);
}

.faq-answer {
  padding: 0 20px;
  max-height: 0;
  overflow: hidden;
  color: #4a5568;
  line-height: 1.6;
  transition: all 0.3s ease;
}

.faq-answer.active {
  padding: 0 20px 20px;
  max-height: 200px;
}

/* Lightbox améliorée */
.lightbox-overlay {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: rgba(0, 0, 0, 0.95);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 2000;
  animation: fadeIn 0.3s ease;
}

.lightbox-container {
  position: relative;
  width: 90%;
  height: 90%;
  background: transparent;
  display: flex;
  flex-direction: column;
  align-items: center;
}

.lightbox-close-btn {
  position: absolute;
  top: 20px;
  right: 20px;
  width: 50px;
  height: 50px;
  background: rgba(255, 255, 255, 0.1);
  border: none;
  border-radius: 50%;
  color: white;
  font-size: 1.5rem;
  cursor: pointer;
  z-index: 10;
  transition: all 0.3s;
  display: flex;
  align-items: center;
  justify-content: center;
}

.lightbox-close-btn:hover {
  background: rgba(255, 255, 255, 0.2);
  transform: rotate(90deg);
}

.close-icon {
  font-size: 1.8rem;
  font-weight: 300;
}

.lightbox-nav {
  position: absolute;
  top: 50%;
  transform: translateY(-50%);
  width: 60px;
  height: 60px;
  background: rgba(255, 255, 255, 0.1);
  border: none;
  border-radius: 50%;
  color: white;
  font-size: 2rem;
  cursor: pointer;
  z-index: 10;
  transition: all 0.3s;
  display: flex;
  align-items: center;
  justify-content: center;
}

.lightbox-nav:hover {
  background: rgba(255, 255, 255, 0.2);
  transform: translateY(-50%) scale(1.1);
}

.prev-nav {
  left: 20px;
}

.next-nav {
  right: 20px;
}

.lightbox-content {
  flex: 1;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  width: 100%;
}

.lightbox-image {
  max-width: 100%;
  max-height: 80vh;
  object-fit: contain;
  border-radius: 10px;
  box-shadow: 0 10px 40px rgba(0, 0, 0, 0.3);
}

.lightbox-info {
  margin-top: 20px;
  text-align: center;
  color: white;
}

.image-counter {
  font-size: 1rem;
  opacity: 0.8;
  margin-bottom: 5px;
}

.image-description {
  font-size: 0.9rem;
  opacity: 0.7;
  max-width: 600px;
  margin: 0 auto;
}

.lightbox-thumbnails {
  display: flex;
  gap: 10px;
  margin-top: 20px;
  padding: 10px;
  overflow-x: auto;
  max-width: 100%;
}

.lightbox-thumbnail {
  width: 80px;
  height: 80px;
  border-radius: 8px;
  overflow: hidden;
  cursor: pointer;
  border: 2px solid transparent;
  flex-shrink: 0;
  transition: all 0.3s;
}

.lightbox-thumbnail:hover {
  border-color: rgba(255, 255, 255, 0.3);
}

.lightbox-thumbnail.active {
  border-color: #667eea;
  transform: scale(1.05);
}

.lightbox-thumbnail img {
  width: 100%;
  height: 100%;
  object-fit: cover;
}

/* Loading overlay amélioré */
.loading-overlay {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: rgba(255, 255, 255, 0.95);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 1000;
  backdrop-filter: blur(5px);
}

.loading-content {
  text-align: center;
  animation: fadeIn 0.5s ease;
}

.loading-spinner-large {
  position: relative;
  width: 100px;
  height: 100px;
  margin: 0 auto 20px;
}

.spinner-ring {
  position: absolute;
  width: 100%;
  height: 100%;
  border-radius: 50%;
  border: 4px solid transparent;
  animation: spin 1.5s linear infinite;
}

.spinner-ring:nth-child(1) {
  border-top-color: #667eea;
  animation-delay: 0s;
}

.spinner-ring:nth-child(2) {
  border-top-color: #764ba2;
  animation-delay: 0.5s;
}

.spinner-ring:nth-child(3) {
  border-top-color: #667eea;
  animation-delay: 1s;
}

.loading-message {
  font-size: 1.2rem;
  color: #2d3748;
  margin-bottom: 5px;
  font-weight: 600;
}

.loading-submessage {
  color: #718096;
  font-size: 0.9rem;
}

/* Écran d'erreur amélioré */
.error-screen {
  display: flex;
  align-items: center;
  justify-content: center;
  min-height: 80vh;
  padding: 40px 20px;
}

.error-content {
  text-align: center;
  max-width: 500px;
  animation: fadeIn 0.5s ease;
}

.error-illustration {
  position: relative;
  margin-bottom: 30px;
}

.error-emoji {
  font-size: 5rem;
  display: block;
  margin-bottom: 20px;
  animation: bounce 2s infinite;
}

@keyframes bounce {
  0%, 100% { transform: translateY(0); }
  50% { transform: translateY(-10px); }
}

.error-shape {
  position: absolute;
  top: 50%;
  left: 50%;
  transform: translate(-50%, -50%);
  width: 200px;
  height: 200px;
  background: linear-gradient(135deg, rgba(102, 126, 234, 0.1), rgba(118, 75, 162, 0.1));
  border-radius: 50%;
  z-index: -1;
}

.error-title {
  font-size: 2rem;
  font-weight: 800;
  color: #2d3748;
  margin-bottom: 15px;
}

.error-message {
  color: #718096;
  line-height: 1.6;
  margin-bottom: 30px;
}

.error-actions {
  display: flex;
  gap: 15px;
  justify-content: center;
}

.error-btn {
  padding: 14px 28px;
  border-radius: 12px;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.3s;
  display: flex;
  align-items: center;
  gap: 10px;
  text-decoration: none;
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

/* Loading initial */
.initial-loading {
  min-height: 80vh;
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 40px 20px;
}

.loading-shimmer {
  width: 100%;
  max-width: 1200px;
}

.shimmer-header {
  height: 30px;
  background: linear-gradient(90deg, #f0f0f0 25%, #e0e0e0 50%, #f0f0f0 75%);
  border-radius: 8px;
  margin-bottom: 30px;
  animation: shimmer 1.5s infinite;
  background-size: 200% 100%;
}

.shimmer-content {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 40px;
}

@media (max-width: 992px) {
  .shimmer-content {
    grid-template-columns: 1fr;
  }
}

.shimmer-gallery {
  height: 500px;
  background: linear-gradient(90deg, #f0f0f0 25%, #e0e0e0 50%, #f0f0f0 75%);
  border-radius: 15px;
  animation: shimmer 1.5s infinite;
  background-size: 200% 100%;
}

.shimmer-info {
  height: 500px;
  background: linear-gradient(90deg, #f0f0f0 25%, #e0e0e0 50%, #f0f0f0 75%);
  border-radius: 15px;
  animation: shimmer 1.5s infinite 0.5s;
  background-size: 200% 100%;
}

@keyframes shimmer {
  0% { background-position: -200% 0; }
  100% { background-position: 200% 0; }
}

/* Responsive supplémentaire */
@media (max-width: 768px) {
  .materiel-detail-container {
    padding: 0 15px 40px;
  }
  
  .materiel-title {
    font-size: 1.8rem;
  }
  
  .price-amount {
    font-size: 2.8rem;
  }
  
  .gallery-header {
    padding: 15px 20px;
  }
  
  .product-image {
    height: 300px;
  }
  
  .thumbnail-card {
    width: 80px;
    height: 80px;
  }
  
  .section-header {
    flex-direction: column;
    align-items: flex-start;
  }
  
  .view-all-link {
    align-self: flex-start;
  }
  
  .lightbox-nav {
    width: 40px;
    height: 40px;
    font-size: 1.5rem;
  }
  
  .lightbox-close-btn {
    width: 40px;
    height: 40px;
    top: 10px;
    right: 10px;
  }
}

@media (max-width: 480px) {
  .materiel-title {
    font-size: 1.5rem;
  }
  
  .price-card {
    padding: 20px;
  }
  
  .price-amount {
    font-size: 2.2rem;
  }
  
  .specs-card,
  .description-card,
  .reservation-card {
    padding: 20px;
  }
  
  .similar-products-grid {
    grid-template-columns: 1fr;
  }
  
  .error-actions {
    flex-direction: column;
  }
  
  .error-btn {
    width: 100%;
    justify-content: center;
  }
}
</style>