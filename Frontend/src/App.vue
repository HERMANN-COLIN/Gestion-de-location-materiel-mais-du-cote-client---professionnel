<script setup>
import { RouterLink, RouterView } from 'vue-router'
import { ref, computed, onMounted, onUnmounted } from 'vue'
import { useAuthStore } from '@/stores/auth'
import api from '@/services/axios'
import Footer from '@/components/Footer.vue'

const auth = useAuthStore()
const mobileMenuOpen = ref(false)
const panierCount = ref(0)

const isAuthenticated = computed(() => auth.isAuthenticated)

// Charger le nombre d'articles dans le panier
const loadPanierCount = async () => {
  if (!isAuthenticated.value) {
    panierCount.value = 0
    return
  }
  
  try {
    const response = await api.get('/panier/compter')
    if (response.data.success) {
      panierCount.value = response.data.count
    }
  } catch (error) {
    console.error('Erreur chargement panier:', error)
    panierCount.value = 0
  }
}

// Écouter les mises à jour du panier
const handleCartUpdate = (event) => {
  if (event.detail?.count !== undefined) {
    panierCount.value = event.detail.count
  } else {
    loadPanierCount()
  }
}

// Écouter les changements d'authentification
const handleAuthChange = () => {
  loadPanierCount()
}

onMounted(() => {
  loadPanierCount()
  window.addEventListener('cartUpdated', handleCartUpdate)
  window.addEventListener('authChanged', handleAuthChange)
})

onUnmounted(() => {
  window.removeEventListener('cartUpdated', handleCartUpdate)
  window.removeEventListener('authChanged', handleAuthChange)
})

const toggleMobileMenu = () => {
  mobileMenuOpen.value = !mobileMenuOpen.value
}
</script>

<template>
  <div id="app">
    <!-- ===== NAVBAR ===== -->
    <header class="navbar">
      <div class="container">
        <!-- Logo -->
        <RouterLink to="/" class="logo">
          <span class="logo-icon">🏢</span>
          <span class="logo-text">TerraSana</span>
        </RouterLink>

        <!-- Barre de recherche -->
        <div class="search-bar">
          <input 
            type="text" 
            placeholder="Rechercher un matériel..." 
            class="search-input"
            @keyup.enter="searchProducts"
          >
          <button class="search-btn" @click="searchProducts">🔍</button>
        </div>

        <!-- Menu navigation -->
        <nav class="nav-links">
          <!-- Bouton Catalogue -->
          <RouterLink to="/catalogue" class="nav-link">
            <span class="nav-icon">📋</span>
            <span class="nav-text">Catalogue</span>
          </RouterLink>

          <!-- Bouton Panier avec badge -->
          <RouterLink to="/panier" class="nav-link cart-link">
            <span class="nav-icon">🛒</span>
            <span class="nav-text">Panier</span>
            <span v-if="panierCount > 0" class="cart-badge">{{ panierCount }}</span>
          </RouterLink>

          <!-- Authentification -->
          <template v-if="!isAuthenticated">
            <RouterLink to="/login" class="nav-link">Connexion</RouterLink>
            <RouterLink to="/register" class="btn primary">Inscription</RouterLink>
          </template>

          <template v-else>
            <!-- Menu utilisateur -->
            <div class="user-menu">
              <RouterLink to="/dashboard" class="user-link">
                <span class="user-icon">👤</span>
                <span class="user-name">{{ auth.user?.name || 'Mon Compte' }}</span>
              </RouterLink>
              <div class="dropdown-menu">
                
                <RouterLink to="/profil" class="dropdown-item">Mon profil</RouterLink>
                <button class="dropdown-item logout" @click="auth.logout()">
                  <span class="logout-icon">🚪</span>
                  Déconnexion
                </button>
              </div>
            </div>
          </template>
        </nav>

        <!-- Menu mobile -->
        <button class="mobile-menu-btn" @click="toggleMobileMenu">
          <span class="menu-icon">☰</span>
        </button>
      </div>

      <!-- Menu mobile déroulant -->
      <div class="mobile-menu" v-if="mobileMenuOpen">
        <RouterLink to="/" class="mobile-link">Accueil</RouterLink>
        <RouterLink to="/catalogue" class="mobile-link">Catalogue</RouterLink>
        <RouterLink to="/panier" class="mobile-link">
          Panier <span v-if="panierCount > 0">({{ panierCount }})</span>
        </RouterLink>
        
        <div class="mobile-auth" v-if="!isAuthenticated">
          <RouterLink to="/login" class="mobile-link">Connexion</RouterLink>
          <RouterLink to="/register" class="mobile-btn">Inscription</RouterLink>
        </div>
        
        <div class="mobile-auth" v-else>
          <RouterLink to="/dashboard" class="mobile-link">Dashboard</RouterLink>
          <button class="mobile-btn danger" @click="auth.logout()">Déconnexion</button>
        </div>
      </div>
    </header>

    <!-- ===== CONTENU PRINCIPAL ===== -->
    <main class="main-content">
      <RouterView />
    </main>

    <!-- ===== FOOTER ===== -->
    <Footer />
  </div>
</template>

<style scoped>
/* ===== BASE ===== */
#app {
  min-height: 100vh;
  display: flex;
  flex-direction: column;
  background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%);
  font-family: 'Segoe UI', system-ui, sans-serif;
}

/* ===== NAVBAR ===== */
.navbar {
  background: white;
  box-shadow: 0 2px 10px rgba(0, 0, 0, 0.08);
  position: sticky;
  top: 0;
  z-index: 1000;
}

.container {
  max-width: 1400px;
  margin: 0 auto;
  padding: 0.8rem 1.5rem;
  display: flex;
  justify-content: space-between;
  align-items: center;
  gap: 1.5rem;
}

/* Logo */
.logo {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  text-decoration: none;
  font-weight: 700;
  font-size: 1.4rem;
  color: #10b981;
  transition: transform 0.3s ease;
}

.logo:hover {
  transform: scale(1.05);
}

.logo-icon {
  font-size: 1.8rem;
}

.logo-text {
  background: linear-gradient(90deg, #10b981, #3b82f6);
  -webkit-background-clip: text;
  -webkit-text-fill-color: transparent;
}

/* Barre de recherche */
.search-bar {
  flex: 1;
  max-width: 500px;
  display: flex;
  background: #f8fafc;
  border-radius: 50px;
  padding: 0.3rem;
  border: 1px solid #e2e8f0;
}

.search-input {
  flex: 1;
  border: none;
  background: transparent;
  padding: 0.5rem 1rem;
  font-size: 0.95rem;
  outline: none;
}

.search-btn {
  background: #10b981;
  color: white;
  border: none;
  border-radius: 50%;
  width: 36px;
  height: 36px;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  transition: background 0.3s;
}

.search-btn:hover {
  background: #0da271;
}

/* Navigation principale */
.nav-links {
  display: flex;
  align-items: center;
  gap: 0.8rem;
}

.nav-link {
  display: flex;
  align-items: center;
  gap: 0.4rem;
  text-decoration: none;
  color: #4b5563;
  padding: 0.6rem 0.9rem;
  border-radius: 8px;
  font-size: 0.95rem;
  font-weight: 500;
  transition: all 0.3s ease;
  position: relative;
}

.nav-link:hover {
  background: #f3f4f6;
  color: #10b981;
  transform: translateY(-1px);
}

.nav-link.router-link-exact-active {
  background: linear-gradient(135deg, #10b98115, #3b82f615);
  color: #10b981;
  font-weight: 600;
}

.nav-icon {
  font-size: 1.1rem;
}

/* Bouton panier */
.cart-link {
  background: #fef3c7;
  color: #92400e;
  position: relative;
  padding-right: 1.2rem;
}

.cart-link:hover {
  background: #fde68a;
}

.cart-badge {
  position: absolute;
  top: -8px;
  right: -8px;
  background: #ef4444;
  color: white;
  font-size: 0.7rem;
  min-width: 20px;
  height: 20px;
  border-radius: 10px;
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 0 4px;
  font-weight: 700;
  box-shadow: 0 2px 5px rgba(239, 68, 68, 0.3);
  animation: badgePop 0.3s ease;
}

@keyframes badgePop {
  0% { transform: scale(0); }
  80% { transform: scale(1.2); }
  100% { transform: scale(1); }
}

/* Boutons */
.btn {
  padding: 0.6rem 1.2rem;
  border-radius: 8px;
  border: none;
  font-weight: 600;
  font-size: 0.95rem;
  cursor: pointer;
  text-decoration: none;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  transition: all 0.3s ease;
}

.btn.primary {
  background: linear-gradient(135deg, #10b981, #3b82f6);
  color: white;
  box-shadow: 0 4px 12px rgba(16, 185, 129, 0.2);
}

.btn.primary:hover {
  transform: translateY(-2px);
  box-shadow: 0 6px 16px rgba(16, 185, 129, 0.3);
}

.btn.danger {
  background: #ef4444;
  color: white;
}

/* Menu utilisateur */
.user-menu {
  position: relative;
}

.user-link {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  padding: 0.6rem 1rem;
  background: #f8fafc;
  border-radius: 8px;
  text-decoration: none;
  color: #4b5563;
  transition: all 0.3s;
}

.user-link:hover {
  background: #f1f5f9;
}

.user-icon {
  font-size: 1.2rem;
}

.user-name {
  max-width: 120px;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

.dropdown-menu {
  position: absolute;
  top: 100%;
  right: 0;
  background: white;
  border-radius: 8px;
  box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
  padding: 0.5rem;
  min-width: 180px;
  opacity: 0;
  visibility: hidden;
  transform: translateY(-10px);
  transition: all 0.3s;
  z-index: 1000;
}

.user-menu:hover .dropdown-menu {
  opacity: 1;
  visibility: visible;
  transform: translateY(0);
}

.dropdown-item {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  padding: 0.7rem 1rem;
  text-decoration: none;
  color: #4b5563;
  border-radius: 6px;
  transition: all 0.2s;
  width: 100%;
  text-align: left;
  background: none;
  border: none;
  font-size: 0.95rem;
  cursor: pointer;
}

.dropdown-item:hover {
  background: #f3f4f6;
  color: #10b981;
}

.dropdown-item.logout {
  color: #ef4444;
  border-top: 1px solid #f3f4f6;
  margin-top: 0.5rem;
  padding-top: 0.8rem;
}

/* Menu mobile */
.mobile-menu-btn {
  display: none;
  background: none;
  border: none;
  font-size: 1.5rem;
  cursor: pointer;
  color: #4b5563;
}

.mobile-menu {
  display: none;
  padding: 1rem;
  background: white;
  border-top: 1px solid #e5e7eb;
}

.mobile-link {
  display: block;
  padding: 0.8rem 1rem;
  text-decoration: none;
  color: #4b5563;
  border-bottom: 1px solid #f3f4f6;
}

.mobile-link:last-child {
  border-bottom: none;
}

.mobile-btn {
  display: block;
  width: 100%;
  padding: 0.8rem;
  margin-top: 1rem;
  text-align: center;
  text-decoration: none;
  border-radius: 8px;
  font-weight: 600;
  border: none;
  cursor: pointer;
}

.mobile-btn.danger {
  background: #ef4444;
  color: white;
}

/* ===== CONTENU PRINCIPAL ===== */
.main-content {
  flex: 1;
  padding: 1.5rem;
  max-width: 1400px;
  margin: 0 auto;
  width: 100%;
}

/* ===== RESPONSIVE ===== */
@media (max-width: 1024px) {
  .search-bar {
    max-width: 300px;
  }
}

@media (max-width: 768px) {
  .container {
    flex-wrap: wrap;
    padding: 0.8rem 1rem;
  }
  
  .search-bar {
    order: 3;
    flex: 1 0 100%;
    max-width: 100%;
    margin-top: 0.8rem;
  }
  
  .nav-links {
    display: none;
  }
  
  .mobile-menu-btn {
    display: block;
  }
  
  .mobile-menu {
    display: block;
  }
  
  .main-content {
    padding: 1rem;
  }
  
  .user-name {
    display: none;
  }
}

@media (max-width: 480px) {
  .logo-text {
    display: none;
  }
  
  .container {
    gap: 0.8rem;
  }
}
</style>