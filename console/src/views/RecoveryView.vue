<script setup lang="ts">
import { computed } from 'vue'
import KpiStat from '../components/KpiStat.vue'
import { useTenant } from '../composables/useTenant'

const { tenant } = useTenant()

const drillResult = computed(() => (tenant.value.lastDrillPassed ? 'Passed' : 'Failed'))

const drillVsRto = computed(
  () => `${tenant.value.lastDrillHours} h vs ${tenant.value.rtoHours} h`,
)

const secretsOnPolicy = computed(() => tenant.value.managedSecretsPercent >= 100)
</script>

<template>
  <h1 class="text-h4 mb-6">Recovery</h1>

  <v-row class="mb-6">
    <v-col cols="12" sm="6" md="4">
      <KpiStat label="Last drill" :value="`${drillResult}, ${tenant.lastDrillAt}`" />
    </v-col>
    <v-col cols="12" sm="6" md="4">
      <KpiStat label="Restore duration vs objective" :value="drillVsRto" />
    </v-col>
    <v-col cols="12" sm="6" md="4">
      <KpiStat label="Backup age vs data loss objective" :value="`${tenant.lastBackupAgeHours} h vs ${tenant.rpoHours} h`" />
    </v-col>
  </v-row>

  <p class="text-body-2 mb-4">Synthetic drill only. Actual pilots must verify workload integrity, usable service, elapsed restore time, and recoverable data age.</p>
  <v-card variant="outlined">
    <v-card-title class="d-flex align-center">
      <span>Managed application secrets</span>
      <v-chip
        class="ml-2"
        :color="secretsOnPolicy ? 'secondary' : 'primary'"
        size="small"
        variant="flat"
      >
        {{ secretsOnPolicy ? 'On policy' : 'Off policy' }}
      </v-chip>
    </v-card-title>
    <v-card-text>{{ tenant.managedSecretsPercent }}% of identified runtime secrets in a managed secret store. Encryption keys are inventoried and protected separately.</v-card-text>
  </v-card>
</template>
