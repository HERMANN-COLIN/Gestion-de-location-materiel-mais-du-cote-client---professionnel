<template>
  <div class="register-container">
    <div class="register-background">
      <div class="bg-particle particle-1">✨</div>
      <div class="bg-particle particle-2">🌟</div>
      <div class="bg-particle particle-3">🎯</div>
    </div>
    
    <div class="register-wrapper animate-fade-in">
      <!-- Progress Steps -->
      <div class="progress-steps">
        <div class="step" :class="{ 'active': step === 1, 'completed': step > 1 }">
          <div class="step-circle">1</div>
          <span class="step-label">Type</span>
        </div>
        <div class="step-line"></div>
        <div class="step" :class="{ 'active': step === 2, 'completed': step > 2 }">
          <div class="step-circle">2</div>
          <span class="step-label">Informations</span>
        </div>
        <div class="step-line"></div>
        <div class="step" :class="{ 'active': step === 3 }">
          <div class="step-circle">3</div>
          <span class="step-label">{{ form.type_id === 1 ? 'Adresse' : 'Détails' }}</span>
        </div>
      </div>
      
      <!-- Step 1: Type Selection -->
      <div v-if="step === 1" class="step-content">
        <div class="step-header">
          <h2 class="step-title">Qui êtes-vous ?</h2>
          <p class="step-subtitle">Choisissez le type de compte qui vous correspond</p>
        </div>
        
        <div class="type-selection">
          <div 
            class="type-card"
            :class="{ 'selected': form.type_id === 1 }"
            @click="form.type_id = 1"
          >
            <div class="type-icon">👤</div>
            <h3 class="type-title">Particulier</h3>
            <p class="type-description">Pour un usage personnel, événements familiaux, etc.</p>
            <ul class="type-features">
              <li>✓ Location pour particuliers</li>
              <li>✓ Événements personnels</li>
              <li>✓ Tarifs adaptés</li>
            </ul>
            <div class="type-badge">Le plus choisi</div>
          </div>
          
          <div 
            class="type-card"
            :class="{ 'selected': form.type_id === 2 }"
            @click="form.type_id = 2"
          >
            <div class="type-icon">🏢</div>
            <h3 class="type-title">Professionnel</h3>
            <p class="type-description">Pour les entreprises, organisateurs d'événements, etc.</p>
            <ul class="type-features">
              <li>✓ Tarifs professionnels</li>
              <li>✓ Facturation détaillée</li>
              <li>✓ Support prioritaire</li>
            </ul>
            <div class="type-badge">Entreprises</div>
          </div>
        </div>
        
        <button @click="nextStep" class="next-btn">
          Continuer
          <span class="btn-arrow">→</span>
        </button>
      </div>
      
      <!-- Step 2: User Information -->
      <div v-else-if="step === 2" class="step-content">
        <div class="step-header">
          <h2 class="step-title">Vos informations</h2>
          <p class="step-subtitle">Remplissez les informations de base de votre compte</p>
        </div>
        
        <form @submit.prevent="nextStep" class="info-form">
          <!-- PARTICULIER : Nom et Prénom -->
          <div v-if="form.type_id === 1" class="form-row">
            <div class="form-group">
              <label class="form-label">Nom *</label>
              <div class="input-with-icon">
                <input
                  v-model="form.nom"
                  type="text"
                  required
                  placeholder="Votre nom"
                  class="form-input"
                  :class="{ 'error': errors.nom }"
                />
                
              </div>
              <p v-if="errors.nom" class="error-message">{{ errors.nom[0] }}</p>
            </div>
            
            <div class="form-group">
              <label class="form-label">Prénom *</label>
              <div class="input-with-icon">
                <input
                  v-model="form.prenom"
                  type="text"
                  required
                  placeholder="Votre prénom"
                  class="form-input"
                  :class="{ 'error': errors.prenom }"
                />
                
              </div>
              <p v-if="errors.prenom" class="error-message">{{ errors.prenom[0] }}</p>
            </div>
          </div>
          
          <!-- PROFESSIONNEL : Nom de société -->
          <div v-else class="form-group">
            <label class="form-label">Nom de la société *</label>
            <input
              v-model="form.nom_societe"
              type="text"
              required
              placeholder="Nom de votre entreprise"
              class="form-input"
              :class="{ 'error': errors.nom_societe }"
            />
            <p v-if="errors.nom_societe" class="error-message">{{ errors.nom_societe[0] }}</p>
          </div>
          
          <!-- Email -->
          <div class="form-group">
            <label class="form-label">Email *</label>
            <div class="input-with-icon">
              <input
                v-model="form.email"
                type="email"
                required
                placeholder="votre@email.com"
                class="form-input"
                :class="{ 'error': errors.email }"
              />
              
            </div>
            <p v-if="errors.email" class="error-message">{{ errors.email[0] }}</p>
          </div>
          
          <!-- Password -->
          <div class="form-row">
            <div class="form-group">
              <label class="form-label">Mot de passe *</label>
              <div class="input-with-icon">
                <input
                  v-model="form.password"
                  :type="showPassword ? 'text' : 'password'"
                  required
                  placeholder="Minimum 8 caractères"
                  class="form-input"
                  :class="{ 'error': errors.password }"
                />
              
                <button 
                  type="button" 
                  @click="showPassword = !showPassword"
                  class="password-toggle"
                >
                  {{ showPassword ? '🙈' : '👁️' }}
                </button>
              </div>
              <p v-if="errors.password" class="error-message">{{ errors.password[0] }}</p>
            </div>
            
            <div class="form-group">
              <label class="form-label">Confirmation *</label>
              <div class="input-with-icon">
                <input
                  v-model="form.password_confirmation"
                  :type="showPassword ? 'text' : 'password'"
                  required
                  placeholder="Retapez votre mot de passe"
                  class="form-input"
                />
               
              </div>
            </div>
          </div>
          
          <!-- Langue -->
          <div class="form-group">
            <label class="form-label">Langue préférée *</label>
            <select v-model="form.langue_id" class="form-input" required>
              <option value="">Sélectionnez une langue</option>
              <option v-for="langue in langues" :key="langue.id" :value="langue.id">
                {{ langue.langue }}
              </option>
            </select>
            <p v-if="errors.langue_id" class="error-message">{{ errors.langue_id[0] }}</p>
          </div>
          
          <div class="form-actions">
            <button type="button" @click="prevStep" class="back-btn">
              ← Retour
            </button>
            <button type="submit" class="next-btn">
              Continuer
              <span class="btn-arrow">→</span>
            </button>
          </div>
        </form>
      </div>
      
      <!-- Step 3: Address / Professional Details -->
      <div v-else class="step-content">
        <!-- PARTICULIER : Adresse simple -->
        <div v-if="form.type_id === 1">
          <div class="step-header">
            <h2 class="step-title">Votre adresse</h2>
            <p class="step-subtitle">Où souhaitez-vous recevoir vos commandes ?</p>
          </div>
          
          <form @submit.prevent="handleSubmit" class="address-form">
            <div class="form-group">
              <label class="form-label">Adresse complète *</label>
              <textarea
                v-model="form.adresse"
                required
                placeholder="Ex: Rue de la Paix 87, 1000 Bruxelles, Belgique"
                class="form-input"
                rows="3"
                :class="{ 'error': errors.adresse }"
              ></textarea>
              <p v-if="errors.adresse" class="error-message">{{ errors.adresse[0] }}</p>
              <p class="help-text">Entrez votre adresse complète (rue, numéro, code postal, ville, pays)</p>
            </div>
            
            <div class="submit-section">
              <div class="terms-section">
                <label class="checkbox-container">
                  <input v-model="acceptTerms" type="checkbox" required />
                  <span class="checkmark"></span>
                  <span class="checkbox-text">
                    J'accepte les 
                    <a href="#" class="terms-link">conditions générales</a> 
                    et la 
                    <a href="#" class="terms-link">politique de confidentialité</a>
                  </span>
                </label>
              </div>
              
              <div v-if="errorMessage" class="error-alert">
                <div class="alert-icon">⚠️</div>
                <div class="alert-content">
                  <p>{{ errorMessage }}</p>
                </div>
              </div>
              
              <div class="form-actions">
                <button type="button" @click="prevStep" class="back-btn">
                  ← Retour
                </button>
                <button 
                  type="submit"
                  :disabled="loading || !acceptTerms"
                  class="submit-btn"
                  :class="{ 'loading': loading }"
                >
                  <span v-if="!loading">Créer mon compte</span>
                  <span v-else class="loading-spinner"></span>
                </button>
              </div>
            </div>
          </form>
        </div>
        
        <!-- PROFESSIONNEL : Adresses + Contact + Horaires -->
        <div v-else>
          <div class="step-header">
            <h2 class="step-title">Détails professionnels</h2>
            <p class="step-subtitle">Adresses, contact et horaires de votre entreprise</p>
          </div>
          
          <form @submit.prevent="handleSubmit" class="pro-form">
            <!-- Adresse du siège -->
            <div class="section-block">
              <h3 class="section-title">📍 Adresse du siège social</h3>
              
              <div class="form-group">
                <label class="form-label">Nom de la rue *</label>
                <input
                  v-model="form.nom_rue_siege"
                  type="text"
                  required
                  placeholder="Ex: Avenue des Entrepreneurs"
                  class="form-input"
                  :class="{ 'error': errors.nom_rue_siege }"
                />
                <p v-if="errors.nom_rue_siege" class="error-message">{{ errors.nom_rue_siege[0] }}</p>
              </div>
              
              <div class="form-row">
                <div class="form-group">
                  <label class="form-label">Numéro *</label>
                  <input
                    v-model="form.numero_rue_siege"
                    type="text"
                    required
                    placeholder="Ex: 42"
                    class="form-input"
                    :class="{ 'error': errors.numero_rue_siege }"
                  />
                  <p v-if="errors.numero_rue_siege" class="error-message">{{ errors.numero_rue_siege[0] }}</p>
                </div>
              </div>
              
              <!-- Formulaire de commune manuel pour le siège -->
              <div class="commune-form">
                <h4 class="commune-title">Commune du siège social</h4>
                <div class="form-row">
                  <div class="form-group">
                    <label class="form-label">Nom de la commune *</label>
                    <input
                      v-model="form.nom_commune_siege"
                      type="text"
                      required
                      placeholder="Ex: Bruxelles"
                      class="form-input"
                      :class="{ 'error': errors.nom_commune_siege }"
                    />
                    <p v-if="errors.nom_commune_siege" class="error-message">{{ errors.nom_commune_siege[0] }}</p>
                  </div>
                  
                  <div class="form-group">
                    <label class="form-label">Numéro de commune *</label>
                    <input
                      v-model="form.numero_commune_siege"
                      type="text"
                      required
                      placeholder="Ex: 1000"
                      class="form-input"
                      :class="{ 'error': errors.numero_commune_siege }"
                    />
                    <p v-if="errors.numero_commune_siege" class="error-message">{{ errors.numero_commune_siege[0] }}</p>
                  </div>
                </div>
              </div>
            </div>
            
            <!-- Adresse de livraison optionnelle -->
            <div class="section-block">
              <div class="section-header">
                <h3 class="section-title">🚚 Adresse de livraison</h3>
                <button 
                  type="button" 
                  @click="showDeliveryAddress = !showDeliveryAddress"
                  class="toggle-btn"
                >
                  {{ showDeliveryAddress ? '✖️ Même adresse' : '➕ Adresse différente' }}
                </button>
              </div>
              
              <div v-if="showDeliveryAddress" class="delivery-address">
                <div class="form-group">
                  <label class="form-label">Nom de la rue *</label>
                  <input
                    v-model="form.nom_rue_livraison"
                    type="text"
                    :required="showDeliveryAddress"
                    placeholder="Ex: Rue du Commerce"
                    class="form-input"
                    :class="{ 'error': errors.nom_rue_livraison }"
                  />
                  <p v-if="errors.nom_rue_livraison" class="error-message">{{ errors.nom_rue_livraison[0] }}</p>
                </div>
                
                <div class="form-row">
                  <div class="form-group">
                    <label class="form-label">Numéro *</label>
                    <input
                      v-model="form.numero_rue_livraison"
                      type="text"
                      :required="showDeliveryAddress"
                      placeholder="Ex: 15"
                      class="form-input"
                      :class="{ 'error': errors.numero_rue_livraison }"
                    />
                    <p v-if="errors.numero_rue_livraison" class="error-message">{{ errors.numero_rue_livraison[0] }}</p>
                  </div>
                </div>
                
                <!-- Formulaire de commune manuel pour la livraison -->
                <div v-if="showDeliveryAddress" class="commune-form">
                  <h4 class="commune-title">Commune de livraison</h4>
                  <div class="form-row">
                    <div class="form-group">
                      <label class="form-label">Nom de la commune *</label>
                      <input
                        v-model="form.nom_commune_livraison"
                        type="text"
                        :required="showDeliveryAddress"
                        placeholder="Ex: Bruxelles"
                        class="form-input"
                        :class="{ 'error': errors.nom_commune_livraison }"
                      />
                      <p v-if="errors.nom_commune_livraison" class="error-message">{{ errors.nom_commune_livraison[0] }}</p>
                    </div>
                    
                    <div class="form-group">
                      <label class="form-label">Numéro de commune *</label>
                      <input
                        v-model="form.numero_commune_livraison"
                        type="text"
                        :required="showDeliveryAddress"
                        placeholder="Ex: 1000"
                        class="form-input"
                        :class="{ 'error': errors.numero_commune_livraison }"
                      />
                      <p v-if="errors.numero_commune_livraison" class="error-message">{{ errors.numero_commune_livraison[0] }}</p>
                    </div>
                  </div>
                </div>
              </div>
            </div>
            
            <!-- Contact professionnel -->
            <div class="section-block">
              <h3 class="section-title">👤 Personne de contact</h3>
              
              <div class="form-row">
                <div class="form-group">
                  <label class="form-label">Nom *</label>
                  <input
                    v-model="form.contact_nom"
                    type="text"
                    required
                    placeholder="Nom du contact"
                    class="form-input"
                    :class="{ 'error': errors.contact_nom }"
                  />
                  <p v-if="errors.contact_nom" class="error-message">{{ errors.contact_nom[0] }}</p>
                </div>
                
                <div class="form-group">
                  <label class="form-label">Prénom *</label>
                  <input
                    v-model="form.contact_prenom"
                    type="text"
                    required
                    placeholder="Prénom du contact"
                    class="form-input"
                    :class="{ 'error': errors.contact_prenom }"
                  />
                  <p v-if="errors.contact_prenom" class="error-message">{{ errors.contact_prenom[0] }}</p>
                </div>
              </div>
              
              <div class="form-row">
                <div class="form-group">
                  <label class="form-label">Email du contact *</label>
                  <input
                    v-model="form.contact_email"
                    type="email"
                    required
                    placeholder="contact@entreprise.com"
                    class="form-input"
                    :class="{ 'error': errors.contact_email }"
                  />
                  <p v-if="errors.contact_email" class="error-message">{{ errors.contact_email[0] }}</p>
                </div>
                
                <div class="form-group">
                  <label class="form-label">Téléphone *</label>
                  <input
                    v-model="form.contact_telephone"
                    type="tel"
                    required
                    placeholder="+32 123 456 789"
                    class="form-input"
                    :class="{ 'error': errors.contact_telephone }"
                  />
                  <p v-if="errors.contact_telephone" class="error-message">{{ errors.contact_telephone[0] }}</p>
                </div>
              </div>
              
              <div class="form-group">
                <label class="form-label">Fonction *</label>
                <select 
                  v-model="form.contact_fonction_id" 
                  class="form-input" 
                  required
                  :class="{ 'error': errors.contact_fonction_id }"
                >
                  <option value="">Sélectionnez une fonction</option>
                  <option v-for="fonction in fonctions" :key="fonction.id" :value="fonction.id">
                    {{ fonction.fonction }}
                  </option>
                </select>
                <p v-if="errors.contact_fonction_id" class="error-message">{{ errors.contact_fonction_id[0] }}</p>
              </div>
            </div>
            
            <!-- Horaires -->
            <div class="section-block">
              <h3 class="section-title">🕒 Horaires d'ouverture</h3>
              
              <div class="form-row">
                <div class="form-group">
                  <label class="form-label">Heure d'ouverture *</label>
                  <input
                    v-model="form.heure_ouverture"
                    type="time"
                    required
                    class="form-input"
                    :class="{ 'error': errors.heure_ouverture }"
                  />
                  <p v-if="errors.heure_ouverture" class="error-message">{{ errors.heure_ouverture[0] }}</p>
                </div>
                
                <div class="form-group">
                  <label class="form-label">Heure de fermeture *</label>
                  <input
                    v-model="form.heure_fermeture"
                    type="time"
                    required
                    class="form-input"
                    :class="{ 'error': errors.heure_fermeture }"
                  />
                  <p v-if="errors.heure_fermeture" class="error-message">{{ errors.heure_fermeture[0] }}</p>
                </div>
              </div>
            </div>
            
            <!-- Submit section -->
            <div class="submit-section">
              <div class="terms-section">
                <label class="checkbox-container">
                  <input v-model="acceptTerms" type="checkbox" required />
                  <span class="checkmark"></span>
                  <span class="checkbox-text">
                    J'accepte les 
                    <a href="#" class="terms-link">conditions générales</a> 
                    et la 
                    <a href="#" class="terms-link">politique de confidentialité</a>
                  </span>
                </label>
              </div>
              
              <div v-if="errorMessage" class="error-alert">
                <div class="alert-icon">⚠️</div>
                <div class="alert-content">
                  <p>{{ errorMessage }}</p>
                </div>
              </div>
              
              <div class="form-actions">
                <button type="button" @click="prevStep" class="back-btn">
                  ← Retour
                </button>
                <button 
                  type="submit"
                  :disabled="loading || !acceptTerms"
                  class="submit-btn"
                  :class="{ 'loading': loading }"
                >
                  <span v-if="!loading">Créer mon compte</span>
                  <span v-else class="loading-spinner"></span>
                </button>
              </div>
            </div>
          </form>
        </div>
      </div>
      
      <!-- Login Link -->
      <div class="login-link">
        Déjà un compte ? 
        <router-link to="/login">Connectez-vous</router-link>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, reactive, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '../stores/auth'
import axios from 'axios'

const router = useRouter()
const authStore = useAuthStore()

// États
const step = ref(1)
const loading = ref(false)
const errorMessage = ref('')
const errors = ref({})
const showPassword = ref(false)
const showDeliveryAddress = ref(false)
const acceptTerms = ref(false)
const langues = ref([])
const fonctions = ref([])

// Formulaire adapté au backend Laravel
const form = reactive({
  // Commun
  type_id: 1,
  email: '',
  password: '',
  password_confirmation: '',
  langue_id: '',
  
  // PARTICULIER
  nom: '',
  prenom: '',
  adresse: '', // Champ unique pour l'adresse complète
  
  // PROFESSIONNEL
  nom_societe: '',
  
  // Adresse siège
  nom_rue_siege: '',
  numero_rue_siege: '',
  // Commune siège (champs textuels)
  nom_commune_siege: '',
  numero_commune_siege: '',
  
  // Adresse livraison
  nom_rue_livraison: '',
  numero_rue_livraison: '',
  // Commune livraison (champs textuels)
  nom_commune_livraison: '',
  numero_commune_livraison: '',
  
  // Contact
  contact_nom: '',
  contact_prenom: '',
  contact_email: '',
  contact_telephone: '',
  contact_fonction_id: '',
  
  // Horaires
  heure_ouverture: '09:00',
  heure_fermeture: '18:00'
})

// Navigation
const nextStep = () => {
  if (step.value < 3) {
    if (step.value === 2 && !validateStep2()) {
      return
    }
    step.value++
    window.scrollTo(0, 0)
  }
}

const prevStep = () => {
  if (step.value > 1) {
    step.value--
    errorMessage.value = ''
    errors.value = {}
    window.scrollTo(0, 0)
  }
}

// Validation étape 2
const validateStep2 = () => {
  errors.value = {}
  let isValid = true

  if (!form.email || !/\S+@\S+\.\S+/.test(form.email)) {
    errors.value.email = ['L\'email est requis et doit être valide']
    isValid = false
  }

  if (!form.password || form.password.length < 8) {
    errors.value.password = ['Le mot de passe doit contenir au moins 8 caractères']
    isValid = false
  }

  if (form.password !== form.password_confirmation) {
    errors.value.password = ['Les mots de passe ne correspondent pas']
    isValid = false
  }

  if (form.type_id === 1) {
    if (!form.nom) {
      errors.value.nom = ['Le nom est requis']
      isValid = false
    }
    if (!form.prenom) {
      errors.value.prenom = ['Le prénom est requis']
      isValid = false
    }
  } else {
    if (!form.nom_societe) {
      errors.value.nom_societe = ['Le nom de la société est requis']
      isValid = false
    }
  }

  if (!form.langue_id) {
    errors.value.langue_id = ['La langue est requise']
    isValid = false
  }

  return isValid
}

// Chargement des données
const loadLangues = async () => {
  try {
    const response = await axios.get('/langues')
    langues.value = Array.isArray(response.data) ? response.data : response.data.data
    console.log('✅ Langues chargées:', langues.value.length)
  } catch (error) {
    console.error('❌ Erreur langues:', error)
    langues.value = [
      { id: 1, langue: 'Français' },
      { id: 2, langue: 'Anglais' },
      { id: 3, langue: 'Néerlandais' }
    ]
  }
}

const loadFonctions = async () => {
  try {
    const response = await axios.get('/fonctions')
    fonctions.value = Array.isArray(response.data) ? response.data : response.data.data
    console.log('✅ Fonctions chargées:', fonctions.value.length)
  } catch (error) {
    console.error('❌ Erreur fonctions:', error)
    fonctions.value = [
      { id: 1, fonction: 'Responsable logistique' },
      { id: 2, fonction: 'Directrice commerciale' },
      { id: 3, fonction: 'Gérant' }
    ]
  }
}

// Soumission
const handleSubmit = async (e) => {
  e.preventDefault()
  
  if (!acceptTerms.value) {
    errorMessage.value = 'Veuillez accepter les conditions générales'
    return
  }

  loading.value = true
  errorMessage.value = ''
  errors.value = {}

  try {
    const dataToSend = {
      type_id: form.type_id,
      email: form.email,
      password: form.password,
      password_confirmation: form.password_confirmation,
      langue_id: parseInt(form.langue_id),
    }

    if (form.type_id === 1) {
      // PARTICULIER
      dataToSend.nom = form.nom
      dataToSend.prenom = form.prenom
      dataToSend.adresse = form.adresse // Adresse complète en texte
    } else {
      // PROFESSIONNEL
      dataToSend.nom_societe = form.nom_societe
      
      // Adresse siège
      dataToSend.nom_rue_siege = form.nom_rue_siege
      dataToSend.numero_rue_siege = form.numero_rue_siege
      dataToSend.nom_commune_siege = form.nom_commune_siege
      dataToSend.numero_commune_siege = form.numero_commune_siege
      
      // Adresse livraison si différente
      if (showDeliveryAddress.value && form.nom_rue_livraison && form.numero_rue_livraison) {
        dataToSend.has_different_delivery_address = true
        dataToSend.nom_rue_livraison = form.nom_rue_livraison
        dataToSend.numero_rue_livraison = form.numero_rue_livraison
        dataToSend.nom_commune_livraison = form.nom_commune_livraison
        dataToSend.numero_commune_livraison = form.numero_commune_livraison
      }
      
      // Contact
      dataToSend.contact_nom = form.contact_nom
      dataToSend.contact_prenom = form.contact_prenom
      dataToSend.contact_email = form.contact_email
      dataToSend.contact_telephone = form.contact_telephone
      dataToSend.contact_fonction_id = parseInt(form.contact_fonction_id)
      
      // Horaires
      dataToSend.heure_ouverture = form.heure_ouverture
      dataToSend.heure_fermeture = form.heure_fermeture
    }

    console.log('📤 Données envoyées:', dataToSend)

    const result = await authStore.register(dataToSend)

    if (result.success) {
      console.log('✅ Inscription réussie')
      router.push('/')
    } else {
      errorMessage.value = result.error || 'Erreur lors de l\'inscription'
    }
  } catch (error) {
    console.error('❌ Erreur:', error)
    
    if (error.response?.status === 422) {
      errors.value = error.response.data.errors
      errorMessage.value = 'Veuillez corriger les erreurs dans le formulaire.'
      
      // Retour à l'étape concernée
      const errorKeys = Object.keys(error.response.data.errors)
      if (errorKeys.some(key => ['email', 'password', 'nom', 'prenom', 'nom_societe', 'langue_id', 'adresse', 'nom_commune_siege', 'numero_commune_siege'].includes(key))) {
        step.value = 2
      }
    } else {
      errorMessage.value = error.response?.data?.message || error.response?.data?.error || 'Une erreur est survenue'
    }
  } finally {
    loading.value = false
  }
}

onMounted(() => {
  console.log('🔄 Chargement des données...')
  loadLangues()
  loadFonctions()
})
</script>

<style scoped>
/* Styles identiques au fichier précédent */
.register-container {
  min-height: 100vh;
  padding: 2rem;
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  position: relative;
  overflow-x: hidden;
}

.register-background {
  position: fixed;
  inset: 0;
  z-index: 1;
}

.bg-particle {
  position: absolute;
  font-size: 2rem;
  opacity: 0.2;
  animation: float 6s ease-in-out infinite;
}

.particle-1 { top: 10%; left: 5%; animation-delay: 0s; }
.particle-2 { top: 60%; right: 10%; animation-delay: 2s; }
.particle-3 { bottom: 20%; left: 50%; animation-delay: 4s; }

.register-wrapper {
  position: relative;
  z-index: 2;
  max-width: 900px;
  margin: 0 auto;
  background: white;
  border-radius: 24px;
  padding: 3rem;
  box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
}

/* Progress Steps */
.progress-steps {
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin-bottom: 3rem;
  position: relative;
}

.step {
  display: flex;
  flex-direction: column;
  align-items: center;
  z-index: 2;
}

.step-circle {
  width: 50px;
  height: 50px;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  font-weight: 700;
  font-size: 1.25rem;
  margin-bottom: 0.5rem;
  transition: all 0.3s ease;
  border: 3px solid #e2e8f0;
  background: white;
  color: #94a3b8;
}

.step.active .step-circle {
  border-color: #6366f1;
  background: #6366f1;
  color: white;
  transform: scale(1.1);
}

.step.completed .step-circle {
  border-color: #10b981;
  background: #10b981;
  color: white;
}

.step-label {
  font-size: 0.875rem;
  font-weight: 600;
  color: #94a3b8;
}

.step.active .step-label {
  color: #6366f1;
}

.step.completed .step-label {
  color: #10b981;
}

.step-line {
  flex: 1;
  height: 3px;
  background: #e2e8f0;
  margin: 0 1rem;
}

.step.completed + .step-line {
  background: #10b981;
}

.step-content {
  animation: slideIn 0.5s ease-out;
}

.step-header {
  text-align: center;
  margin-bottom: 3rem;
}

.step-title {
  font-size: 2.5rem;
  font-weight: 800;
  color: #1e293b;
  margin-bottom: 0.5rem;
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  -webkit-background-clip: text;
  -webkit-text-fill-color: transparent;
}

.step-subtitle {
  color: #64748b;
  font-size: 1.125rem;
}

/* Type Selection */
.type-selection {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
  gap: 2rem;
  margin-bottom: 3rem;
}

.type-card {
  padding: 2.5rem;
  border: 2px solid #e2e8f0;
  border-radius: 20px;
  text-align: center;
  cursor: pointer;
  transition: all 0.4s ease;
  position: relative;
  background: white;
}

.type-card:hover {
  transform: translateY(-5px);
  border-color: #cbd5e1;
  box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
}

.type-card.selected {
  border-color: #6366f1;
  background: linear-gradient(135deg, rgba(99, 102, 241, 0.05) 0%, rgba(168, 85, 247, 0.05) 100%);
  transform: translateY(-5px);
  box-shadow: 0 15px 30px rgba(99, 102, 241, 0.15);
}

.type-icon {
  font-size: 3.5rem;
  margin-bottom: 1rem;
}

.type-title {
  font-size: 1.5rem;
  font-weight: 700;
  color: #1e293b;
  margin-bottom: 1rem;
}

.type-description {
  color: #64748b;
  line-height: 1.6;
  margin-bottom: 1.5rem;
}

.type-features {
  list-style: none;
  text-align: left;
  margin-bottom: 1.5rem;
}

.type-features li {
  color: #475569;
  padding: 0.5rem 0;
  border-bottom: 1px solid #f1f5f9;
}

.type-features li:last-child {
  border-bottom: none;
}

.type-badge {
  display: inline-block;
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  color: white;
  padding: 0.5rem 1rem;
  border-radius: 50px;
  font-size: 0.875rem;
  font-weight: 600;
}

/* Forms */
.info-form, .address-form, .pro-form {
  margin-bottom: 2rem;
}

.form-row {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 1.5rem;
  margin-bottom: 1.5rem;
}

.form-group {
  margin-bottom: 1.5rem;
}

.form-label {
  display: block;
  font-weight: 600;
  color: #475569;
  margin-bottom: 0.5rem;
  font-size: 0.875rem;
}

.input-with-icon {
  position: relative;
}

.form-input {
  width: 100%;
  padding: 1rem;
  border: 2px solid #e2e8f0;
  border-radius: 12px;
  font-size: 1rem;
  transition: all 0.3s ease;
  background: #f8fafc;
}

.form-input:focus {
  outline: none;
  border-color: #6366f1;
  background: white;
  box-shadow: 0 0 0 4px rgba(99, 102, 241, 0.1);
}

.form-input.error {
  border-color: #ef4444;
}

.input-icon {
  position: absolute;
  left: 1rem;
  top: 50%;
  transform: translateY(-50%);
  font-size: 1.25rem;
  color: #94a3b8;
}

.password-toggle {
  position: absolute;
  right: 1rem;
  top: 50%;
  transform: translateY(-50%);
  background: none;
  border: none;
  font-size: 1.25rem;
  cursor: pointer;
  color: #94a3b8;
}

.password-toggle:hover {
  color: #6366f1;
}

.error-message {
  color: #ef4444;
  font-size: 0.875rem;
  margin-top: 0.5rem;
}

/* Section blocks */
.section-block {
  margin: 2rem 0;
  padding: 2rem;
  background: #f8fafc;
  border-radius: 16px;
  border: 2px solid #e2e8f0;
}

.section-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 1.5rem;
}

.section-title {
  font-size: 1.25rem;
  font-weight: 700;
  color: #1e293b;
  margin-bottom: 1.5rem;
}

.toggle-btn {
  background: none;
  border: none;
  color: #6366f1;
  font-size: 0.875rem;
  font-weight: 600;
  cursor: pointer;
  padding: 0.5rem 1rem;
  border-radius: 8px;
  transition: all 0.3s ease;
}

.toggle-btn:hover {
  background: rgba(99, 102, 241, 0.1);
}

.delivery-address {
  padding-top: 1.5rem;
  border-top: 2px solid #e2e8f0;
  margin-top: 1rem;
}

/* Commune form */
.commune-form {
  margin-top: 1.5rem;
  padding: 1.5rem;
  background: white;
  border-radius: 12px;
  border: 1px solid #e2e8f0;
}

.commune-title {
  font-size: 1rem;
  font-weight: 600;
  color: #475569;
  margin-bottom: 1rem;
  padding-bottom: 0.5rem;
  border-bottom: 1px solid #e2e8f0;
}

/* Submit section */
.submit-section {
  margin-top: 3rem;
}

.terms-section {
  margin: 2rem 0;
  padding: 1.5rem 0;
  border-top: 2px solid #e2e8f0;
  border-bottom: 2px solid #e2e8f0;
}

.checkbox-container {
  display: flex;
  align-items: flex-start;
  cursor: pointer;
}

.checkbox-container input {
  display: none;
}

.checkmark {
  width: 24px;
  height: 24px;
  border: 2px solid #cbd5e1;
  border-radius: 6px;
  margin-right: 1rem;
  margin-top: 0.25rem;
  flex-shrink: 0;
  position: relative;
  transition: all 0.3s ease;
}

.checkbox-container input:checked + .checkmark {
  background: #6366f1;
  border-color: #6366f1;
}

.checkbox-container input:checked + .checkmark::after {
  content: '✓';
  position: absolute;
  color: white;
  font-size: 1rem;
  top: 50%;
  left: 50%;
  transform: translate(-50%, -50%);
}

.checkbox-text {
  color: #475569;
  line-height: 1.6;
}

.terms-link {
  color: #6366f1;
  text-decoration: none;
  font-weight: 600;
}

.terms-link:hover {
  text-decoration: underline;
}

.error-alert {
  display: flex;
  align-items: center;
  gap: 1rem;
  background: #fef2f2;
  border: 1px solid #fee2e2;
  border-radius: 12px;
  padding: 1rem;
  margin: 1.5rem 0;
}

.alert-icon {
  font-size: 1.5rem;
  flex-shrink: 0;
}

.alert-content {
  color: #b91c1c;
  font-size: 0.875rem;
}

.form-actions {
  display: flex;
  justify-content: space-between;
  gap: 1rem;
}

.back-btn {
  padding: 1rem 2rem;
  border: 2px solid #e2e8f0;
  border-radius: 12px;
  background: white;
  color: #475569;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.3s ease;
  min-width: 150px;
}

.back-btn:hover {
  border-color: #cbd5e1;
  background: #f8fafc;
}

.next-btn, .submit-btn {
  padding: 1rem 2.5rem;
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  color: white;
  border: none;
  border-radius: 12px;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.3s ease;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 0.75rem;
  min-width: 150px;
}

.next-btn:hover:not(:disabled),
.submit-btn:hover:not(:disabled) {
  transform: translateY(-2px);
  box-shadow: 0 10px 25px rgba(102, 126, 234, 0.4);
}

.next-btn:disabled,
.submit-btn:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}

.btn-arrow {
  font-size: 1.25rem;
  transition: transform 0.3s ease;
}

.next-btn:hover .btn-arrow {
  transform: translateX(5px);
}

.submit-btn.loading {
  pointer-events: none;
}

.loading-spinner {
  display: inline-block;
  width: 20px;
  height: 20px;
  border: 3px solid rgba(255, 255, 255, 0.3);
  border-radius: 50%;
  border-top-color: white;
  animation: spin 1s ease-in-out infinite;
}

.login-link {
  text-align: center;
  margin-top: 2rem;
  color: #64748b;
}

.login-link a {
  color: #6366f1;
  font-weight: 600;
  text-decoration: none;
}

.login-link a:hover {
  text-decoration: underline;
}

@keyframes slideIn {
  from {
    opacity: 0;
    transform: translateX(30px);
  }
  to {
    opacity: 1;
    transform: translateX(0);
  }
}

@keyframes spin {
  to {
    transform: rotate(360deg);
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

@media (max-width: 768px) {
  .register-wrapper {
    padding: 2rem 1.5rem;
  }
  
  .form-row {
    grid-template-columns: 1fr;
  }
  
  .type-selection {
    grid-template-columns: 1fr;
  }
  
  .step-title {
    font-size: 2rem;
  }
  
  .form-actions {
    flex-direction: column;
  }
}
</style>