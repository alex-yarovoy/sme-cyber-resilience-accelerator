<script setup lang="ts">
import { computed } from 'vue'
import { useTenant } from '../composables/useTenant'
import { controls } from '../fixtures/controls'

const { tenant } = useTenant()

function formatMeasure(value: number, unit: string): string {
  if (unit === '%') return `${value}%`
  if (unit === 'h') return `${value} h`
  return String(value)
}

const rows = computed(() =>
  controls
    .filter((row) => row.tenantId === tenant.value.id)
    .map((row) => ({
      id: row.id,
      policyTheme: row.policyTheme,
      control: row.control,
      kpi: `${formatMeasure(row.current, row.unit)} vs ${formatMeasure(row.target, row.unit)}`,
    })),
)

const headers = [
  { title: 'Policy theme', key: 'policyTheme' },
  { title: 'Control', key: 'control' },
  { title: 'KPI', key: 'kpi' },
]
</script>

<template>
  <h1 class="text-h4 mb-6">Controls</h1>

  <v-data-table
    :headers="headers"
    :items="rows"
    item-value="id"
    :items-per-page="-1"
    hide-default-footer
    density="compact"
  />
</template>
