<template>
  <div class="profile-container">
    <!-- Loading initial -->
    <div v-if="initialLoading" class="loading-container">
      <div class="loading-spinner"></div>
      <p>Chargement de votre profil...</p>
    </div>

    <div v-else>
      <!-- En-tête du profil -->
      <div class="profile-header">
        <div class="header-content">
          <h1>Mon Profil</h1>
          <p>Gérez vos informations personnelles et vos préférences</p>
          <div class="user-type-badge" :class="userType">
            {{ userType === 'particulier' ? '👤 Particulier' : '🏢 Professionnel' }}
          </div>
        </div>
        <div class="header-actions">
          <button @click="logout" class="logout-btn">
            <span class="logout-icon">🚪</span>
            Déconnexion
          </button>
        </div>
      </div>

      <div class="profile-content">
        <!-- Menu latéral -->
        <div class="profile-sidebar">
          <nav class="profile-menu">
            <button
              v-for="tab in tabs"
              :key="tab.id"
              @click="activeTab = tab.id"
              class="menu-item"
              :class="{ 'active': activeTab === tab.id }"
            >
              <span class="menu-icon">{{ tab.icon }}</span>
              <span class="menu-label">{{ tab.label }}</span>
              <span v-if="tab.id === 'orders' && commandes.length > 0" class="menu-badge">
                {{ commandes.length }}
              </span>
            </button>
          </nav>
        </div>

        <!-- Contenu principal -->
        <div class="profile-main">

          <!-- ===================== ONGLET INFORMATIONS ===================== -->
          <div v-if="activeTab === 'info'" class="tab-content">
            <div class="tab-header">
              <h2>Informations personnelles</h2>
              <p>Modifiez vos coordonnées et vos préférences</p>
            </div>

            <!-- PARTICULIER -->
            <form v-if="userType === 'particulier'" @submit.prevent="updateProfile" class="profile-form">
              <div class="form-section">
                <h3>Informations de base</h3>
                <div class="form-grid">
                  <div class="form-group">
                    <label for="nom">Nom *</label>
                    <input id="nom" v-model="form.nom" type="text" required :class="{ 'error': errors.nom }">
                    <p v-if="errors.nom" class="error-message">{{ errors.nom[0] }}</p>
                  </div>
                  <div class="form-group">
                    <label for="prenom">Prénom *</label>
                    <input id="prenom" v-model="form.prenom" type="text" required :class="{ 'error': errors.prenom }">
                    <p v-if="errors.prenom" class="error-message">{{ errors.prenom[0] }}</p>
                  </div>
                  <div class="form-group full-width">
                    <label for="email">Email *</label>
                    <input id="email" v-model="form.email" type="email" required readonly class="readonly-field">
                    <p class="field-help">L'email ne peut pas être modifié</p>
                  </div>
                </div>
              </div>

              <div class="form-section">
                <h3>Adresse</h3>
                <div class="form-group">
                  <label for="adresse">Adresse complète *</label>
                  <textarea id="adresse" v-model="form.adresse" rows="3" required
                    placeholder="Rue, numéro, code postal, ville, pays"
                    :class="{ 'error': errors.adresse }"></textarea>
                  <p v-if="errors.adresse" class="error-message">{{ errors.adresse[0] }}</p>
                </div>
              </div>

              <div class="form-section">
                <h3>Préférences</h3>
                <div class="form-group">
                  <label for="langue_id">Langue préférée *</label>
                  <select id="langue_id" v-model="form.langue_id" required :class="{ 'error': errors.langue_id }">
                    <option value="">Sélectionnez une langue</option>
                    <option v-for="langue in langues" :key="langue.id" :value="langue.id">{{ langue.langue }}</option>
                  </select>
                  <p v-if="errors.langue_id" class="error-message">{{ errors.langue_id[0] }}</p>
                </div>
              </div>

              <div class="form-actions">
                <button type="button" @click="resetForm" class="cancel-btn">Annuler</button>
                <button type="submit" :disabled="loading" class="save-btn">
                  <span v-if="!loading">Enregistrer les modifications</span>
                  <span v-else class="loading-spinner"></span>
                </button>
              </div>
            </form>

            <!-- PROFESSIONNEL -->
            <form v-else @submit.prevent="updateProfile" class="profile-form">
              <div class="form-section">
                <h3>Informations de l'entreprise</h3>
                <div class="form-grid">
                  <div class="form-group full-width">
                    <label for="nom_societe">Nom de la société *</label>
                    <input id="nom_societe" v-model="form.nom_societe" type="text" required :class="{ 'error': errors.nom_societe }">
                    <p v-if="errors.nom_societe" class="error-message">{{ errors.nom_societe[0] }}</p>
                  </div>
                  <div class="form-group full-width">
                    <label for="email">Email *</label>
                    <input id="email" v-model="form.email" type="email" required readonly class="readonly-field">
                    <p class="field-help">L'email ne peut pas être modifié</p>
                  </div>
                </div>
              </div>

              <div class="form-section">
                <h3>Coordonnées</h3>
                <div class="form-group">
                  <label for="telephone">Téléphone</label>
                  <input id="telephone" v-model="form.telephone" type="tel" placeholder="Ex: 01 23 45 67 89" :class="{ 'error': errors.telephone }">
                  <p v-if="errors.telephone" class="error-message">{{ errors.telephone[0] }}</p>
                </div>
              </div>

              <div class="form-section">
                <h3>Horaires d'ouverture</h3>
                <div class="form-grid">
                  <div class="form-group">
                    <label for="heure_ouverture">Heure d'ouverture *</label>
                    <input id="heure_ouverture" v-model="form.heure_ouverture" type="time" required :class="{ 'error': errors.heure_ouverture }">
                    <p v-if="errors.heure_ouverture" class="error-message">{{ errors.heure_ouverture[0] }}</p>
                  </div>
                  <div class="form-group">
                    <label for="heure_fermeture">Heure de fermeture *</label>
                    <input id="heure_fermeture" v-model="form.heure_fermeture" type="time" required :class="{ 'error': errors.heure_fermeture }">
                    <p v-if="errors.heure_fermeture" class="error-message">{{ errors.heure_fermeture[0] }}</p>
                  </div>
                </div>
              </div>

              <div class="form-section">
                <h3>Adresses</h3>
                <div class="address-display-card">
                  <div class="address-header"><h4>🏢 Adresse du siège</h4></div>
                  <p v-if="form.adresse_siege" class="address-text">{{ form.adresse_siege }}</p>
                  <p v-else class="no-address">Aucune adresse de siège enregistrée</p>
                </div>
                <div class="address-display-card">
                  <div class="address-header"><h4>📦 Adresse de livraison par défaut</h4></div>
                  <p v-if="form.adresse_livraison_defaut" class="address-text">{{ form.adresse_livraison_defaut }}</p>
                  <p v-else class="no-address">Aucune adresse de livraison enregistrée</p>
                </div>
                <p class="field-help">ℹ️ Pour modifier vos adresses, veuillez contacter notre service client.</p>
              </div>

              <div class="form-section" v-if="form.contact">
                <h3>Contact professionnel</h3>
                <div class="form-grid">
                  <div class="form-group">
                    <label>Nom du contact</label>
                    <input v-model="form.contact.nom" type="text" readonly class="readonly-field">
                  </div>
                  <div class="form-group">
                    <label>Prénom du contact</label>
                    <input v-model="form.contact.prenom" type="text" readonly class="readonly-field">
                  </div>
                  <div class="form-group">
                    <label>Téléphone</label>
                    <input v-model="form.contact.telephone" type="tel" readonly class="readonly-field">
                  </div>
                  <div class="form-group">
                    <label>Email</label>
                    <input v-model="form.contact.email" type="email" readonly class="readonly-field">
                  </div>
                </div>
                <p class="field-help">ℹ️ Pour modifier les informations de contact, veuillez contacter notre service client.</p>
              </div>

              <div class="form-section">
                <h3>Préférences</h3>
                <div class="form-group">
                  <label for="langue_id">Langue préférée *</label>
                  <select id="langue_id" v-model="form.langue_id" required :class="{ 'error': errors.langue_id }">
                    <option value="">Sélectionnez une langue</option>
                    <option v-for="langue in langues" :key="langue.id" :value="langue.id">{{ langue.langue }}</option>
                  </select>
                  <p v-if="errors.langue_id" class="error-message">{{ errors.langue_id[0] }}</p>
                </div>
              </div>

              <div class="form-actions">
                <button type="button" @click="resetForm" class="cancel-btn">Annuler</button>
                <button type="submit" :disabled="loading" class="save-btn">
                  <span v-if="!loading">Enregistrer les modifications</span>
                  <span v-else class="loading-spinner"></span>
                </button>
              </div>
            </form>
          </div>

          <!-- ===================== ONGLET COMMANDES ===================== -->
          <div v-else-if="activeTab === 'orders'" class="tab-content">
            <div class="tab-header">
              <h2>Mes Commandes</h2>
              <p>Historique de toutes vos locations</p>
            </div>

            <div v-if="loadingCommandes" class="loading-small">
              <div class="loading-spinner"></div>
              <p>Chargement de vos commandes...</p>
            </div>

            <div v-else-if="commandes.length === 0" class="empty-state">
              <div class="empty-icon">📦</div>
              <h3>Aucune commande pour le moment</h3>
              <p>Commencez à explorer notre catalogue</p>
              <router-link to="/catalogue" class="browse-btn">Parcourir le catalogue</router-link>
            </div>

            <div v-else class="orders-list">
              <div v-for="commande in commandes" :key="commande.id" class="order-card">
                <!-- En-tête commande -->
                <div class="order-header">
                  <div class="order-info">
                    <h4 class="order-number">Commande #{{ commande.numero_commande }}</h4>
                    <div class="order-dates">
                      <span class="order-date">📅 {{ formatDate(commande.date_commande) }}</span>
                      <span class="order-period">
                        Du {{ formatDate(commande.date_debut) }} au {{ formatDate(commande.date_fin) }}
                        <strong>({{ getNombreJours(commande) }} jour{{ getNombreJours(commande) > 1 ? 's' : '' }})</strong>
                      </span>
                    </div>
                  </div>
                  <div class="order-status" :class="getStatusClass(commande.statut)">
                    {{ getStatusLabel(commande.statut) }}
                  </div>
                </div>

                <!-- Détails commande -->
                <div class="order-details">
                  <!-- Items -->
                  <div class="order-items">
                    <div v-for="item in getMateriels(commande)" :key="item.id" class="order-item">
                      <div class="item-image-wrapper">
                        <img
                          :src="getItemImage(item)"
                          :alt="item.nom"
                          class="item-image"
                          @error="(e) => handleImageError(e, item)"
                          :data-item-name="item.nom"
                        >
                      </div>
                      <div class="item-info">
                        <h5>{{ item.nom }}</h5>
                        <p class="item-details">
                          <span class="item-quantity">{{ getQuantite(item) }} × {{ formatPrice(getPrixUnitaireHT(item)) }}</span>
                          <span class="item-duration">× {{ getNombreJours(commande) }} jour{{ getNombreJours(commande) > 1 ? 's' : '' }}</span>
                        </p>
                        <p class="item-taxes" v-if="item.pivot?.taux_tva">
                          TVA {{ item.pivot.taux_tva }}% incluse
                        </p>
                      </div>
                      <div class="item-pricing">
                        <div class="item-subtotal-ht">{{ formatPrice(getItemSousTotalHT(item, commande)) }} HT</div>
                        <div class="item-subtotal-ttc">{{ formatPrice(getItemSousTotalTTC(item, commande)) }} TTC</div>
                      </div>
                    </div>
                  </div>

                  <!-- Récapitulatif financier détaillé -->
                  <div class="order-summary">
                    <div class="summary-group">
                      <h4>Détail des montants</h4>
                      <div class="summary-row">
                        <span>Sous-total HT</span>
                        <span>{{ formatPrice(calculateSubtotalHT(commande)) }}</span>
                      </div>
                      <div class="summary-row">
                        <span>TVA ({{ getTauxTVAMoyen(commande) }}%)</span>
                        <span>{{ formatPrice(calculateTVA(commande)) }}</span>
                      </div>
                      <div class="summary-row highlight">
                        <span>Sous-total TTC</span>
                        <span>{{ formatPrice(calculateSubtotalTTC(commande)) }}</span>
                      </div>
                    </div>

                    <div class="summary-group" v-if="commande.frais_livraison > 0 || commande.frais_retour > 0">
                      <h4>Frais</h4>
                      <div class="summary-row" v-if="commande.frais_livraison > 0">
                        <span>Livraison</span>
                        <span>{{ formatPrice(commande.frais_livraison) }}</span>
                      </div>
                      <div class="summary-row" v-if="commande.frais_retour > 0">
                        <span>Retour</span>
                        <span>{{ formatPrice(commande.frais_retour) }}</span>
                      </div>
                    </div>

                    <div class="summary-group" v-if="commande.code_reduction">
                      <h4>Réduction</h4>
                      <div class="summary-row discount">
                        <span>Code "{{ commande.code_reduction.code }}"</span>
                        <span>-{{ formatPrice(calculateRemise(commande)) }}</span>
                      </div>
                    </div>

                    <div class="summary-divider"></div>

                    <div class="summary-total">
                      <div class="total-row">
                        <span>Total TTC</span>
                        <span class="total-price">{{ formatPrice(commande.montant_total) }}</span>
                      </div>
                      <div class="total-details" v-if="commande.montant_total !== calculateTotalAvantRemise(commande)">
                        <small>Dont {{ formatPrice(commande.montant_total - calculateSubtotalHT(commande)) }} de taxes</small>
                      </div>
                    </div>
                  </div>
                </div>

                <!-- Actions -->
                <div class="order-actions">
                  <button @click="viewOrder(commande.id)" class="view-btn">
                    <span class="btn-icon">👁️</span>
                    Voir les détails
                  </button>
                  <button 
                    v-if="canCancelOrder(commande.statut)" 
                    @click="cancelOrder(commande.id)" 
                    class="cancel-order-btn"
                  >
                    <span class="btn-icon">❌</span>
                    Annuler la commande
                  </button>
                </div>
              </div>
            </div>
          </div>

          <!-- ===================== ONGLET SÉCURITÉ ===================== -->
          <div v-else-if="activeTab === 'security'" class="tab-content">
            <div class="tab-header">
              <h2>Sécurité du compte</h2>
              <p>Gérez votre mot de passe et la sécurité de votre compte</p>
            </div>

            <!-- Statistiques de sécurité -->
            <div class="security-stats">
              <div class="stat-card">
                <div class="stat-icon">🔐</div>
                <div class="stat-info">
                  <div class="stat-value">Dernière connexion</div>
                  <div class="stat-label">{{ userData?.last_login ? formatDate(userData.last_login) : 'Aujourd\'hui' }}</div>
                </div>
              </div>
              <div class="stat-card">
                <div class="stat-icon">🛡️</div>
                <div class="stat-info">
                  <div class="stat-value">Méthode 2FA</div>
                  <div class="stat-label">Non activée</div>
                </div>
              </div>
              <div class="stat-card">
                <div class="stat-icon">📱</div>
                <div class="stat-info">
                  <div class="stat-value">Appareils connectés</div>
                  <div class="stat-label">1 appareil</div>
                </div>
              </div>
            </div>

            <form @submit.prevent="updatePassword" class="security-form">
              <div class="form-section">
                <h3>Changer le mot de passe</h3>

                <div class="form-group">
                  <label for="current_password">Mot de passe actuel *</label>
                  <div class="password-input">
                    <input 
                      id="current_password" 
                      v-model="passwordForm.current_password"
                      :type="showCurrentPassword ? 'text' : 'password'" 
                      required
                      :class="{ 'error': passwordErrors.current_password }"
                      placeholder="Entrez votre mot de passe actuel"
                    >
                    <button type="button" @click="showCurrentPassword = !showCurrentPassword" class="password-toggle">
                      {{ showCurrentPassword ? '🙈' : '👁️' }}
                    </button>
                  </div>
                  <p v-if="passwordErrors.current_password" class="error-message">{{ passwordErrors.current_password[0] }}</p>
                </div>

                <div class="form-group">
                  <label for="new_password">Nouveau mot de passe *</label>
                  <div class="password-input">
                    <input 
                      id="new_password" 
                      v-model="passwordForm.new_password"
                      :type="showNewPassword ? 'text' : 'password'" 
                      required
                      minlength="8"
                      :class="{ 'error': passwordErrors.new_password }"
                      placeholder="8 caractères minimum"
                    >
                    <button type="button" @click="showNewPassword = !showNewPassword" class="password-toggle">
                      {{ showNewPassword ? '🙈' : '👁️' }}
                    </button>
                  </div>
                  <p v-if="passwordErrors.new_password" class="error-message">{{ passwordErrors.new_password[0] }}</p>
                  
                  <!-- Indicateur de force du mot de passe -->
                  <div class="password-strength" v-if="passwordForm.new_password">
                    <div class="strength-bar" :class="getPasswordStrength(passwordForm.new_password)"></div>
                    <span class="strength-text">Force : {{ getPasswordStrengthText(passwordForm.new_password) }}</span>
                  </div>
                </div>

                <div class="form-group">
                  <label for="new_password_confirmation">Confirmer le nouveau mot de passe *</label>
                  <div class="password-input">
                    <input 
                      id="new_password_confirmation" 
                      v-model="passwordForm.new_password_confirmation"
                      :type="showConfirmPassword ? 'text' : 'password'" 
                      required
                      :class="{ 'error': passwordErrors.new_password_confirmation }"
                      placeholder="Répétez le nouveau mot de passe"
                    >
                    <button type="button" @click="showConfirmPassword = !showConfirmPassword" class="password-toggle">
                      {{ showConfirmPassword ? '🙈' : '👁️' }}
                    </button>
                  </div>
                  <p v-if="passwordErrors.new_password_confirmation" class="error-message">{{ passwordErrors.new_password_confirmation[0] }}</p>
                </div>

                <div class="password-requirements">
                  <p><strong>Le mot de passe doit contenir :</strong></p>
                  <ul>
                    <li :class="{ 'valid': passwordForm.new_password.length >= 8 }">
                      {{ passwordForm.new_password.length >= 8 ? '✅' : '❌' }} Au moins 8 caractères
                    </li>
                    <li :class="{ 'valid': /[A-Z]/.test(passwordForm.new_password) }">
                      {{ /[A-Z]/.test(passwordForm.new_password) ? '✅' : '❌' }} Une majuscule
                    </li>
                    <li :class="{ 'valid': /[a-z]/.test(passwordForm.new_password) }">
                      {{ /[a-z]/.test(passwordForm.new_password) ? '✅' : '❌' }} Une minuscule
                    </li>
                    <li :class="{ 'valid': /[0-9]/.test(passwordForm.new_password) }">
                      {{ /[0-9]/.test(passwordForm.new_password) ? '✅' : '❌' }} Un chiffre
                    </li>
                    <li :class="{ 'valid': /[^A-Za-z0-9]/.test(passwordForm.new_password) }">
                      {{ /[^A-Za-z0-9]/.test(passwordForm.new_password) ? '✅' : '❌' }} Un caractère spécial
                    </li>
                  </ul>
                </div>
              </div>

              <div class="form-actions">
                <button type="submit" :disabled="passwordLoading" class="save-btn">
                  <span v-if="!passwordLoading">Mettre à jour le mot de passe</span>
                  <span v-else class="loading-spinner"></span>
                </button>
              </div>
            </form>

            <!-- Section de suppression du compte -->
            <div class="danger-zone">
              <h3>Zone dangereuse</h3>
              <div class="danger-card">
                <div class="danger-icon">⚠️</div>
                <div class="danger-content">
                  <h4>Supprimer mon compte</h4>
                  <p>Cette action est irréversible. Toutes vos données seront supprimées définitivement.</p>
                  <button @click="deleteAccount" class="delete-account-btn">
                    Supprimer mon compte
                  </button>
                </div>
              </div>
            </div>
          </div>

          <!-- ===================== ONGLET NOTIFICATIONS ===================== -->
          <div v-else-if="activeTab === 'notifications'" class="tab-content">
            <div class="tab-header">
              <h2>Préférences de notifications</h2>
              <p>Choisissez les notifications que vous souhaitez recevoir</p>
            </div>

            <form @submit.prevent="updateNotifications" class="notifications-form">
              <!-- Notifications par email -->
              <div class="form-section">
                <h3>Notifications par email</h3>
                <div class="checkbox-group">
                  <label class="checkbox-item">
                    <input type="checkbox" v-model="notifications.email.commandes" class="checkbox-input">
                    <span class="checkbox-label">
                      <span class="checkbox-title">📦 Nouvelles commandes</span>
                      <span class="checkbox-description">Recevoir un email lors de la création d'une nouvelle commande</span>
                    </span>
                  </label>

                  <label class="checkbox-item">
                    <input type="checkbox" v-model="notifications.email.statut" class="checkbox-input">
                    <span class="checkbox-label">
                      <span class="checkbox-title">🔄 Changements de statut</span>
                      <span class="checkbox-description">Être informé des mises à jour de vos commandes (confirmation, préparation, livraison)</span>
                    </span>
                  </label>

                  <label class="checkbox-item">
                    <input type="checkbox" v-model="notifications.email.promotions" class="checkbox-input">
                    <span class="checkbox-label">
                      <span class="checkbox-title">🎁 Promotions et offres spéciales</span>
                      <span class="checkbox-description">Recevoir nos offres exclusives et codes promo</span>
                    </span>
                  </label>

                  <label class="checkbox-item">
                    <input type="checkbox" v-model="notifications.email.newsletter" class="checkbox-input">
                    <span class="checkbox-label">
                      <span class="checkbox-title">📧 Newsletter</span>
                      <span class="checkbox-description">Recevoir nos actualités, conseils et nouveautés</span>
                    </span>
                  </label>
                </div>
              </div>

              <!-- Notifications SMS -->
              <div class="form-section">
                <h3>Notifications par SMS</h3>
                <div class="checkbox-group">
                  <label class="checkbox-item">
                    <input type="checkbox" v-model="notifications.sms.confirmation" class="checkbox-input">
                    <span class="checkbox-label">
                      <span class="checkbox-title">✅ Confirmation de commande</span>
                      <span class="checkbox-description">Recevoir un SMS de confirmation après chaque commande</span>
                    </span>
                  </label>

                  <label class="checkbox-item">
                    <input type="checkbox" v-model="notifications.sms.livraison" class="checkbox-input">
                    <span class="checkbox-label">
                      <span class="checkbox-title">🚚 Suivi de livraison</span>
                      <span class="checkbox-description">Recevoir des SMS lors de la livraison de vos commandes</span>
                    </span>
                  </label>

                  <label class="checkbox-item" v-if="userType === 'professionnel'">
                    <input type="checkbox" v-model="notifications.sms.alerte" class="checkbox-input">
                    <span class="checkbox-label">
                      <span class="checkbox-title">⚠️ Alertes professionnelles</span>
                      <span class="checkbox-description">Recevoir des alertes importantes concernant vos locations</span>
                    </span>
                  </label>
                </div>
              </div>

              <!-- Fréquence des notifications -->
              <div class="form-section">
                <h3>Fréquence des notifications</h3>
                <div class="radio-group">
                  <label class="radio-item">
                    <input type="radio" v-model="notifications.frequence" value="instant" class="radio-input">
                    <span class="radio-label">
                      <span class="radio-title">📱 Instantané</span>
                      <span class="radio-description">Recevoir les notifications immédiatement</span>
                    </span>
                  </label>

                  <label class="radio-item">
                    <input type="radio" v-model="notifications.frequence" value="daily" class="radio-input">
                    <span class="radio-label">
                      <span class="radio-title">📅 Résumé quotidien</span>
                      <span class="radio-description">Recevoir un résumé des notifications une fois par jour</span>
                    </span>
                  </label>

                  <label class="radio-item">
                    <input type="radio" v-model="notifications.frequence" value="weekly" class="radio-input">
                    <span class="radio-label">
                      <span class="radio-title">📊 Résumé hebdomadaire</span>
                      <span class="radio-description">Recevoir un résumé des notifications une fois par semaine</span>
                    </span>
                  </label>
                </div>
              </div>

              <div class="form-actions">
                <button type="submit" :disabled="notificationsLoading" class="save-btn">
                  <span v-if="!notificationsLoading">Enregistrer les préférences</span>
                  <span v-else class="loading-spinner"></span>
                </button>
              </div>
            </form>
          </div>

        </div><!-- /profile-main -->
      </div><!-- /profile-content -->
    </div>

    <!-- Messages d'alerte -->
    <transition name="alert-slide">
      <div v-if="successMessage" class="alert success">
        <span class="alert-icon">✅</span>
        <span class="alert-message">{{ successMessage }}</span>
        <button @click="successMessage = ''" class="alert-close">×</button>
      </div>
    </transition>

    <transition name="alert-slide">
      <div v-if="errorMessage" class="alert error">
        <span class="alert-icon">⚠️</span>
        <span class="alert-message">{{ errorMessage }}</span>
        <button @click="errorMessage = ''" class="alert-close">×</button>
      </div>
    </transition>
  </div>
</template>

<script setup>
import { ref, onMounted, computed } from 'vue'
import { useRouter } from 'vue-router'
import api from '@/services/axios'

const router = useRouter()
const BASE_URL = import.meta.env.VITE_API_BASE_URL || 'http://127.0.0.1:8000'

// ============================================
// ÉTATS
// ============================================
const activeTab = ref('info')
const initialLoading = ref(true)
const loading = ref(false)
const loadingCommandes = ref(false)
const passwordLoading = ref(false)
const notificationsLoading = ref(false)
const successMessage = ref('')
const errorMessage = ref('')
const errors = ref({})
const passwordErrors = ref({})

const userData = ref(null)
const langues = ref([])
const commandes = ref([])

const form = ref({
  email: '',
  langue_id: '',
  nom: '',
  prenom: '',
  adresse: '',
  nom_societe: '',
  telephone: '',
  heure_ouverture: '',
  heure_fermeture: '',
  adresse_siege: '',
  adresse_livraison_defaut: '',
  contact: null
})

const passwordForm = ref({
  current_password: '',
  new_password: '',
  new_password_confirmation: ''
})

const notifications = ref({
  email: {
    commandes: true,
    statut: true,
    promotions: true,
    newsletter: true
  },
  sms: {
    confirmation: false,
    livraison: false,
    alerte: false
  },
  frequence: 'instant'
})

const showCurrentPassword = ref(false)
const showNewPassword = ref(false)
const showConfirmPassword = ref(false)

const tabs = [
  { id: 'info',          label: 'Informations', icon: '👤' },
  { id: 'orders',        label: 'Commandes',    icon: '📦' },
  { id: 'security',      label: 'Sécurité',     icon: '🔒' },
  { id: 'notifications', label: 'Notifications',icon: '🔔' }
]

const userType = computed(() => userData.value?.type || 'particulier')

// ============================================
// UTILITAIRES IMAGES
// ============================================

const stringToColor = (str) => {
  if (!str) return 'hsl(200,70%,80%)'
  let hash = 0
  for (let i = 0; i < str.length; i++) hash = str.charCodeAt(i) + ((hash << 5) - hash)
  return `hsl(${Math.abs(hash % 360)},70%,80%)`
}

const getInitialsImage = (nom) => {
  const initial = (nom && nom.length > 0) ? nom.charAt(0).toUpperCase() : '?'
  const color = encodeURIComponent(stringToColor(nom || ''))
  return `data:image/svg+xml;utf8,<svg xmlns='http://www.w3.org/2000/svg' width='60' height='60' viewBox='0 0 60 60'><rect width='60' height='60' fill='${color}'/><text x='30' y='38' font-size='24' text-anchor='middle' fill='%23333' font-family='Arial,sans-serif' font-weight='bold'>${initial}</text></svg>`
}

const buildImageUrl = (path) => {
  if (!path) return null
  const p = String(path).trim()
  if (p.startsWith('http://') || p.startsWith('https://')) return p
  const clean = p.replace(/^\/+/, '').replace(/^storage\//, '')
  return `${BASE_URL}/storage/${clean}`
}

const getItemImage = (item) => {
  if (!item) return getInitialsImage('')

  try {
    if (Array.isArray(item.photos) && item.photos.length > 0) {
      for (const photo of item.photos) {
        if (photo.url_photo) return photo.url_photo
        const raw = photo.chemin_fichier || photo.path || (typeof photo === 'string' ? photo : null)
        const url = buildImageUrl(raw)
        if (url) return url
      }
    }

    if (item.photo) {
      const url = buildImageUrl(item.photo)
      if (url) return url
    }

    if (item.pivot?.photo_url) {
      const url = buildImageUrl(item.pivot.photo_url)
      if (url) return url
    }

  } catch (e) {
    console.error('[getItemImage] Erreur:', e)
  }

  return getInitialsImage(item.nom)
}

const handleImageError = (event, item) => {
  const img = event.target
  if (img.src.startsWith('data:image')) return
  img.onerror = null
  img.src = getInitialsImage(item?.nom || 'Produit')
}

// ============================================
// UTILITAIRES COMMANDES
// ============================================

const getMateriels = (commande) => commande.materiels || commande.articles || []

const getQuantite = (item) => {
  return parseInt(item.pivot?.quantite ?? item.quantite ?? 1)
}

const getPrixUnitaireHT = (item) => {
  return parseFloat(item.pivot?.prix_unitaire_ht ?? item.prix_unitaire_ht ?? item.prix_journalier ?? 0)
}

const getPrixUnitaireTTC = (item) => {
  return parseFloat(item.pivot?.prix_unitaire_ttc ?? item.prix_unitaire_ttc ?? 0) || 
         getPrixUnitaireHT(item) * (1 + (getTauxTVA(item) / 100))
}

const getTauxTVA = (item) => {
  return parseFloat(item.pivot?.taux_tva ?? item.taux_tva ?? 20)
}

const getNombreJours = (commande) => {
  if (!commande.date_debut || !commande.date_fin) return 1
  const debut = new Date(commande.date_debut)
  const fin = new Date(commande.date_fin)
  const diff = Math.ceil((fin - debut) / (1000 * 60 * 60 * 24))
  return diff > 0 ? diff + 1 : 1
}

const getItemSousTotalHT = (item, commande) => {
  const fromPivot = parseFloat(item.pivot?.sous_total_ht ?? NaN)
  if (!isNaN(fromPivot) && fromPivot > 0) return fromPivot

  const prix = getPrixUnitaireHT(item)
  const qte = getQuantite(item)
  const jours = getNombreJours(commande)
  return prix * qte * jours
}

const getItemSousTotalTTC = (item, commande) => {
  const fromPivot = parseFloat(item.pivot?.sous_total_ttc ?? NaN)
  if (!isNaN(fromPivot) && fromPivot > 0) return fromPivot

  const ht = getItemSousTotalHT(item, commande)
  const tva = ht * (getTauxTVA(item) / 100)
  return ht + tva
}

// ============================================
// CALCULS RÉCAPITULATIF COMMANDE
// ============================================

const calculateSubtotalHT = (commande) => {
  const materiels = getMateriels(commande)
  if (!materiels.length) return 0
  return materiels.reduce((total, item) => total + getItemSousTotalHT(item, commande), 0)
}

const calculateSubtotalTTC = (commande) => {
  const materiels = getMateriels(commande)
  if (!materiels.length) return 0
  return materiels.reduce((total, item) => total + getItemSousTotalTTC(item, commande), 0)
}

const calculateTVA = (commande) => {
  return calculateSubtotalTTC(commande) - calculateSubtotalHT(commande)
}

const getTauxTVAMoyen = (commande) => {
  const ht = calculateSubtotalHT(commande)
  if (ht === 0) return 0
  const tva = calculateTVA(commande)
  return Math.round((tva / ht) * 100)
}

const calculateFraisTotaux = (commande) => {
  return (parseFloat(commande.frais_livraison) || 0) + (parseFloat(commande.frais_retour) || 0)
}

const calculateTotalAvantRemise = (commande) => {
  return calculateSubtotalTTC(commande) + calculateFraisTotaux(commande)
}

const calculateRemise = (commande) => {
  if (!commande.code_reduction) return 0

  const explicit = parseFloat(commande.code_reduction.montant_remise ?? NaN)
  if (!isNaN(explicit) && explicit > 0) return explicit

  const avantRemise = calculateTotalAvantRemise(commande)
  const montantFinal = parseFloat(commande.montant_total ?? NaN)
  if (!isNaN(montantFinal) && montantFinal < avantRemise) {
    return avantRemise - montantFinal
  }

  return 0
}

// ============================================
// CHARGEMENT DES DONNÉES
// ============================================

const loadUserData = async () => {
  try {
    const response = await api.get('/user')
    
    if (response.data && response.data.success && response.data.data) {
      const data = response.data.data
      userData.value = data

      if (data.type === 'particulier') {
        form.value = {
          email:     data.email || '',
          nom:       data.nom || '',
          prenom:    data.prenom || '',
          adresse:   data.adresse || '',
          langue_id: data.langue_id || ''
        }
      } else {
        form.value = {
          email:                    data.email || '',
          nom_societe:              data.nom_societe || '',
          telephone:                data.telephone || '',
          heure_ouverture:          data.heure_ouverture || '',
          heure_fermeture:          data.heure_fermeture || '',
          adresse_siege:            data.adresse_siege || '',
          adresse_livraison_defaut: data.adresse_livraison_defaut || '',
          langue_id:                data.langue_id || '',
          contact:                  data.contact || null
        }
      }
    } else {
      throw new Error('Structure de données invalide')
    }
  } catch (error) {
    console.error('❌ Erreur chargement utilisateur:', error)
    errorMessage.value = 'Impossible de charger votre profil.'
  }
}

const loadLangues = async () => {
  try {
    const response = await api.get('/langues')
    langues.value = response.data.data || response.data || []
  } catch (error) {
    console.error('❌ Erreur chargement langues:', error)
  }
}

const loadCommandes = async () => {
  loadingCommandes.value = true
  try {
    const response = await api.get('/commandes')

    if (response.data?.success) {
      commandes.value = response.data.data || []
      console.log('Commandes chargées:', commandes.value)
    } else {
      commandes.value = []
    }
  } catch (error) {
    console.error('❌ Erreur chargement commandes:', error)
    commandes.value = []
  } finally {
    loadingCommandes.value = false
  }
}

const loadData = async () => {
  initialLoading.value = true
  try {
    await Promise.all([loadUserData(), loadLangues(), loadCommandes()])
  } finally {
    initialLoading.value = false
  }
}

// ============================================
// ACTIONS PROFIL
// ============================================

const updateProfile = async () => {
  loading.value = true
  errors.value = {}
  errorMessage.value = ''
  successMessage.value = ''
  try {
    const response = await api.put('/user/profile', form.value)
    if (response.data.success) {
      successMessage.value = 'Profil mis à jour avec succès'
      await loadUserData()
      setTimeout(() => { successMessage.value = '' }, 3000)
    }
  } catch (error) {
    if (error.response?.status === 422) {
      errors.value = error.response.data.errors || {}
      errorMessage.value = 'Veuillez corriger les erreurs dans le formulaire'
    } else {
      errorMessage.value = error.response?.data?.message || 'Erreur lors de la mise à jour du profil'
    }
  } finally {
    loading.value = false
  }
}

const updatePassword = async () => {
  passwordLoading.value = true
  passwordErrors.value = {}
  errorMessage.value = ''
  successMessage.value = ''
  try {
    const response = await api.put('/user/password', passwordForm.value)
    if (response.data.success) {
      successMessage.value = 'Mot de passe mis à jour avec succès'
      passwordForm.value = { current_password: '', new_password: '', new_password_confirmation: '' }
      setTimeout(() => { successMessage.value = '' }, 3000)
    }
  } catch (error) {
    if (error.response?.status === 422) {
      passwordErrors.value = error.response.data.errors || {}
    } else {
      errorMessage.value = error.response?.data?.message || 'Erreur lors de la mise à jour du mot de passe'
    }
  } finally {
    passwordLoading.value = false
  }
}

const updateNotifications = async () => {
  notificationsLoading.value = true
  try {
    await new Promise(resolve => setTimeout(resolve, 1000))
    successMessage.value = 'Préférences de notifications mises à jour'
    setTimeout(() => { successMessage.value = '' }, 3000)
  } catch {
    errorMessage.value = 'Erreur lors de la mise à jour des notifications'
  } finally {
    notificationsLoading.value = false
  }
}

const deleteAccount = async () => {
  if (!confirm('⚠️ Attention : Cette action est irréversible. Toutes vos données seront supprimées définitivement. Êtes-vous absolument sûr ?')) return
  
  const password = prompt('Pour confirmer la suppression de votre compte, veuillez entrer votre mot de passe :')
  if (!password) return
  
  const confirmation = prompt('Tapez "DELETE" pour confirmer la suppression :')
  if (confirmation !== 'DELETE') {
    errorMessage.value = 'Confirmation incorrecte'
    return
  }
  
  try {
    const response = await api.delete('/user', { data: { password, confirmation: 'DELETE' } })
    if (response.data.success) {
      localStorage.removeItem('token')
      localStorage.removeItem('user')
      router.push('/login')
    }
  } catch (error) {
    errorMessage.value = error.response?.data?.message || 'Erreur lors de la suppression du compte'
  }
}

// ============================================
// ACTIONS COMMANDES
// ============================================

const viewOrder = (orderId) => router.push(`/commandes/${orderId}`)

const canCancelOrder = (statut) => [1, 2].includes(statut)

const cancelOrder = async (orderId) => {
  if (!confirm('Êtes-vous sûr de vouloir annuler cette commande ?')) return
  try {
    const response = await api.put(`/commandes/${orderId}/annuler`)
    if (response.data.success) {
      successMessage.value = 'Commande annulée avec succès'
      await loadCommandes()
      setTimeout(() => { successMessage.value = '' }, 3000)
    }
  } catch (error) {
    errorMessage.value = error.response?.data?.message || 'Erreur lors de l\'annulation de la commande'
  }
}

// ============================================
// UTILITAIRES GÉNÉRAUX
// ============================================

const resetForm = () => {
  if (!userData.value) return
  if (userType.value === 'particulier') {
    form.value = {
      email:     userData.value.email     || '',
      nom:       userData.value.nom       || '',
      prenom:    userData.value.prenom    || '',
      adresse:   userData.value.adresse   || '',
      langue_id: userData.value.langue_id || ''
    }
  } else {
    form.value = {
      email:                    userData.value.email                    || '',
      nom_societe:              userData.value.nom_societe              || '',
      telephone:                userData.value.telephone                || '',
      heure_ouverture:          userData.value.heure_ouverture          || '',
      heure_fermeture:          userData.value.heure_fermeture          || '',
      adresse_siege:            userData.value.adresse_siege            || '',
      adresse_livraison_defaut: userData.value.adresse_livraison_defaut || '',
      langue_id:                userData.value.langue_id                || '',
      contact:                  userData.value.contact                  || null
    }
  }
  errors.value = {}
}

const formatDate = (dateString) => {
  if (!dateString) return ''
  return new Date(dateString).toLocaleDateString('fr-FR', { 
    day: '2-digit', 
    month: '2-digit', 
    year: 'numeric' 
  })
}

const formatPrice = (price) => {
  if (price === null || price === undefined) return '0,00 €'
  const n = typeof price === 'string' ? parseFloat(price) : price
  if (isNaN(n)) return '0,00 €'
  return new Intl.NumberFormat('fr-FR', { 
    style: 'currency', 
    currency: 'EUR', 
    minimumFractionDigits: 2 
  }).format(n)
}

const getStatusLabel = (statut) => {
  const s = { 
    1: 'En attente', 
    2: 'Confirmée', 
    3: 'En préparation', 
    4: 'Livrée', 
    5: 'Annulée', 
    6: 'Retournée' 
  }
  return s[statut] || 'Inconnu'
}

const getStatusClass = (statut) => {
  const c = { 
    1: 'status-pending', 
    2: 'status-confirmed', 
    3: 'status-preparing', 
    4: 'status-delivered', 
    5: 'status-cancelled', 
    6: 'status-returned' 
  }
  return c[statut] || ''
}

const getPasswordStrength = (password) => {
  if (!password) return 'strength-none'
  let s = 0
  if (password.length >= 8) s++
  if (/[A-Z]/.test(password)) s++
  if (/[a-z]/.test(password)) s++
  if (/[0-9]/.test(password)) s++
  if (/[^A-Za-z0-9]/.test(password)) s++
  if (s < 2) return 'strength-weak'
  if (s < 4) return 'strength-medium'
  return 'strength-strong'
}

const getPasswordStrengthText = (password) => {
  const map = { 
    'strength-weak': 'Faible', 
    'strength-medium': 'Moyen', 
    'strength-strong': 'Fort' 
  }
  return map[getPasswordStrength(password)] || ''
}

const logout = async () => {
  if (!confirm('Êtes-vous sûr de vouloir vous déconnecter ?')) return
  try { 
    await api.post('/logout') 
  } catch (e) { 
    console.error(e) 
  } finally {
    localStorage.removeItem('token')
    localStorage.removeItem('user')
    router.push('/login')
  }
}

onMounted(() => loadData())
</script>

<style scoped>
.profile-container {
  max-width: 1200px;
  margin: 0 auto;
  padding: 20px;
  min-height: 100vh;
  background: #f9fafb;
}

/* Loading */
.loading-container {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  min-height: 60vh;
  gap: 20px;
}
.loading-container p { color: #6b7280; font-size: 1.1rem; }

/* Header */
.profile-header {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  margin-bottom: 40px;
  padding: 30px;
  background: white;
  border-radius: 15px;
  box-shadow: 0 2px 10px rgba(0,0,0,.05);
}
.header-content h1  { font-size: 2.5rem; color: #1f2937; margin: 0 0 10px; }
.header-content > p { color: #6b7280; font-size: 1.1rem; margin: 0 0 15px; }

.user-type-badge {
  display: inline-block;
  padding: 8px 16px;
  border-radius: 20px;
  font-weight: 600;
  font-size: .9rem;
}
.user-type-badge.particulier   { background: #dbeafe; color: #1e40af; }
.user-type-badge.professionnel { background: #fef3c7; color: #92400e; }

.logout-btn {
  display: flex;
  align-items: center;
  gap: 8px;
  padding: 12px 24px;
  background: #ef4444;
  color: white;
  border: none;
  border-radius: 10px;
  font-weight: 600;
  cursor: pointer;
  transition: all .3s;
}
.logout-btn:hover { background: #dc2626; transform: translateY(-2px); box-shadow: 0 4px 12px rgba(239,68,68,.3); }

/* Layout */
.profile-content {
  display: grid;
  grid-template-columns: 250px 1fr;
  gap: 30px;
  align-items: start;
}
.profile-sidebar { position: sticky; top: 20px; }

.profile-menu {
  display: flex;
  flex-direction: column;
  gap: 8px;
  background: white;
  border-radius: 12px;
  padding: 15px;
  box-shadow: 0 2px 10px rgba(0,0,0,.05);
}
.menu-item {
  display: flex;
  align-items: center;
  gap: 12px;
  padding: 15px;
  border: none;
  background: transparent;
  border-radius: 10px;
  cursor: pointer;
  transition: all .3s;
  color: #4b5563;
  font-weight: 500;
  text-align: left;
}
.menu-item:hover  { background: #f3f4f6; color: #1f2937; }
.menu-item.active { background: linear-gradient(135deg,#667eea,#764ba2); color: white; }
.menu-icon  { font-size: 1.3rem; }
.menu-label { flex: 1; }
.menu-badge {
  padding: 3px 10px;
  background: #ef4444;
  color: white;
  border-radius: 12px;
  font-size: .75rem;
  font-weight: 700;
}

/* Tab */
.tab-content {
  background: white;
  border-radius: 15px;
  padding: 30px;
  box-shadow: 0 2px 10px rgba(0,0,0,.05);
}
.tab-header { margin-bottom: 30px; padding-bottom: 20px; border-bottom: 2px solid #f3f4f6; }
.tab-header h2 { font-size: 1.8rem; color: #1f2937; margin: 0 0 8px; }
.tab-header p  { color: #6b7280; margin: 0; }

/* Forms */
.form-section { margin-bottom: 35px; }
.form-section h3 {
  font-size: 1.3rem;
  color: #1f2937;
  margin: 0 0 20px;
  padding-bottom: 10px;
  border-bottom: 2px solid #f3f4f6;
}
.form-grid { display: grid; grid-template-columns: repeat(auto-fit,minmax(280px,1fr)); gap: 20px; }
.form-group.full-width { grid-column: 1 / -1; }
.form-group { margin-bottom: 0; }
.form-group label { display: block; margin-bottom: 8px; font-weight: 600; color: #374151; font-size: .95rem; }

.form-group input,
.form-group select,
.form-group textarea {
  width: 100%;
  padding: 12px 15px;
  border: 2px solid #e5e7eb;
  border-radius: 10px;
  font-size: 1rem;
  transition: all .3s;
  font-family: inherit;
  box-sizing: border-box;
}
.form-group input:focus,
.form-group select:focus,
.form-group textarea:focus {
  outline: none;
  border-color: #667eea;
  box-shadow: 0 0 0 3px rgba(102,126,234,.1);
}
.form-group input.error,
.form-group select.error,
.form-group textarea.error { border-color: #ef4444; }

.readonly-field { background: #f9fafb; color: #6b7280; cursor: not-allowed; }
.field-help     { font-size: .85rem; color: #6b7280; margin: 6px 0 0; }
.error-message  { color: #ef4444; font-size: .875rem; margin: 6px 0 0; font-weight: 500; }

/* Adresses pro */
.address-display-card {
  padding: 20px;
  background: #f9fafb;
  border-radius: 12px;
  border: 2px solid #e5e7eb;
  margin-bottom: 15px;
}
.address-header { margin-bottom: 12px; }
.address-header h4 { font-size: 1.1rem; color: #1f2937; margin: 0; }
.address-text { color: #4b5563; line-height: 1.6; margin: 0; }
.no-address   { color: #9ca3af; font-style: italic; margin: 0; }

/* Password */
.password-input { position: relative; }
.password-toggle {
  position: absolute;
  right: 12px;
  top: 50%;
  transform: translateY(-50%);
  background: none;
  border: none;
  cursor: pointer;
  font-size: 1.2rem;
  padding: 5px;
}
.password-strength { margin-top: 10px; }
.strength-bar { height: 6px; border-radius: 3px; transition: all .3s; margin-bottom: 5px; }
.strength-none   { width: 0;    background: #d1d5db; }
.strength-weak   { width: 33%;  background: #ef4444; }
.strength-medium { width: 66%;  background: #f59e0b; }
.strength-strong { width: 100%; background: #10b981; }
.strength-text   { font-size: .85rem; font-weight: 600; }

.password-requirements { margin-top: 20px; padding: 15px; background: #f9fafb; border-radius: 10px; }
.password-requirements p { margin: 0 0 10px; color: #4b5563; }
.password-requirements ul { list-style: none; padding: 0; margin: 0; }
.password-requirements li { padding: 5px 0; color: #6b7280; font-size: .9rem; }
.password-requirements li.valid { color: #10b981; font-weight: 600; }

/* Security stats */
.security-stats {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
  gap: 20px;
  margin-bottom: 30px;
}
.stat-card {
  display: flex;
  align-items: center;
  gap: 15px;
  padding: 20px;
  background: #f9fafb;
  border-radius: 12px;
  border: 2px solid #e5e7eb;
}
.stat-icon { font-size: 2rem; }
.stat-info { flex: 1; }
.stat-value { font-size: 1.1rem; font-weight: 700; color: #1f2937; }
.stat-label { font-size: 0.85rem; color: #6b7280; margin-top: 4px; }

/* Danger zone */
.danger-zone { margin-top: 40px; padding-top: 30px; border-top: 2px solid #fee2e2; }
.danger-zone h3 { color: #dc2626; font-size: 1.2rem; margin: 0 0 20px 0; }
.danger-card {
  display: flex;
  gap: 20px;
  padding: 20px;
  background: #fef2f2;
  border-radius: 12px;
  border: 2px solid #fecaca;
}
.danger-icon { font-size: 2rem; }
.danger-content { flex: 1; }
.danger-content h4 { color: #dc2626; margin: 0 0 8px 0; }
.danger-content p { color: #6b7280; margin: 0 0 15px 0; }
.delete-account-btn {
  padding: 10px 20px;
  background: #dc2626;
  color: white;
  border: none;
  border-radius: 8px;
  font-weight: 600;
  cursor: pointer;
  transition: all .3s;
}
.delete-account-btn:hover { background: #b91c1c; transform: translateY(-2px); }

/* Notifications */
.checkbox-group { display: flex; flex-direction: column; gap: 15px; }
.checkbox-item {
  display: flex;
  gap: 15px;
  padding: 15px;
  border: 2px solid #e5e7eb;
  border-radius: 10px;
  cursor: pointer;
  transition: all .3s;
}
.checkbox-item:hover { background: #f9fafb; border-color: #667eea; }
.checkbox-input { width: 20px; height: 20px; cursor: pointer; flex-shrink: 0; margin-top: 2px; }
.checkbox-label { flex: 1; }
.checkbox-title       { display: block; font-weight: 600; color: #1f2937; margin-bottom: 4px; }
.checkbox-description { display: block; font-size: .9rem; color: #6b7280; }

/* Radio group */
.radio-group { display: flex; flex-direction: column; gap: 15px; }
.radio-item {
  display: flex;
  gap: 15px;
  padding: 15px;
  border: 2px solid #e5e7eb;
  border-radius: 10px;
  cursor: pointer;
  transition: all .3s;
}
.radio-item:hover { background: #f9fafb; border-color: #667eea; }
.radio-input { width: 20px; height: 20px; cursor: pointer; flex-shrink: 0; margin-top: 2px; }
.radio-label { flex: 1; }
.radio-title       { display: block; font-weight: 600; color: #1f2937; margin-bottom: 4px; }
.radio-description { display: block; font-size: .9rem; color: #6b7280; }

/* Buttons */
.form-actions {
  display: flex;
  justify-content: flex-end;
  gap: 15px;
  margin-top: 30px;
  padding-top: 30px;
  border-top: 2px solid #f3f4f6;
}
.cancel-btn {
  padding: 12px 24px;
  background: #f3f4f6;
  color: #374151;
  border: 2px solid #e5e7eb;
  border-radius: 10px;
  font-weight: 600;
  cursor: pointer;
  transition: all .3s;
}
.cancel-btn:hover { background: #e5e7eb; }

.save-btn {
  padding: 12px 30px;
  background: linear-gradient(135deg,#10b981,#34d399);
  color: white;
  border: none;
  border-radius: 10px;
  font-weight: 600;
  cursor: pointer;
  transition: all .3s;
  min-width: 200px;
  display: flex;
  align-items: center;
  justify-content: center;
}
.save-btn:hover:not(:disabled) { transform: translateY(-2px); box-shadow: 0 8px 20px rgba(16,185,129,.3); }
.save-btn:disabled { opacity: .7; cursor: not-allowed; transform: none; }

/* Commandes */
.loading-small {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  padding: 60px 20px;
  gap: 15px;
}
.loading-small p { color: #6b7280; margin: 0; }

.empty-state { text-align: center; padding: 60px 20px; }
.empty-icon  { font-size: 4rem; margin-bottom: 20px; }
.empty-state h3 { font-size: 1.5rem; color: #1f2937; margin: 0 0 10px; }
.empty-state p  { color: #6b7280; margin: 0 0 25px; }
.browse-btn {
  display: inline-block;
  padding: 12px 30px;
  background: linear-gradient(135deg,#667eea,#764ba2);
  color: white;
  text-decoration: none;
  border-radius: 10px;
  font-weight: 600;
  transition: all .3s;
}
.browse-btn:hover { transform: translateY(-2px); box-shadow: 0 8px 20px rgba(102,126,234,.3); }

.orders-list { display: flex; flex-direction: column; gap: 20px; }
.order-card {
  border: 2px solid #e5e7eb;
  border-radius: 15px;
  overflow: hidden;
  transition: all .3s;
}
.order-card:hover { box-shadow: 0 4px 20px rgba(0,0,0,.08); border-color: #d1d5db; }

.order-header {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  padding: 20px;
  background: #f9fafb;
  border-bottom: 2px solid #e5e7eb;
}
.order-number { font-size: 1.2rem; color: #1f2937; margin: 0 0 8px; font-weight: 700; }
.order-dates  { display: flex; flex-direction: column; gap: 4px; }
.order-date, .order-period { font-size: .9rem; color: #6b7280; }
.order-period strong { color: #374151; }

.order-status {
  padding: 8px 16px;
  border-radius: 20px;
  font-weight: 600;
  font-size: .85rem;
  white-space: nowrap;
}
.status-pending   { background: #fef3c7; color: #92400e; }
.status-confirmed { background: #dbeafe; color: #1e40af; }
.status-preparing { background: #e0e7ff; color: #3730a3; }
.status-delivered { background: #d1fae5; color: #065f46; }
.status-cancelled { background: #fee2e2; color: #991b1b; }
.status-returned  { background: #f3f4f6; color: #374151; }

.order-details { padding: 20px; }
.order-items   { display: flex; flex-direction: column; gap: 15px; margin-bottom: 20px; }

.order-item {
  display: flex;
  gap: 15px;
  align-items: center;
  padding: 15px;
  background: #f9fafb;
  border-radius: 10px;
}

.item-image-wrapper {
  width: 60px;
  height: 60px;
  flex-shrink: 0;
  border-radius: 8px;
  overflow: hidden;
  background: #f3f4f6;
  display: flex;
  align-items: center;
  justify-content: center;
}
.item-image {
  width: 100%;
  height: 100%;
  object-fit: cover;
  display: block;
}
.item-image[src^="data:image/svg"] {
  object-fit: contain;
  padding: 6px;
}

.item-info { flex: 1; }
.item-info h5 { font-size: 1rem; color: #1f2937; margin: 0 0 5px; }
.item-details {
  display: flex;
  gap: 8px;
  align-items: center;
  flex-wrap: wrap;
}
.item-quantity, .item-duration {
  background: #f3f4f6;
  padding: 2px 8px;
  border-radius: 12px;
  font-size: 0.85rem;
}
.item-taxes {
  font-size: 0.8rem;
  color: #6b7280;
  margin-top: 4px;
}
.item-pricing { text-align: right; }
.item-subtotal-ht { font-size: 0.9rem; color: #6b7280; }
.item-subtotal-ttc { font-weight: 700; color: #10b981; font-size: 1rem; }

.order-summary {
  padding: 20px;
  background: #f9fafb;
  border-radius: 10px;
}
.summary-group { margin-bottom: 20px; }
.summary-group h4 { font-size: 1rem; color: #374151; margin: 0 0 10px 0; font-weight: 600; }
.summary-row {
  display: flex;
  justify-content: space-between;
  padding: 8px 0;
  font-size: .95rem;
}
.summary-row.highlight {
  background: #f3f4f6;
  padding: 8px 12px;
  border-radius: 8px;
  margin: 5px 0;
  font-weight: 500;
}
.summary-row.discount { color: #10b981; font-weight: 600; }
.summary-divider { height: 1px; background: #e5e7eb; margin: 6px 0; }
.summary-total { margin-top: 15px; }
.total-row {
  display: flex;
  justify-content: space-between;
  font-size: 1.1rem;
  font-weight: 700;
}
.total-price { color: #10b981; font-size: 1.3rem; }
.total-details {
  text-align: right;
  color: #6b7280;
  font-size: 0.85rem;
  margin-top: 4px;
}

.order-actions {
  display: flex;
  gap: 10px;
  padding: 20px;
  background: #f9fafb;
  border-top: 2px solid #e5e7eb;
}
.view-btn, .cancel-order-btn {
  padding: 10px 20px;
  border: none;
  border-radius: 8px;
  font-weight: 600;
  cursor: pointer;
  transition: all .3s;
  font-size: .9rem;
}
.view-btn         { background: #667eea; color: white; }
.view-btn:hover   { background: #5568d3; transform: translateY(-2px); }
.cancel-order-btn       { background: #fee2e2; color: #991b1b; }
.cancel-order-btn:hover { background: #fecaca; }
.btn-icon { margin-right: 6px; }

/* Spinner */
.loading-spinner {
  width: 20px;
  height: 20px;
  border: 2px solid rgba(255,255,255,.3);
  border-radius: 50%;
  border-top-color: white;
  animation: spin .8s linear infinite;
}
@keyframes spin { to { transform: rotate(360deg); } }

/* Alerts */
.alert {
  position: fixed;
  top: 20px;
  right: 20px;
  display: flex;
  align-items: center;
  gap: 12px;
  padding: 16px 20px;
  border-radius: 12px;
  z-index: 1000;
  max-width: 400px;
  box-shadow: 0 10px 40px rgba(0,0,0,.2);
}
.alert.success { background: #10b981; color: white; }
.alert.error   { background: #ef4444; color: white; }
.alert-icon    { font-size: 1.3rem; }
.alert-message { flex: 1; font-weight: 500; }
.alert-close {
  background: none;
  border: none;
  color: white;
  font-size: 1.5rem;
  cursor: pointer;
  padding: 0;
  width: 24px;
  height: 24px;
  display: flex;
  align-items: center;
  justify-content: center;
  border-radius: 50%;
  transition: background .3s;
}
.alert-close:hover { background: rgba(0,0,0,.1); }
.alert-slide-enter-active,
.alert-slide-leave-active  { transition: all .3s ease; }
.alert-slide-enter-from,
.alert-slide-leave-to      { transform: translateX(100%); opacity: 0; }

/* Responsive */
@media (max-width: 992px) {
  .profile-content { grid-template-columns: 1fr; }
  .profile-sidebar { position: static; }
  .profile-menu { flex-direction: row; overflow-x: auto; padding-bottom: 10px; }
  .menu-item    { white-space: nowrap; flex-shrink: 0; }
  .form-grid    { grid-template-columns: 1fr; }
  .security-stats { grid-template-columns: 1fr; }
}
@media (max-width: 768px) {
  .profile-header { flex-direction: column; gap: 20px; }
  .header-actions { width: 100%; }
  .logout-btn     { width: 100%; justify-content: center; }
  .form-actions   { flex-direction: column; }
  .save-btn, .cancel-btn { width: 100%; }
  .order-header { flex-direction: column; gap: 15px; }
  .order-status { align-self: flex-start; }
  .order-actions { flex-direction: column; }
  .view-btn, .cancel-order-btn { width: 100%; }
  .danger-card { flex-direction: column; text-align: center; }
  .alert { left: 10px; right: 10px; max-width: none; }
}
</style>