<template>
  <div class="container" v-if="invoice">
    <h1>🧾 Facture {{ invoice.numero_facture }}</h1>

    <p>Montant HT : {{ invoice.montant_ht }} €</p>
    <p>TVA : {{ invoice.montant_tva }} €</p>
    <p>Total TTC : {{ invoice.montant_ttc }} €</p>
    <p>Statut paiement : {{ invoice.statut_paiement }}</p>

    <a :href="invoice.url_pdf" target="_blank">📥 Télécharger PDF</a>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import axios from '@/services/axios'
import { useRoute } from 'vue-router'

const route = useRoute()
const invoice = ref(null)

onMounted(async () => {
  const res = await axios.get(`/invoices/${route.params.id}`)
  invoice.value = res.data
})
</script>
