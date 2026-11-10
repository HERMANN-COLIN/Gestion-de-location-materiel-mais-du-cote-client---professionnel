<template>
  <div class="admin-stat">
    <div class="page-header">
      <div>
        <h1>Analyse et statistiques</h1>
        <p>Vue d'ensemble des performances de votre plateforme</p>
      </div>
      <select v-model="periode" @change="loadStats" class="periode-select">
        <option value="7">7 derniers jours</option>
        <option value="30">30 derniers jours</option>
        <option value="90">3 derniers mois</option>
        <option value="365">Dernière année</option>
      </select>
    </div>

    <!-- KPI Cards -->
    <div class="kpi-grid">
      <div class="kpi-card">
        <div class="kpi-icon revenue">💰</div>
        <div class="kpi-content">
          <div class="kpi-value">{{ formatPrice(kpi.chiffre_affaires || 0) }}</div>
          <div class="kpi-label">Chiffre d'affaires</div>
          <div class="kpi-trend" :class="kpi.évolution >= 0 ? 'up' : 'down'">
            {{ kpi.évolution >= 0 ? '↗' : '↘' }} {{ Math.abs(kpi.évolution || 0) }}% vs période précédente
          </div>
        </div>
      </div>

      <div class="kpi-card">
        <div class="kpi-icon orders">🛒</div>
        <div class="kpi-content">
          <div class="kpi-value">{{ kpi.commandes || 0 }}</div>
          <div class="kpi-label">Commandes</div>
        </div>
      </div>

      <div class="kpi-card">
        <div class="kpi-icon clients">👥</div>
        <div class="kpi-content">
          <div class="kpi-value">{{ kpi.clients || 0 }}</div>
          <div class="kpi-label">Clients actifs</div>
        </div>
      </div>

      <div class="kpi-card">
        <div class="kpi-icon avg">📊</div>
        <div class="kpi-content">
          <div class="kpi-value">{{ formatPrice(kpi.panier_moyen || 0) }}</div>
          <div class="kpi-label">Panier moyen</div>
        </div>
      </div>
    </div>

    <!-- Graphiques -->
    <div class="charts-grid">
      <!-- Graphique CA -->
      <div class="chart-card large">
        <div class="chart-header">
          <h2>Évolution du chiffre d'affaires</h2>
        </div>
        <div class="chart-container">
          <canvas ref="revenueChart"></canvas>
        </div>
      </div>

      <!-- Graphique commandes par statut -->
      <div class="chart-card">
        <div class="chart-header">
          <h2>Répartition des commandes</h2>
        </div>
        <div class="chart-container">
          <canvas ref="statusChart"></canvas>
        </div>
      </div>

      <!-- Top matériels -->
      <div class="chart-card">
        <div class="chart-header">
          <h2>Top 5 des matériels loués</h2>
        </div>
        <div class="chart-container">
          <canvas ref="topMaterielsChart"></canvas>
        </div>
      </div>

      <!-- Commandes par catégorie -->
      <div class="chart-card">
        <div class="chart-header">
          <h2>Locations par catégorie</h2>
        </div>
        <div class="chart-container">
          <canvas ref="categoriesChart"></canvas>
        </div>
      </div>
    </div>

    <!-- Tableau top matériels -->
    <div class="top-table-card">
      <h2>🏆 Matériels les plus loués</h2>
      <table class="data-table">
        <thead>
          <tr>
            <th>#</th>
            <th>Matériel</th>
            <th>Catégorie</th>
            <th>Nombre de locations</th>
            <th>Revenus générés</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="(item, i) in topMateriels" :key="item.id">
            <td><span class="rank" :class="`rank-${i + 1}`">{{ i + 1 }}</span></td>
            <td class="cell-main">{{ item.nom }}</td>
            <td>{{ item.categorie }}</td>
            <td class="cell-number">{{ item.locations }}</td>
            <td class="cell-price">{{ formatPrice(item.revenus) }}</td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, onBeforeUnmount } from 'vue'
import api from '@/services/axios'
import { Chart, registerables } from 'chart.js'

Chart.register(...registerables)

const periode = ref('30')
const kpi = ref({})
const topMateriels = ref([])
const charts = {}

const revenueChart = ref(null)
const statusChart = ref(null)
const topMaterielsChart = ref(null)
const categoriesChart = ref(null)

const formatPrice = (price) => {
  return new Intl.NumberFormat('fr-FR', { style: 'currency', currency: 'EUR', maximumFractionDigits: 0 }).format(price)
}

const destroyCharts = () => {
  Object.values(charts).forEach(chart => chart && chart.destroy())
}

const loadStats = async () => {
  try {
    const response = await api.get(`/admin/analytics?periode=${periode.value}`)
    const data = response.data.data
    
    kpi.value = data.kpi || {}
    topMateriels.value = data.top_materiels || []
    
    destroyCharts()
    renderRevenueChart(data.revenue_by_day || [])
    renderStatusChart(data.par_statut || {})
    renderTopMaterielsChart(data.top_materiels || [])
    renderCategoriesChart(data.par_categorie || [])
  } catch (error) {
    console.error('Erreur analytics:', error)
  }
}

const renderRevenueChart = (data) => {
  if (!revenueChart.value) return
  charts.revenue = new Chart(revenueChart.value, {
    type: 'line',
    data: {
      labels: data.map(d => d.date),
      datasets: [{
        label: 'Chiffre d\'affaires (€)',
        data: data.map(d => d.montant),
        borderColor: '#3b82f6',
        backgroundColor: 'rgba(59, 130, 246, 0.1)',
        fill: true,
        tension: 0.4,
        borderWidth: 3,
        pointRadius: 4,
        pointBackgroundColor: '#3b82f6'
      }]
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      plugins: { legend: { display: false } },
      scales: {
        y: { beginAtZero: true, ticks: { callback: v => v + '€' } }
      }
    }
  })
}

const renderStatusChart = (data) => {
  if (!statusChart.value) return
  charts.status = new Chart(statusChart.value, {
    type: 'doughnut',
    data: {
      labels: ['En attente', 'Confirmées', 'Livrées', 'Annulées'],
      datasets: [{
        data: [data.en_attente || 0, data.confirmee || 0, data.livree || 0, data.annulee || 0],
        backgroundColor: ['#f59e0b', '#3b82f6', '#10b981', '#ef4444'],
        borderWidth: 0
      }]
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      plugins: { legend: { position: 'bottom' } }
    }
  })
}

const renderTopMaterielsChart = (data) => {
  if (!topMaterielsChart.value) return
  charts.topMateriels = new Chart(topMaterielsChart.value, {
    type: 'bar',
    data: {
      labels: data.map(m => m.nom.substring(0, 20)),
      datasets: [{
        label: 'Locations',
        data: data.map(m => m.locations),
        backgroundColor: '#10b981',
        borderRadius: 6
      }]
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      indexAxis: 'y',
      plugins: { legend: { display: false } },
      scales: { x: { beginAtZero: true } }
    }
  })
}

const renderCategoriesChart = (data) => {
  if (!categoriesChart.value) return
  charts.categories = new Chart(categoriesChart.value, {
    type: 'bar',
    data: {
      labels: data.map(c => c.nom),
      datasets: [{
        label: 'Locations',
        data: data.map(c => c.total),
        backgroundColor: ['#3b82f6', '#10b981', '#f59e0b', '#8b5cf6', '#ec4899', '#06b6d4'],
        borderRadius: 6
      }]
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      plugins: { legend: { display: false } },
      scales: { y: { beginAtZero: true } }
    }
  })
}

onMounted(() => {
  loadStats()
})

onBeforeUnmount(() => {
  destroyCharts()
})
</script>

<style scoped>
.admin-stat {
  display: flex;
  flex-direction: column;
  gap: 25px;
}

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

.periode-select {
  padding: 10px 15px;
  border: 2px solid #e2e8f0;
  border-radius: 8px;
  background: white;
  font-weight: 500;
  cursor: pointer;
}

/* KPI */
.kpi-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
  gap: 15px;
}

.kpi-card {
  background: white;
  border-radius: 12px;
  padding: 20px;
  display: flex;
  gap: 15px;
  align-items: flex-start;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
}

.kpi-icon {
  width: 50px;
  height: 50px;
  border-radius: 12px;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 1.5rem;
  flex-shrink: 0;
}

.kpi-icon.revenue { background: #dcfce7; }
.kpi-icon.orders { background: #dbeafe; }
.kpi-icon.clients { background: #fce7f3; }
.kpi-icon.avg { background: #fef3c7; }

.kpi-content { flex: 1; }

.kpi-value {
  font-size: 1.6rem;
  font-weight: 700;
  color: #1e293b;
}

.kpi-label {
  color: #64748b;
  font-size: 0.85rem;
  margin-top: 2px;
}

.kpi-trend {
  font-size: 0.8rem;
  margin-top: 5px;
  font-weight: 600;
}

.kpi-trend.up { color: #10b981; }
.kpi-trend.down { color: #ef4444; }

/* Charts */
.charts-grid {
  display: grid;
  grid-template-columns: repeat(2, 1fr);
  gap: 20px;
}

.chart-card {
  background: white;
  border-radius: 12px;
  padding: 20px;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
}

.chart-card.large {
  grid-column: span 2;
}

.chart-header h2 {
  font-size: 1.1rem;
  color: #1e293b;
  margin: 0 0 20px;
}

.chart-container {
  position: relative;
  height: 300px;
}

.chart-card.large .chart-container {
  height: 350px;
}

/* Top table */
.top-table-card {
  background: white;
  border-radius: 12px;
  padding: 20px;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
}

.top-table-card h2 {
  font-size: 1.2rem;
  color: #1e293b;
  margin: 0 0 15px;
}

.data-table {
  width: 100%;
  border-collapse: collapse;
}

.data-table th {
  padding: 12px;
  text-align: left;
  font-size: 0.8rem;
  font-weight: 600;
  color: #64748b;
  text-transform: uppercase;
  letter-spacing: 0.5px;
  border-bottom: 2px solid #e2e8f0;
}

.data-table td {
  padding: 14px 12px;
  border-bottom: 1px solid #f1f5f9;
}

.cell-main { font-weight: 600; color: #1e293b; }
.cell-price { font-weight: 700; color: #10b981; }
.cell-number { font-weight: 600; color: #3b82f6; }

.rank {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  width: 28px;
  height: 28px;
  border-radius: 50%;
  background: #f1f5f9;
  color: #64748b;
  font-weight: 700;
  font-size: 0.85rem;
}

.rank-1 { background: #fef3c7; color: #d97706; }
.rank-2 { background: #e2e8f0; color: #475569; }
.rank-3 { background: #fed7aa; color: #c2410c; }

@media (max-width: 768px) {
  .charts-grid {
    grid-template-columns: 1fr;
  }
  .chart-card.large {
    grid-column: span 1;
  }
}
</style>