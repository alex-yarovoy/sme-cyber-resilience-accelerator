<script setup lang="ts">
import { sampleTenants } from '../fixtures/sampleTenants'
import type { SampleTenant } from '../fixtures/types'

const sectorLabel: Record<SampleTenant['sector'], string> = {
  hospitality: 'Hospitality',
  ecommerce: 'Ecommerce',
}

function csfScore(csf: SampleTenant['csf']): number {
  const values = Object.values(csf)
  return Math.round(values.reduce((sum, n) => sum + n, 0) / values.length)
}

const rows = sampleTenants.map((tenant) => ({
  id: tenant.id,
  name: tenant.name,
  sector: sectorLabel[tenant.sector],
  mfa: `${tenant.mfaPercent}%`,
  lastDrill: `${tenant.lastDrillPassed ? 'Passed' : 'Failed'}, ${tenant.lastDrillAt}`,
  csfScore: csfScore(tenant.csf),
}))

const headers = [
  { title: 'Name', key: 'name' },
  { title: 'Sector', key: 'sector' },
  { title: 'MFA', key: 'mfa' },
  { title: 'Last drill', key: 'lastDrill' },
  { title: 'CSF score', key: 'csfScore' },
]
</script>

<template>
  <h1 class="text-h4 mb-6">Clients</h1>

  <v-data-table
    :headers="headers"
    :items="rows"
    item-value="id"
    :items-per-page="-1"
    hide-default-footer
    density="compact"
  />
</template>
