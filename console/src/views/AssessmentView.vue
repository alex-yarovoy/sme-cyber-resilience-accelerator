<script setup lang="ts">
import { computed } from 'vue'
import KpiStat from '../components/KpiStat.vue'
import { useTenant } from '../composables/useTenant'
import { controls } from '../fixtures/controls'

const { tenant } = useTenant()

function formatMeasure(value: number, unit: string): string {
  if (unit === '%') return `${value}%`
  if (unit === 'h') return `${value} h`
  return String(value)
}

const misconfigPath = computed(() => {
  const current = tenant.value
  return `${current.misconfigsBaseline} → ${current.misconfigsCurrent} → ${current.misconfigsTarget}`
})

const rows = computed(() =>
  controls
    .filter((row) => row.tenantId === tenant.value.id)
    .map((row) => ({
      id: row.id,
      control: row.control,
      current: formatMeasure(row.current, row.unit),
      target: formatMeasure(row.target, row.unit),
      owner: tenant.value.msp,
    })),
)

const headers = [
  { title: 'Control', key: 'control' },
  { title: 'Current', key: 'current' },
  { title: 'Target', key: 'target' },
  { title: 'Owner', key: 'owner' },
]
</script>

<template>
  <h1 class="text-h4 mb-6">Assessment</h1>

  <v-row class="mb-6">
    <v-col cols="12" md="6">
      <KpiStat label="Misconfigurations" :value="misconfigPath" />
    </v-col>
    <v-col cols="12" md="6">
      <KpiStat label="Assets" :value="String(tenant.assetCount)" />
    </v-col>
  </v-row>

  <v-data-table
    :headers="headers"
    :items="rows"
    item-value="id"
    :items-per-page="-1"
    hide-default-footer
    density="compact"
  />
</template>
