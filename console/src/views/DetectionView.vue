<script setup lang="ts">
import { computed } from 'vue'
import KpiStat from '../components/KpiStat.vue'
import { useTenant } from '../composables/useTenant'
import { detection, incidentRateTargetLabel } from '../fixtures/detection'
import type { AlertRouteStatus } from '../fixtures/types'

const { tenant } = useTenant()

const snapshot = computed(
  () => detection.find((row) => row.tenantId === tenant.value.id) ?? detection[0],
)

const statusColor: Record<AlertRouteStatus, string | undefined> = {
  active: 'secondary',
  degraded: 'primary',
  unconfigured: undefined,
}

const statusLabel: Record<AlertRouteStatus, string> = {
  active: 'Active',
  degraded: 'Degraded',
  unconfigured: 'Unconfigured',
}
</script>

<template>
  <h1 class="text-h4 mb-6">Detection</h1>

  <v-row class="mb-6">
    <v-col cols="12" sm="6" md="4">
      <KpiStat label="Log coverage" :value="`${snapshot.logCoveragePercent}%`" />
    </v-col>
    <v-col cols="12" sm="6" md="4">
      <KpiStat label="Failed logins" :value="String(snapshot.failedLoginCount)" />
    </v-col>
    <v-col cols="12" sm="6" md="4">
      <KpiStat label="Program target" :value="incidentRateTargetLabel" />
    </v-col>
  </v-row>

  <div class="text-subtitle-1 mb-3">Alert routes</div>
  <v-chip
    v-for="route in snapshot.alertRoutes"
    :key="route.channel"
    class="mr-2 mb-2"
    :color="statusColor[route.status]"
    :variant="route.status === 'unconfigured' ? 'outlined' : 'flat'"
  >
    {{ route.channel }} · {{ statusLabel[route.status] }}
  </v-chip>
</template>
