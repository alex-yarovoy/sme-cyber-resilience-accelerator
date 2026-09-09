<script setup lang="ts">
import { computed } from 'vue'
import CsfRadar from '../components/CsfRadar.vue'
import KpiStat from '../components/KpiStat.vue'
import { useTenant } from '../composables/useTenant'

const { tenant } = useTenant()

const csfScore = computed(() => {
  const values = Object.values(tenant.value.csf)
  return Math.round(values.reduce((sum, n) => sum + n, 0) / values.length)
})

const drillValue = computed(() => {
  const result = tenant.value.lastDrillPassed ? 'Passed' : 'Failed'
  return `${result}, ${tenant.value.lastDrillHours} h`
})

const bundles = computed(() => {
  const current = tenant.value
  return [
    {
      id: 'identity',
      label: 'Identity',
      detail: `Protect ${current.csf.protect} · MFA ${current.mfaPercent}%`,
      onTarget: current.mfaPercent >= 95,
    },
    {
      id: 'detection',
      label: 'Detection',
      detail: `Detect ${current.csf.detect} · Log ${current.logCoveragePercent}%`,
      onTarget: current.logCoveragePercent >= 90,
    },
    {
      id: 'recovery',
      label: 'Recovery',
      detail: `Recover ${current.csf.recover} · ${current.lastDrillPassed ? 'Passed' : 'Failed'}`,
      onTarget: current.lastDrillPassed,
    },
  ]
})
</script>

<template>
  <h1 class="text-h4 mb-6">Overview</h1>

  <v-row class="mb-6">
    <v-col cols="12" sm="6" md="3">
      <KpiStat label="CSF score" :value="String(csfScore)" />
    </v-col>
    <v-col cols="12" sm="6" md="3">
      <KpiStat label="MFA" :value="`${tenant.mfaPercent}%`" />
    </v-col>
    <v-col cols="12" sm="6" md="3">
      <KpiStat label="Last drill" :value="drillValue" />
    </v-col>
    <v-col cols="12" sm="6" md="3">
      <KpiStat label="Log coverage" :value="`${tenant.logCoveragePercent}%`" />
    </v-col>
  </v-row>

  <v-row>
    <v-col cols="12" md="7">
      <v-card variant="outlined">
        <v-card-title>CSF 2.0 functions</v-card-title>
        <v-card-text>
          <CsfRadar :scores="tenant.csf" />
        </v-card-text>
      </v-card>
    </v-col>
    <v-col cols="12" md="5">
      <div class="text-subtitle-1 mb-3">Bundles</div>
      <div v-for="bundle in bundles" :key="bundle.id" class="mb-3">
        <v-chip :color="bundle.onTarget ? 'secondary' : 'primary'" variant="flat" class="mb-1">
          {{ bundle.label }} · {{ bundle.onTarget ? 'On target' : 'Off target' }}
        </v-chip>
        <div class="text-body-2">{{ bundle.detail }}</div>
      </div>
    </v-col>
  </v-row>
</template>
