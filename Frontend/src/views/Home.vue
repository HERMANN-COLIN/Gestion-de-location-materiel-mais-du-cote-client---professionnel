<template>
  <div class="home-container">
    <!-- Hero Section avec effet parallaxe -->
    <div class="hero-section">
      <div class="hero-overlay"></div>
      <div class="hero-content animate-fade-in">
        <div class="hero-badge">🌟 Plateforme N°1 de Location</div>
        <h1 class="hero-title">
          <span class="gradient-text">Matériel Événementiel</span>
          <br>
          <span class="hero-subtitle">Louez en toute simplicité</span>
        </h1>
        <p class="hero-description">
          Chaises, tables, décoration et bien plus. Notre plateforme simplifie la location 
          de matériel pour tous vos événements, que vous soyez particulier ou professionnel.
        </p>
        
        <div class="hero-cta">
          <router-link to="/catalogue" class="btn-hero-primary animate-float">
            <span>🎯 Explorer le catalogue</span>
            <i class="arrow-icon">→</i>
          </router-link>
          <router-link v-if="!isAuthenticated" to="/register" class="btn-hero-secondary">
            <span>🚀 Commencer gratuitement</span>
          </router-link>
        </div>
      </div>
      
      <div class="hero-image-container animate-slide-in">
        <div class="hero-image">
          <div class="image-overlay"></div>
        </div>
        <div class="floating-elements">
          <div class="floating-item item-1">🪑</div>
          <div class="floating-item item-2">✨</div>
          <div class="floating-item item-3">🎉</div>
        </div>
      </div>
    </div>

    <!-- Stats Section -->
    <div class="stats-section">
      <div class="stats-container">
        <div class="stat-card">
          <div class="stat-icon">📦</div>
          <div class="stat-number">500+</div>
          <div class="stat-label">Matériels</div>
        </div>
        <div class="stat-card">
          <div class="stat-icon">🚚</div>
          <div class="stat-number">24h</div>
          <div class="stat-label">Livraison</div>
        </div>
        <div class="stat-card">
          <div class="stat-icon">⭐</div>
          <div class="stat-number">4.8/5</div>
          <div class="stat-label">Satisfaction</div>
        </div>
        <div class="stat-card">
          <div class="stat-icon">💯</div>
          <div class="stat-number">100%</div>
          <div class="stat-label">Garanti</div>
        </div>
      </div>
    </div>

    <!-- Features Section -->
    <div class="features-section">
      <div class="section-header">
        <h2 class="section-title">Pourquoi nous choisir ?</h2>
        <p class="section-subtitle">Une expérience de location simplifiée et sans stress</p>
      </div>
      
      <div class="features-grid">
        <div class="feature-card card-hover">
          <div class="feature-icon-wrapper">
            <div class="feature-icon">🚚</div>
          </div>
          <h3 class="feature-title">Livraison Flexible</h3>
          <p class="feature-description">
            Choisissez entre livraison à domicile ou retrait sur place selon vos besoins.
          </p>
          <div class="feature-decoration"></div>
        </div>
        
        <div class="feature-card card-hover">
          <div class="feature-icon-wrapper">
            <div class="feature-icon">🛡️</div>
          </div>
          <h3 class="feature-title">Matériel Garanti</h3>
          <p class="feature-description">
            Tous nos articles sont vérifiés et entretenus régulièrement pour garantir leur qualité.
          </p>
          <div class="feature-decoration"></div>
        </div>
        
        <div class="feature-card card-hover">
          <div class="feature-icon-wrapper">
            <div class="feature-icon">💎</div>
          </div>
          <h3 class="feature-title">Transparence Totale</h3>
          <p class="feature-description">
            Pas de frais cachés. Vous payez uniquement pour la durée de location réelle.
          </p>
          <div class="feature-decoration"></div>
        </div>
      </div>
    </div>

    <!-- CTA Section pour non connectés -->
    <div v-if="!isAuthenticated" class="cta-section gradient-bg">
      <div class="cta-content">
        <h2 class="cta-title">Prêt à commencer votre aventure ?</h2>
        <p class="cta-description">
          Rejoignez des milliers de clients satisfaits et simplifiez vos locations
        </p>
        <div class="cta-buttons">
          <router-link to="/register" class="btn-cta-primary">
            <span>🎯 Créer mon compte gratuit</span>
          </router-link>
          <router-link to="/login" class="btn-cta-secondary">
            <span>🔐 J'ai déjà un compte</span>
          </router-link>
        </div>
      </div>
      <div class="cta-decoration">
        <div class="decoration-circle circle-1"></div>
        <div class="decoration-circle circle-2"></div>
        <div class="decoration-circle circle-3"></div>
      </div>
    </div>

    <!-- Welcome Section pour connectés -->
    <div v-else class="welcome-section">
      <div class="welcome-card">
        <div class="welcome-icon">🎉</div>
        <div class="welcome-content">
          <h3 class="welcome-title">Bienvenue {{ userDisplayName }} !</h3>
          <p class="welcome-message">
            Vous êtes connecté en tant que {{ userTypeText }}. 
            <router-link to="/catalogue" class="welcome-link">
              Commencez à louer du matériel →
            </router-link>
          </p>
        </div>
      </div>
    </div>

    <!-- Footer -->
    <footer class="home-footer">
      <div class="footer-content">
        <div class="footer-brand">
          <div class="footer-logo">📦 LocationMatériel</div>
          <p class="footer-tagline">Votre partenaire de confiance pour la location événementielle</p>
        </div>
        <div class="footer-links">
          <router-link to="/catalogue" class="footer-link">Catalogue</router-link>
          <router-link to="/login" class="footer-link">Connexion</router-link>
          <router-link to="/register" class="footer-link">Inscription</router-link>
        </div>
      </div>
      <div class="footer-bottom">
        <p>&copy; 2024 LocationMatériel. Tous droits réservés.</p>
      </div>
    </footer>
  </div>
</template>

<script setup>
import { computed } from 'vue'
import { useAuthStore } from '../stores/auth'
import { storeToRefs } from 'pinia'

const authStore = useAuthStore()
const { user, isAuthenticated } = storeToRefs(authStore)

const userDisplayName = computed(() => {
  if (!user.value) return ''
  if (user.value.particulier) return `${user.value.particulier.prenom}`
  if (user.value.professionnel) return user.value.professionnel.nom_societe
  return user.value.email.split('@')[0]
})

const userTypeText = computed(() => {
  if (!user.value) return ''
  return user.value.type_id === 1 ? 'particulier' : 'professionnel'
})
</script>

<style scoped>
/* ===== HOME STYLES ===== */
.home-container {
  min-height: 100vh;
  background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%);
}

/* Hero Section */
.hero-section {
  position: relative;
  min-height: 85vh;
  display: flex;
  align-items: center;
  padding: 2rem;
  overflow: hidden;
}

.hero-overlay {
  position: absolute;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: linear-gradient(135deg, rgba(99, 102, 241, 0.1) 0%, rgba(168, 85, 247, 0.05) 100%);
}

.hero-content {
  position: relative;
  z-index: 2;
  max-width: 600px;
  padding: 2rem;
}

.hero-badge {
  display: inline-block;
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  color: white;
  padding: 0.5rem 1rem;
  border-radius: 50px;
  font-size: 0.875rem;
  font-weight: 600;
  margin-bottom: 2rem;
  animation: pulse 2s infinite;
}

.hero-title {
  font-size: 3.5rem;
  font-weight: 800;
  line-height: 1.2;
  margin-bottom: 1.5rem;
  color: #1e293b;
}

.gradient-text {
  background: linear-gradient(135deg, #667eea 0%, #764ba2 50%, #f093fb 100%);
  -webkit-background-clip: text;
  -webkit-text-fill-color: transparent;
  background-clip: text;
  background-size: 200% auto;
  animation: gradient 3s ease infinite;
}

.hero-subtitle {
  font-size: 2rem;
  color: #475569;
  font-weight: 600;
}

.hero-description {
  font-size: 1.125rem;
  color: #64748b;
  line-height: 1.6;
  margin-bottom: 3rem;
  max-width: 500px;
}

.hero-cta {
  display: flex;
  gap: 1rem;
  flex-wrap: wrap;
}

.btn-hero-primary {
  display: inline-flex;
  align-items: center;
  gap: 0.75rem;
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  color: white;
  padding: 1rem 2rem;
  border-radius: 12px;
  font-weight: 600;
  text-decoration: none;
  transition: all 0.3s ease;
  box-shadow: 0 10px 30px rgba(102, 126, 234, 0.4);
}

.btn-hero-primary:hover {
  transform: translateY(-3px);
  box-shadow: 0 15px 40px rgba(102, 126, 234, 0.6);
}

.btn-hero-secondary {
  display: inline-flex;
  align-items: center;
  gap: 0.75rem;
  background: white;
  color: #6366f1;
  padding: 1rem 2rem;
  border-radius: 12px;
  font-weight: 600;
  text-decoration: none;
  transition: all 0.3s ease;
  border: 2px solid #6366f1;
  box-shadow: 0 4px 15px rgba(99, 102, 241, 0.1);
}

.btn-hero-secondary:hover {
  background: #6366f1;
  color: white;
  transform: translateY(-3px);
}

.arrow-icon {
  font-size: 1.25rem;
  transition: transform 0.3s ease;
}

.btn-hero-primary:hover .arrow-icon {
  transform: translateX(5px);
}

/* Hero Image */
.hero-image-container {
  position: absolute;
  right: 5%;
  top: 50%;
  transform: translateY(-50%);
  width: 45%;
  height: 70%;
}

.hero-image {
  position: relative;
  width: 100%;
  height: 100%;
  background: linear-gradient(135deg, #6366f1 0%, #8b5cf6 50%, #d946ef 100%);
  border-radius: 30px;
  overflow: hidden;
}

.image-overlay {
  position: absolute;
  inset: 0;
  background: url('https://images.unsplash.com/photo-1511795409834-ef04bbd61622?ixlib=rb-1.2.1&auto=format&fit=crop&w=1200&q=80') center/cover;
  opacity: 0.9;
  mix-blend-mode: overlay;
}

.floating-elements {
  position: absolute;
  inset: 0;
}

.floating-item {
  position: absolute;
  font-size: 2.5rem;
  animation: float 3s ease-in-out infinite;
}

.item-1 { top: 20%; left: 10%; animation-delay: 0s; }
.item-2 { top: 60%; right: 15%; animation-delay: 1s; }
.item-3 { bottom: 20%; left: 40%; animation-delay: 2s; }

/* Stats Section */
.stats-section {
  padding: 4rem 2rem;
}

.stats-container {
  max-width: 1200px;
  margin: 0 auto;
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
  gap: 2rem;
  padding: 2rem;
  background: white;
  border-radius: 20px;
  box-shadow: 0 10px 40px rgba(0, 0, 0, 0.1);
}

.stat-card {
  text-align: center;
  padding: 2rem;
  transition: transform 0.3s ease;
}

.stat-card:hover {
  transform: translateY(-10px);
}

.stat-icon {
  font-size: 3rem;
  margin-bottom: 1rem;
}

.stat-number {
  font-size: 2.5rem;
  font-weight: 800;
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  -webkit-background-clip: text;
  -webkit-text-fill-color: transparent;
  margin-bottom: 0.5rem;
}

.stat-label {
  color: #64748b;
  font-weight: 600;
}

/* Features Section */
.features-section {
  padding: 6rem 2rem;
  background: #f8fafc;
}

.section-header {
  text-align: center;
  max-width: 800px;
  margin: 0 auto 4rem;
}

.section-title {
  font-size: 3rem;
  font-weight: 800;
  color: #1e293b;
  margin-bottom: 1rem;
}

.section-subtitle {
  font-size: 1.25rem;
  color: #64748b;
}

.features-grid {
  max-width: 1200px;
  margin: 0 auto;
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
  gap: 3rem;
}

.feature-card {
  position: relative;
  background: white;
  padding: 3rem 2rem;
  border-radius: 20px;
  text-align: center;
  transition: all 0.4s ease;
  overflow: hidden;
}

.card-hover:hover {
  transform: translateY(-15px);
  box-shadow: 0 30px 60px rgba(0, 0, 0, 0.15);
}

.feature-icon-wrapper {
  width: 80px;
  height: 80px;
  margin: 0 auto 2rem;
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
}

.feature-icon {
  font-size: 2.5rem;
}

.feature-title {
  font-size: 1.5rem;
  font-weight: 700;
  color: #1e293b;
  margin-bottom: 1rem;
}

.feature-description {
  color: #64748b;
  line-height: 1.6;
}

.feature-decoration {
  position: absolute;
  bottom: 0;
  left: 0;
  right: 0;
  height: 4px;
  background: linear-gradient(90deg, #667eea 0%, #764ba2 100%);
  transform: scaleX(0);
  transition: transform 0.3s ease;
}

.feature-card:hover .feature-decoration {
  transform: scaleX(1);
}

/* CTA Section */
.cta-section {
  position: relative;
  padding: 6rem 2rem;
  overflow: hidden;
}

.cta-content {
  position: relative;
  z-index: 2;
  max-width: 800px;
  margin: 0 auto;
  text-align: center;
}

.cta-title {
  font-size: 3rem;
  font-weight: 800;
  color: white;
  margin-bottom: 1.5rem;
}

.cta-description {
  font-size: 1.25rem;
  color: rgba(255, 255, 255, 0.9);
  margin-bottom: 3rem;
  max-width: 600px;
  margin-left: auto;
  margin-right: auto;
}

.cta-buttons {
  display: flex;
  gap: 1rem;
  justify-content: center;
  flex-wrap: wrap;
}

.btn-cta-primary {
  display: inline-flex;
  align-items: center;
  gap: 0.75rem;
  background: white;
  color: #6366f1;
  padding: 1rem 2.5rem;
  border-radius: 12px;
  font-weight: 600;
  text-decoration: none;
  transition: all 0.3s ease;
}

.btn-cta-primary:hover {
  transform: translateY(-3px);
  box-shadow: 0 20px 40px rgba(255, 255, 255, 0.2);
}

.btn-cta-secondary {
  display: inline-flex;
  align-items: center;
  gap: 0.75rem;
  background: transparent;
  color: white;
  padding: 1rem 2.5rem;
  border-radius: 12px;
  font-weight: 600;
  text-decoration: none;
  transition: all 0.3s ease;
  border: 2px solid white;
}

.btn-cta-secondary:hover {
  background: white;
  color: #6366f1;
  transform: translateY(-3px);
}

.cta-decoration {
  position: absolute;
  inset: 0;
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
  right: -150px;
}

.circle-2 {
  width: 200px;
  height: 200px;
  bottom: -100px;
  left: -100px;
}

.circle-3 {
  width: 150px;
  height: 150px;
  top: 50%;
  left: 10%;
}

/* Welcome Section */
.welcome-section {
  padding: 4rem 2rem;
}

.welcome-card {
  max-width: 800px;
  margin: 0 auto;
  background: linear-gradient(135deg, #10b981 0%, #059669 100%);
  border-radius: 20px;
  padding: 3rem;
  display: flex;
  align-items: center;
  gap: 2rem;
  color: white;
}

.welcome-icon {
  font-size: 4rem;
}

.welcome-content {
  flex: 1;
}

.welcome-title {
  font-size: 2rem;
  font-weight: 700;
  margin-bottom: 0.5rem;
}

.welcome-message {
  font-size: 1.125rem;
  opacity: 0.9;
}

.welcome-link {
  color: white;
  font-weight: 600;
  text-decoration: underline;
}

/* Footer */
.home-footer {
  background: #1e293b;
  color: white;
  padding: 4rem 2rem 2rem;
}

.footer-content {
  max-width: 1200px;
  margin: 0 auto;
  display: flex;
  justify-content: space-between;
  align-items: center;
  flex-wrap: wrap;
  gap: 2rem;
  margin-bottom: 3rem;
}

.footer-brand {
  flex: 1;
  min-width: 300px;
}

.footer-logo {
  font-size: 1.875rem;
  font-weight: 800;
  margin-bottom: 1rem;
}

.footer-tagline {
  color: #cbd5e1;
}

.footer-links {
  display: flex;
  gap: 2rem;
}

.footer-link {
  color: #cbd5e1;
  text-decoration: none;
  transition: color 0.3s ease;
}

.footer-link:hover {
  color: white;
}

.footer-bottom {
  text-align: center;
  padding-top: 2rem;
  border-top: 1px solid #334155;
  color: #94a3b8;
}

/* Responsive */
@media (max-width: 768px) {
  .hero-section {
    flex-direction: column;
    text-align: center;
    min-height: auto;
    padding: 4rem 1rem;
  }
  
  .hero-content {
    max-width: 100%;
    padding: 1rem;
  }
  
  .hero-title {
    font-size: 2.5rem;
  }
  
  .hero-image-container {
    position: relative;
    width: 100%;
    height: 300px;
    margin-top: 3rem;
    right: 0;
    top: 0;
    transform: none;
  }
  
  .features-grid {
    grid-template-columns: 1fr;
  }
  
  .cta-buttons {
    flex-direction: column;
    align-items: center;
  }
  
  .footer-content {
    flex-direction: column;
    text-align: center;
  }
}

/* Animations */
@keyframes fadeIn {
  from {
    opacity: 0;
    transform: translateY(20px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}

@keyframes slideInRight {
  from {
    opacity: 0;
    transform: translateX(50px);
  }
  to {
    opacity: 1;
    transform: translateX(0);
  }
}

@keyframes pulse {
  0%, 100% {
    opacity: 1;
    transform: scale(1);
  }
  50% {
    opacity: 0.8;
    transform: scale(1.05);
  }
}

@keyframes float {
  0%, 100% {
    transform: translateY(0);
  }
  50% {
    transform: translateY(-20px);
  }
}

@keyframes gradient {
  0% {
    background-position: 0% 50%;
  }
  50% {
    background-position: 100% 50%;
  }
  100% {
    background-position: 0% 50%;
  }
}
</style>