<template>
  <div class="container">
    <h1>📦 Mes commandes</h1>

    <table>
      <thead>
        <tr>
          <th>N°</th>
          <th>Date</th>
          <th>Statut</th>
          <th>Total</th>
          <th></th>
        </tr>
      </thead>

      <tbody>
        <tr v-for="order in orders" :key="order.id">
          <td>{{ order.numero_commande }}</td>
          <td>{{ order.date_commande }}</td>
          <td>{{ order.statut }}</td>
          <td>{{ order.montant_total }} €</td>
          <td>
            <router-link :to="`/orders/${order.id}`">Voir</router-link>
          </td>
        </tr>
      </tbody>
    </table>
  </div>
</template>

<script setup>
import { onMounted, ref } from 'vue'
import axios from '@/services/axios'

const orders = ref([])

onMounted(async () => {
  const res = await axios.get('/orders')
  orders.value = res.data
})
</script>
