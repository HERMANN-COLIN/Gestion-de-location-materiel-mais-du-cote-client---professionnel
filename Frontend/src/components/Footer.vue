<template>
  <footer class="footer">
    <div class="footer-container">
      
      <!-- Section principale -->
      <div class="footer-main">
        <!-- Logo et description -->
        <div class="footer-brand">
          <div class="footer-logo">
            <span class="logo-icon">🏢</span>
            <span class="logo-text">LocationApp</span>
          </div>
          <p class="footer-description">
            Votre solution de location d'équipements pour particuliers et professionnels.
          </p>
          <div class="footer-social">
            <a href="#" class="social-link" aria-label="Facebook">
              <span class="social-icon">📘</span>
            </a>
            <a href="#" class="social-link" aria-label="Twitter">
              <span class="social-icon">🐦</span>
            </a>
            <a href="#" class="social-link" aria-label="Instagram">
              <span class="social-icon">📸</span>
            </a>
            <a href="#" class="social-link" aria-label="LinkedIn">
              <span class="social-icon">💼</span>
            </a>
          </div>
        </div>

        <!-- Liens rapides -->
        <div class="footer-links">
          <h3 class="links-title">Liens rapides</h3>
          <ul class="links-list">
            <li><router-link to="/" class="footer-link">Accueil</router-link></li>
            <li><router-link to="/catalogue" class="footer-link">Catalogue</router-link></li>
            <li><router-link to="/a-propos" class="footer-link">À propos</router-link></li>
            <li><router-link to="/contact" class="footer-link">Contact</router-link></li>
            <li><router-link to="/faq" class="footer-link">FAQ</router-link></li>
          </ul>
        </div>

        <!-- Services -->
        <div class="footer-links">
          <h3 class="links-title">Services</h3>
          <ul class="links-list">
            <li><router-link to="/location-particuliers" class="footer-link">Location particuliers</router-link></li>
            <li><router-link to="/location-professionnels" class="footer-link">Location professionnels</router-link></li>
            <li><router-link to="/evenements" class="footer-link">Événements</router-link></li>
            <li><router-link to="/devis" class="footer-link">Demande de devis</router-link></li>
            <li><router-link to="/livraison" class="footer-link">Livraison</router-link></li>
          </ul>
        </div>

        <!-- Contact -->
        <div class="footer-contact">
          <h3 class="links-title">Contact</h3>
          <ul class="contact-list">
            <li class="contact-item">
              <span class="contact-icon">📍</span>
              <span>Rue de l'Industrie 123<br>1000 Bruxelles</span>
            </li>
            <li class="contact-item">
              <span class="contact-icon">📞</span>
              <span>+32 2 123 45 67</span>
            </li>
            <li class="contact-item">
              <span class="contact-icon">✉️</span>
              <span>info@locationapp.be</span>
            </li>
            <li class="contact-item">
              <span class="contact-icon">🕒</span>
              <span>Lun-Ven: 9h-18h<br>Sam: 10h-16h</span>
            </li>
          </ul>
        </div>
      </div>

      <!-- Ligne de séparation -->
      <div class="footer-divider"></div>

      <!-- Section bas -->
      <div class="footer-bottom">
        <div class="footer-copyright">
          <p>&copy; {{ currentYear }} LocationApp. Tous droits réservés.</p>
        </div>
        
        <div class="footer-legal">
          <router-link to="/mentions-legales" class="legal-link">Mentions légales</router-link>
          <router-link to="/confidentialite" class="legal-link">Politique de confidentialité</router-link>
          <router-link to="/cookies" class="legal-link">Cookies</router-link>
          <router-link to="/cgv" class="legal-link">CGV</router-link>
        </div>
      </div>

    </div>
  </footer>
</template>

<script setup>
import { ref, computed } from 'vue'

const currentYear = computed(() => new Date().getFullYear())
</script>

<style scoped>
.footer {
  background: linear-gradient(135deg, #1a202c 0%, #2d3748 100%);
  color: #e2e8f0;
  margin-top: auto;
  padding: 3rem 0 1.5rem;
  position: relative;
  overflow: hidden;
}

.footer::before {
  content: '';
  position: absolute;
  top: 0;
  left: 0;
  right: 0;
  height: 4px;
  background: linear-gradient(90deg, #4299e1, #48bb78, #ed8936);
}

.footer-container {
  max-width: 1200px;
  margin: 0 auto;
  padding: 0 1.5rem;
}

/* Section principale */
.footer-main {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
  gap: 2.5rem;
  margin-bottom: 2rem;
}

/* Logo et branding */
.footer-brand {
  display: flex;
  flex-direction: column;
  gap: 1rem;
}

.footer-logo {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  font-size: 1.75rem;
  font-weight: 700;
  color: white;
}

.logo-icon {
  font-size: 2rem;
}

.footer-description {
  color: #a0aec0;
  line-height: 1.6;
  max-width: 300px;
}

.footer-social {
  display: flex;
  gap: 1rem;
  margin-top: 0.5rem;
}

.social-link {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  width: 40px;
  height: 40px;
  background: rgba(255, 255, 255, 0.1);
  border-radius: 50%;
  transition: all 0.3s ease;
  text-decoration: none;
}

.social-link:hover {
  background: #4299e1;
  transform: translateY(-3px);
}

.social-icon {
  font-size: 1.25rem;
}

/* Liens */
.footer-links, .footer-contact {
  display: flex;
  flex-direction: column;
  gap: 1rem;
}

.links-title {
  color: white;
  font-size: 1.125rem;
  font-weight: 600;
  margin-bottom: 0.5rem;
}

.links-list, .contact-list {
  list-style: none;
  padding: 0;
  margin: 0;
  display: flex;
  flex-direction: column;
  gap: 0.75rem;
}

.footer-link {
  color: #a0aec0;
  text-decoration: none;
  transition: color 0.3s ease;
  font-size: 0.95rem;
}

.footer-link:hover {
  color: #4299e1;
}

/* Contact */
.contact-item {
  display: flex;
  align-items: flex-start;
  gap: 0.75rem;
  color: #a0aec0;
  font-size: 0.95rem;
  line-height: 1.5;
}

.contact-icon {
  flex-shrink: 0;
  font-size: 1rem;
}

/* Divider */
.footer-divider {
  height: 1px;
  background: rgba(255, 255, 255, 0.1);
  margin: 2rem 0;
}

/* Section bas */
.footer-bottom {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 1rem;
  text-align: center;
}

.footer-copyright p {
  color: #a0aec0;
  font-size: 0.875rem;
}

.footer-legal {
  display: flex;
  flex-wrap: wrap;
  justify-content: center;
  gap: 1.5rem;
}

.legal-link {
  color: #a0aec0;
  text-decoration: none;
  font-size: 0.875rem;
  transition: color 0.3s ease;
}

.legal-link:hover {
  color: #48bb78;
}

/* Responsive */
@media (min-width: 768px) {
  .footer-bottom {
    flex-direction: row;
    justify-content: space-between;
    text-align: left;
  }
  
  .footer-main {
    grid-template-columns: 1fr 1fr 1fr 1fr;
  }
}

@media (max-width: 768px) {
  .footer {
    padding: 2rem 0 1rem;
  }
  
  .footer-main {
    grid-template-columns: 1fr;
    gap: 2rem;
  }
  
  .footer-social {
    justify-content: center;
  }
  
  .footer-legal {
    gap: 1rem;
  }
}
</style>