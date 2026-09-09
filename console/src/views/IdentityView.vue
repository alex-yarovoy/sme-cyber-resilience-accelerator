<script setup lang="ts">
import { computed } from 'vue'
import KpiStat from '../components/KpiStat.vue'
import { useTenant } from '../composables/useTenant'
import { detection } from '../fixtures/detection'

const { tenant } = useTenant()

const snapshot = computed(
  () => detection.find((row) => row.tenantId === tenant.value.id) ?? detection[0],
)

const mfaOnTarget = computed(() => tenant.value.mfaPercent >= 95)
</script>

<template>
  <h1 class="text-h4 mb-6">Identity</h1>

  <v-card variant="outlined" class="mb-6">
    <v-card-title class="d-flex align-center">
      <span>MFA vs target 95%</span>
      <v-chip
        class="ml-2"
        :color="mfaOnTarget ? 'secondary' : 'primary'"
        size="small"
        variant="flat"
      >
        {{ mfaOnTarget ? 'On target' : 'Off target' }}
      </v-chip>
    </v-card-title>
    <v-card-text>
      <div class="text-body-2 mb-2">{{ tenant.mfaPercent }}% vs 95%</div>
      <v-progress-linear
        :model-value="tenant.mfaPercent"
        :color="mfaOnTarget ? 'secondary' : 'primary'"
        height="12"
      />
    </v-card-text>
  </v-card>

  <v-row>
    <v-col cols="12" md="8">
      <v-card variant="outlined">
        <v-card-title>TOTP vs passkey</v-card-title>
        <v-card-text>
          <div class="text-body-2 mb-2">MFA {{ tenant.mfaPercent }}%</div>
          <v-progress-linear class="mb-4" :model-value="tenant.mfaPercent" color="primary" height="12" />
          <div class="text-body-2 mb-2">Passkey {{ tenant.passkeyPercent }}%</div>
          <v-progress-linear :model-value="tenant.passkeyPercent" color="secondary" height="12" />
        </v-card-text>
      </v-card>
    </v-col>
    <v-col cols="12" md="4">
      <KpiStat label="Lockout count" :value="String(snapshot.failedLoginCount)" />
    </v-col>
  </v-row>
</template>
