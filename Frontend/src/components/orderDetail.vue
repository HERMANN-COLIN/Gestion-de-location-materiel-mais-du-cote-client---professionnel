<template>
  <div class="container" v-if="order">
    <h1>📄 Commande {{ order.numero_commande }}</h1>

    <p>Statut : {{ order.statut }}</p>
    <p>Total : {{ order.montant_total }} €</p>

    <h3>Détails</h3>
    <ul>
      <li v-for="item in order.details" :key="item.id">
        {{ item.materiel.nom }} × {{ item.quantite }} —
        {{ item.sous_total }} €
      </li>
    </ul>

    <router-link :to="`/invoices/${order.id}`">
      Voir facture
    </router-link>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import axios from '@/services/axios'
import { useRoute } from 'vue-router'

const route = useRoute()
const order = ref(null)

onMounted(async () => {
  const res = await axios.get(`/orders/${route.params.id}`)
  order.value = res.data
})
</script>
