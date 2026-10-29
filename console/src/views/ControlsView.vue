<script setup lang="ts">
import { computed } from 'vue'
import { useTenant } from '../composables/useTenant'
import { controls } from '../fixtures/controls'

const { tenant } = useTenant()

function formatMeasure(value: number | null, unit: string): string {
  if (value === null) return 'N/A'
  if (unit === '%') return `${value}%`
  if (unit === 'h') return `${value} h`
  if (unit === 'min') return `${value} min`
  return String(value)
}

const rows = computed(() =>
  controls
    .filter((row) => row.tenantId === tenant.value.id)
    .map((row) => ({
      id: row.id,
      policyTheme: row.policyTheme,
      control: row.control,
      kpi: `${formatMeasure(row.current, row.unit)} vs ${row.direction === 'at-most' ? '≤' : '≥'} ${formatMeasure(row.target, row.unit)}`,
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

  <p class="text-body-2 mb-4">Prospective pilot objectives compared with synthetic example values. Inventory, scope, exceptions, and acceptance evidence are agreed for each pilot.</p>
  <v-data-table
    :headers="headers"
    :items="rows"
    item-value="id"
    :items-per-page="-1"
    hide-default-footer
    density="compact"
  />
</template>
