<script setup lang="ts">
import { computed } from 'vue'
import KpiStat from '../components/KpiStat.vue'
import { useTenant } from '../composables/useTenant'

const { tenant } = useTenant()

const drillResult = computed(() => (tenant.value.lastDrillPassed ? 'Passed' : 'Failed'))

const drillVsRto = computed(
  () => `${tenant.value.lastDrillHours} h vs ${tenant.value.rtoHours} h`,
)

const kmsOnPolicy = computed(() => tenant.value.kmsSecretsPercent >= 100)
</script>

<template>
  <h1 class="text-h4 mb-6">Recovery</h1>

  <v-row class="mb-6">
    <v-col cols="12" sm="6" md="4">
      <KpiStat label="Last drill" :value="`${drillResult}, ${tenant.lastDrillAt}`" />
    </v-col>
    <v-col cols="12" sm="6" md="4">
      <KpiStat label="Duration vs RTO" :value="drillVsRto" />
    </v-col>
    <v-col cols="12" sm="6" md="4">
      <KpiStat label="RPO" :value="`${tenant.rpoHours} h`" />
    </v-col>
  </v-row>

  <v-card variant="outlined">
    <v-card-title class="d-flex align-center">
      <span>Encryption / KMS policy</span>
      <v-chip
        class="ml-2"
        :color="kmsOnPolicy ? 'secondary' : 'primary'"
        size="small"
        variant="flat"
      >
        {{ kmsOnPolicy ? 'On policy' : 'Off policy' }}
      </v-chip>
    </v-card-title>
    <v-card-text>{{ tenant.kmsSecretsPercent }}% of secrets under KMS policy</v-card-text>
  </v-card>
</template>
