<script setup>
import { RouterLink, RouterView } from 'vue-router'
import { computed } from 'vue'
import { useAuthStore } from '@/stores/auth'

const auth = useAuthStore()

const isAuthenticated = computed(() => auth.isAuthenticated)
</script>

<template>
  <div id="app">
    <!-- ===== NAVBAR ===== -->
    <header class="navbar">
      <div class="container">
        <RouterLink to="/" class="logo">
          TerraSana
        </RouterLink>

        <nav class="nav-links">
          <RouterLink to="/">Accueil</RouterLink>
          <RouterLink to="/materiels">Matériels</RouterLink>

          <template v-if="!isAuthenticated">
            <RouterLink to="/login">Connexion</RouterLink>
            <RouterLink to="/register" class="btn">Inscription</RouterLink>
          </template>

          <template v-else>
            <RouterLink to="/dashboard">Dashboard</RouterLink>
            <button class="btn danger" @click="auth.logout()">Déconnexion</button>
          </template>
        </nav>
      </div>
    </header>

    <!-- ===== CONTENU ===== -->
    <main class="main-content">
      <RouterView />
    </main>
  </div>
</template>

<style scoped>
/* ===== BASE ===== */
#app {
  min-height: 100vh;
  display: flex;
  flex-direction: column;
  background: #f8f9fa;
}

/* ===== NAVBAR ===== */
.navbar {
  background: #ffffff;
  border-bottom: 1px solid #e5e7eb;
}

.container {
  max-width: 1200px;
  margin: auto;
  padding: 1rem;
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.logo {
  font-weight: bold;
  font-size: 1.2rem;
  color: #16a34a;
  text-decoration: none;
}

.nav-links {
  display: flex;
  gap: 1rem;
  align-items: center;
}

.nav-links a {
  text-decoration: none;
  color: #374151;
  font-size: 0.95rem;
}

.nav-links a.router-link-exact-active {
  font-weight: 600;
  color: #16a34a;
}

/* ===== BOUTONS ===== */
.btn {
  padding: 0.4rem 0.8rem;
  border-radius: 6px;
  background: #16a34a;
  color: white;
  font-size: 0.9rem;
  text-decoration: none;
  border: none;
  cursor: pointer;
}

.btn:hover {
  opacity: 0.9;
}

.btn.danger {
  background: #dc2626;
}

/* ===== CONTENU ===== */
.main-content {
  flex: 1;
  padding: 1.5rem;
}

/* ===== RESPONSIVE ===== */
@media (max-width: 768px) {
  .nav-links {
    flex-wrap: wrap;
    justify-content: flex-end;
  }
}
</style>
