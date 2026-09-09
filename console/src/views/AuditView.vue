<script setup lang="ts">
import { computed } from 'vue'
import { useTenant } from '../composables/useTenant'
import { auditEvents } from '../fixtures/auditEvents'
import type { AuditEventType } from '../fixtures/types'

const { tenant } = useTenant()

const typeLabels: Record<AuditEventType, string> = {
  login: 'Login',
  mfa: 'MFA',
  passkey: 'Passkey',
  'policy-change': 'Policy change',
}

const rows = computed(() =>
  auditEvents
    .filter((row) => row.tenantId === tenant.value.id)
    .map((row) => ({
      id: row.id,
      type: typeLabels[row.type],
      ip: row.ip,
      outcome: row.outcome === 'success' ? 'Success' : 'Failure',
      at: row.at,
    })),
)

const headers = [
  { title: 'Type', key: 'type' },
  { title: 'IP', key: 'ip' },
  { title: 'Outcome', key: 'outcome' },
  { title: 'At', key: 'at' },
]
</script>

<template>
  <h1 class="text-h4 mb-6">Audit</h1>

  <v-data-table
    :headers="headers"
    :items="rows"
    item-value="id"
    :items-per-page="-1"
    hide-default-footer
    density="compact"
  />
</template>
