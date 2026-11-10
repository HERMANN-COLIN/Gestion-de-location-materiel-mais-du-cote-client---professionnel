<template>
  <div class="admin-dashboard">
    <!-- Barre de navigation Admin -->
    <div class="admin-nav-tabs">
      <router-link to="/admin/produits" class="admin-tab active">
        📦 Produits & Stocks
      </router-link>
      <router-link to="/admin/commandes" class="admin-tab">
        🛒 Commandes
      </router-link>
      <router-link to="/admin/statistiques" class="admin-tab">
        📊 Statistiques
      </router-link>
      <router-link to="/" class="admin-tab back-to-site">
        🏠 Retour au site
      </router-link>
    </div>

    <!-- En-tête -->
    <div class="page-header">
      <div>
        <h1>Gestion des produits</h1>
        <p>Ajoutez, modifiez ou supprimez vos matériels et gérez les stocks</p>
      </div>
      <button @click="openForm()" class="btn-primary">
        ➕ Ajouter un matériel
      </button>
    </div>

    <!-- Statistiques rapides -->
    <div class="quick-stats">
      <div class="stat-card">
        <span class="stat-icon">📦</span>
        <div>
          <div class="stat-value">{{ stats.total_materiels || 0 }}</div>
          <div class="stat-label">Matériels</div>
        </div>
      </div>
      <div class="stat-card">
        <span class="stat-icon">⚠️</span>
        <div>
          <div class="stat-value">{{ stats.stock_faible || 0 }}</div>
          <div class="stat-label">Stock faible</div>
        </div>
      </div>
      <div class="stat-card">
        <span class="stat-icon">🔴</span>
        <div>
          <div class="stat-value">{{ stats.rupture || 0 }}</div>
          <div class="stat-label">Rupture</div>
        </div>
      </div>
    </div>

    <!-- Filtres -->
    <div class="filters-bar">
      <input 
        v-model="searchQuery" 
        type="text" 
        placeholder="Rechercher un matériel..."
        class="search-input"
        @input="debouncedSearch"
      >
      <select v-model="filterCategorie" @change="loadMateriels" class="filter-select">
        <option value="">Toutes les catégories</option>
        <option v-for="cat in categories" :key="cat.id" :value="cat.id">
          {{ cat.nom }}
        </option>
      </select>
      <select v-model="filterStock" @change="loadMateriels" class="filter-select">
        <option value="">Tous les stocks</option>
        <option value="low">Stock faible (&lt;10)</option>
        <option value="out">Rupture</option>
      </select>
    </div>

    <!-- Tableau -->
    <div class="table-wrapper">
      <table class="data-table">
        <thead>
          <tr>
            <th>Photo</th>
            <th>Nom</th>
            <th>Catégorie</th>
            <th>Prix/jour</th>
            <th>Stock</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="materiel in materiels" :key="materiel.id">
            <td>
              <img :src="getPhotoUrl(materiel)" :alt="materiel.nom" class="thumb">
            </td>
            <td>
              <div class="cell-main">{{ materiel.nom }}</div>
              <div class="cell-sub">{{ materiel.dimensions }}</div>
            </td>
            <td>{{ materiel.categorie?.nom }}</td>
            <td class="cell-price">{{ formatPrice(materiel.prix_journalier) }}</td>
            <td>
              <div class="stock-control">
                <button @click="adjustStock(materiel, -1)" class="stock-btn">−</button>
                <span class="stock-value" :class="getStockClass(materiel.stock_disponible)">
                  {{ materiel.stock_disponible }} / {{ materiel.stock_total }}
                </span>
                <button @click="adjustStock(materiel, 1)" class="stock-btn">+</button>
              </div>
            </td>
            <td>
              <div class="actions">
                <button @click="openForm(materiel)" class="btn-icon" title="Modifier">✏️</button>
                <button @click="deleteMateriel(materiel.id)" class="btn-icon danger" title="Supprimer">🗑️</button>
              </div>
            </td>
          </tr>
          <tr v-if="materiels.length === 0">
            <td colspan="6" class="empty-row">Aucun matériel trouvé</td>
          </tr>
        </tbody>
      </table>
    </div>

    <!-- Pagination -->
    <div v-if="pagination.last_page > 1" class="pagination">
      <button @click="goToPage(pagination.current_page - 1)" :disabled="pagination.current_page === 1">←</button>
      <span>Page {{ pagination.current_page }} sur {{ pagination.last_page }}</span>
      <button @click="goToPage(pagination.current_page + 1)" :disabled="pagination.current_page === pagination.last_page">→</button>
    </div>

    <!-- Modal Formulaire -->
    <div v-if="showForm" class="modal-overlay" @click.self="closeForm">
      <div class="modal-content">
        <div class="modal-header">
          <h2>{{ editingMateriel ? 'Modifier le matériel' : 'Nouveau matériel' }}</h2>
          <button @click="closeForm" class="close-btn">×</button>
        </div>
        <form @submit.prevent="saveMateriel" class="modal-body">
          <div class="form-grid">
            <div class="form-group full">
              <label>Nom *</label>
              <input v-model="form.nom" type="text" required>
            </div>
            <div class="form-group">
              <label>Catégorie *</label>
              <select v-model="form.categorie_id" required>
                <option value="">Sélectionner</option>
                <option v-for="cat in categories" :key="cat.id" :value="cat.id">
                  {{ cat.nom }}
                </option>
              </select>
            </div>
            <div class="form-group">
              <label>Prix/jour (€) *</label>
              <input v-model.number="form.prix_journalier" type="number" step="0.01" min="0" required>
            </div>
            <div class="form-group">
              <label>Dimensions</label>
              <input v-model="form.dimensions" type="text" placeholder="Ex: 120x80cm">
            </div>
            <div class="form-group">
              <label>Stock total *</label>
              <input v-model.number="form.stock_total" type="number" min="0" required>
            </div>
            <div class="form-group">
              <label>Stock disponible *</label>
              <input v-model.number="form.stock_disponible" type="number" min="0" :max="form.stock_total" required>
            </div>
            <div class="form-group full">
              <label>Description</label>
              <textarea v-model="form.description" rows="4"></textarea>
            </div>
            <div class="form-group full">
              <label>Photos</label>
              <input type="file" multiple accept="image/*" @change="handlePhotos" class="file-input">
              <div v-if="photosPreview.length > 0" class="photo-preview">
                <div v-for="(photo, i) in photosPreview" :key="i" class="preview-item">
                  <img :src="photo" alt="">
                  <button type="button" @click="removePhoto(i)" class="remove-photo">×</button>
                </div>
              </div>
            </div>
          </div>
          <div v-if="formError" class="error-msg">{{ formError }}</div>
          <div class="modal-footer">
            <button type="button" @click="closeForm" class="btn-secondary">Annuler</button>
            <button type="submit" :disabled="saving" class="btn-primary">
              {{ saving ? 'Enregistrement...' : 'Enregistrer' }}
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import api from '@/services/axios'

const materiels = ref([])
const categories = ref([])
const searchQuery = ref('')
const filterCategorie = ref('')
const filterStock = ref('')
const loading = ref(false)

const showForm = ref(false)
const editingMateriel = ref(null)
const saving = ref(false)
const formError = ref('')
const photosPreview = ref([])
const newPhotos = ref([])

const stats = ref({})

const form = ref({
  nom: '',
  categorie_id: '',
  description: '',
  prix_journalier: 0,
  dimensions: '',
  stock_total: 0,
  stock_disponible: 0
})

const pagination = ref({ current_page: 1, last_page: 1, total: 0 })
let searchTimeout = null

const debouncedSearch = () => {
  clearTimeout(searchTimeout)
  searchTimeout = setTimeout(() => loadMateriels(), 300)
}

const loadCategories = async () => {
  try {
    const response = await api.get('/categories-materiel')
    categories.value = response.data.data || response.data
  } catch (error) {
    console.error('Erreur catégories:', error)
  }
}

const loadMateriels = async (page = 1) => {
  loading.value = true
  try {
    let url = `/admin/materiels?page=${page}`
    if (filterCategorie.value) url += `&categorie_id=${filterCategorie.value}`
    if (searchQuery.value) url += `&search=${searchQuery.value}`
    if (filterStock.value) url += `&filter_stock=${filterStock.value}`
    
    const response = await api.get(url)
    const data = response.data
    materiels.value = data.data || []
    pagination.value = {
      current_page: data.current_page || 1,
      last_page: data.last_page || 1,
      total: data.total || 0
    }
  } catch (error) {
    console.error('Erreur matériels:', error)
  } finally {
    loading.value = false
  }
}

const loadStats = async () => {
  try {
    const response = await api.get('/admin/stats')
    stats.value = response.data.data || {}
  } catch (error) {
    console.error('Erreur stats:', error)
  }
}

const openForm = (materiel = null) => {
  if (materiel) {
    editingMateriel.value = materiel
    form.value = {
      nom: materiel.nom,
      categorie_id: materiel.categorie_id,
      description: materiel.description || '',
      prix_journalier: materiel.prix_journalier,
      dimensions: materiel.dimensions || '',
      stock_total: materiel.stock_total,
      stock_disponible: materiel.stock_disponible
    }
    photosPreview.value = (materiel.photos || []).map(p => p.url_photo)
  } else {
    editingMateriel.value = null
    form.value = {
      nom: '',
      categorie_id: '',
      description: '',
      prix_journalier: 0,
      dimensions: '',
      stock_total: 0,
      stock_disponible: 0
    }
    photosPreview.value = []
  }
  newPhotos.value = []
  formError.value = ''
  showForm.value = true
}

const closeForm = () => {
  showForm.value = false
  editingMateriel.value = null
  photosPreview.value = []
  newPhotos.value = []
  formError.value = ''
}

const handlePhotos = (event) => {
  const files = Array.from(event.target.files)
  files.forEach(file => {
    newPhotos.value.push(file)
    const reader = new FileReader()
    reader.onload = (e) => photosPreview.value.push(e.target.result)
    reader.readAsDataURL(file)
  })
}

const removePhoto = (index) => {
  photosPreview.value.splice(index, 1)
  // Simplification : on ne gère que l'ajout de nouvelles photos pour l'instant
}

const saveMateriel = async () => {
  saving.value = true
  formError.value = ''
  
  try {
    const formData = new FormData()
    Object.keys(form.value).forEach(key => {
      formData.append(key, form.value[key])
    })
    newPhotos.value.forEach((photo, i) => {
      formData.append(`photos[${i}]`, photo)
    })
    
    if (editingMateriel.value) {
      // Laravel exige _method=PUT pour gérer les fichiers en POST
      formData.append('_method', 'PUT') 
      await api.post(`/admin/materiels/${editingMateriel.value.id}`, formData, {
        headers: { 'Content-Type': 'multipart/form-data' }
      })
    } else {
      await api.post('/admin/materiels', formData, {
        headers: { 'Content-Type': 'multipart/form-data' }
      })
    }
    
    closeForm()
    loadMateriels()
    loadStats()
  } catch (error) {
    console.error(error)
    if (error.response?.status === 422) {
      formError.value = Object.values(error.response.data.errors).flat().join(', ')
    } else {
      formError.value = error.response?.data?.message || 'Erreur lors de l\'enregistrement'
    }
  } finally {
    saving.value = false
  }
}

const deleteMateriel = async (id) => {
  if (!confirm('Supprimer ce matériel ?')) return
  try {
    await api.delete(`/admin/materiels/${id}`)
    loadMateriels(pagination.value.current_page)
    loadStats()
  } catch (error) {
    alert('Erreur lors de la suppression')
  }
}

const adjustStock = async (materiel, delta) => {
  const newStock = materiel.stock_disponible + delta
  if (newStock < 0 || newStock > materiel.stock_total) return
  try {
    const response = await api.post(`/admin/materiels/${materiel.id}/stock`, {
      stock_disponible: newStock
    })
    // Mise à jour locale avec la réponse du serveur (valeur authoritative)
    materiel.stock_disponible = response.data.data.stock_disponible
    loadStats()
  } catch (error) {
    console.error('Erreur stock:', error)
    // Recharger pour synchroniser
    loadMateriels(pagination.value.current_page)
  }
}

const goToPage = (page) => {
  if (page >= 1 && page <= pagination.value.last_page) loadMateriels(page)
}

const getPhotoUrl = (materiel) => {
  if (materiel.photos && materiel.photos.length > 0) return materiel.photos[0].url_photo
  return '/placeholder.jpg'
}

const formatPrice = (price) => {
  return new Intl.NumberFormat('fr-FR', { style: 'currency', currency: 'EUR' }).format(price)
}

const getStockClass = (stock) => {
  if (stock === 0) return 'out'
  if (stock < 10) return 'low'
  return 'ok'
}

onMounted(() => {
  loadCategories()
  loadMateriels()
  loadStats()
})
</script>

<style scoped>
.admin-dashboard {
  display: flex;
  flex-direction: column;
  gap: 25px;
  padding: 20px;
  max-width: 1400px;
  margin: 0 auto;
}

/* Navigation Admin */
.admin-nav-tabs {
  display: flex;
  gap: 10px;
  background: white;
  padding: 10px;
  border-radius: 12px;
  box-shadow: 0 2px 8px rgba(0,0,0,0.05);
  margin-bottom: 10px;
}

.admin-tab {
  padding: 10px 20px;
  text-decoration: none;
  color: #64748b;
  font-weight: 600;
  border-radius: 8px;
  transition: all 0.3s;
}

.admin-tab:hover {
  background: #f1f5f9;
  color: #1e293b;
}

.admin-tab.active {
  background: #3b82f6;
  color: white;
}

.admin-tab.back-to-site {
  margin-left: auto;
  color: #10b981;
  background: #f0fdf4;
}
.admin-tab.back-to-site:hover { background: #dcfce7; }

/* Reste des styles inchangés (copiés du message précédent) */
.page-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  flex-wrap: wrap;
  gap: 15px;
}

.page-header h1 {
  font-size: 1.8rem;
  color: #1e293b;
  margin: 0;
}

.page-header p {
  color: #64748b;
  margin: 5px 0 0;
}

.quick-stats {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
  gap: 15px;
}

.stat-card {
  background: white;
  padding: 20px;
  border-radius: 12px;
  display: flex;
  align-items: center;
  gap: 15px;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
}

.stat-icon { font-size: 2rem; }
.stat-value { font-size: 1.5rem; font-weight: 700; color: #1e293b; }
.stat-label { font-size: 0.85rem; color: #64748b; }

.filters-bar {
  display: flex;
  gap: 12px;
  flex-wrap: wrap;
  background: white;
  padding: 15px;
  border-radius: 12px;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
}

.search-input, .filter-select {
  padding: 10px 15px;
  border: 2px solid #e2e8f0;
  border-radius: 8px;
  font-size: 0.95rem;
  background: white;
}
.search-input { flex: 1; min-width: 200px; }

.table-wrapper {
  background: white;
  border-radius: 12px;
  overflow: hidden;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
}

.data-table { width: 100%; border-collapse: collapse; }
.data-table th {
  background: #f8fafc; padding: 15px; text-align: left;
  font-size: 0.85rem; font-weight: 600; color: #64748b;
  border-bottom: 1px solid #e2e8f0;
}
.data-table td { padding: 12px 15px; border-bottom: 1px solid #f1f5f9; vertical-align: middle; }
.data-table tr:hover { background: #f8fafc; }
.thumb { width: 50px; height: 50px; border-radius: 8px; object-fit: cover; }
.cell-main { font-weight: 600; color: #1e293b; }
.cell-sub { font-size: 0.8rem; color: #94a3b8; }
.cell-price { font-weight: 700; color: #10b981; }
.empty-row { text-align: center; color: #94a3b8; padding: 40px !important; }

.stock-control { display: flex; align-items: center; gap: 5px; }
.stock-btn {
  width: 26px; height: 26px; border: 1px solid #e2e8f0;
  background: white; border-radius: 6px; cursor: pointer;
  font-weight: 700; color: #64748b;
}
.stock-btn:hover { background: #3b82f6; color: white; border-color: #3b82f6; }
.stock-value { font-weight: 600; min-width: 70px; text-align: center; padding: 4px 8px; border-radius: 6px; }
.stock-value.ok { background: #dcfce7; color: #16a34a; }
.stock-value.low { background: #fef3c7; color: #d97706; }
.stock-value.out { background: #fee2e2; color: #dc2626; }

.actions { display: flex; gap: 5px; }
.btn-icon {
  width: 34px; height: 34px; border: none; background: #f1f5f9;
  border-radius: 8px; cursor: pointer; font-size: 1rem; transition: all 0.2s;
}
.btn-icon:hover { background: #e2e8f0; }
.btn-icon.danger:hover { background: #fee2e2; }

.btn-primary {
  padding: 10px 20px; background: #3b82f6; color: white;
  border: none; border-radius: 8px; font-weight: 600;
  cursor: pointer; transition: all 0.2s;
}
.btn-primary:hover { background: #2563eb; }
.btn-primary:disabled { opacity: 0.6; cursor: not-allowed; }
.btn-secondary {
  padding: 10px 20px; background: #f1f5f9; color: #475569;
  border: none; border-radius: 8px; font-weight: 600; cursor: pointer;
}
.btn-secondary:hover { background: #e2e8f0; }

.pagination { display: flex; justify-content: center; align-items: center; gap: 15px; }
.pagination button { padding: 8px 16px; background: white; border: 1px solid #e2e8f0; border-radius: 8px; cursor: pointer; }
.pagination button:disabled { opacity: 0.5; cursor: not-allowed; }

.modal-overlay { position: fixed; inset: 0; background: rgba(0,0,0,0.5); display: flex; align-items: center; justify-content: center; z-index: 1000; padding: 20px; }
.modal-content { background: white; border-radius: 12px; width: 100%; max-width: 700px; max-height: 90vh; overflow-y: auto; }
.modal-header { padding: 20px; display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid #e2e8f0; }
.modal-header h2 { margin: 0; font-size: 1.3rem; }
.close-btn { background: none; border: none; font-size: 1.8rem; cursor: pointer; color: #64748b; line-height: 1; }
.modal-body { padding: 20px; }
.form-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 15px; }
.form-group.full { grid-column: span 2; }
.form-group label { display: block; margin-bottom: 5px; font-weight: 600; color: #374151; font-size: 0.9rem; }
.form-group input, .form-group select, .form-group textarea {
  width: 100%; padding: 10px 12px; border: 2px solid #e2e8f0;
  border-radius: 8px; font-size: 0.95rem; font-family: inherit;
}
.form-group input:focus, .form-group select:focus, .form-group textarea:focus { outline: none; border-color: #3b82f6; }
.file-input { padding: 8px !important; }
.photo-preview { display: flex; gap: 10px; flex-wrap: wrap; margin-top: 10px; }
.preview-item { position: relative; width: 80px; height: 80px; }
.preview-item img { width: 100%; height: 100%; object-fit: cover; border-radius: 8px; }
.remove-photo { position: absolute; top: -5px; right: -5px; width: 22px; height: 22px; background: #ef4444; color: white; border: none; border-radius: 50%; cursor: pointer; font-size: 0.8rem; }
.error-msg { background: #fee2e2; color: #dc2626; padding: 10px; border-radius: 8px; margin-top: 10px; font-size: 0.9rem; }
.modal-footer { display: flex; justify-content: flex-end; gap: 10px; padding-top: 20px; border-top: 1px solid #e2e8f0; margin-top: 20px; }
</style>