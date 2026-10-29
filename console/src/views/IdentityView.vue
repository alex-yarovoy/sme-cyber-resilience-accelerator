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
const privilegedOnTarget = computed(() => tenant.value.privilegedMfaPercent >= 100)
</script>

<template>
  <h1 class="text-h4 mb-6">Identity</h1>

  <v-card variant="outlined" class="mb-6">
    <v-card-title class="d-flex align-center">
      <span>Staff MFA vs objective 95%</span>
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

  <v-card variant="outlined" class="mb-6">
    <v-card-title>Privileged interactive accounts</v-card-title>
    <v-card-text>{{ tenant.privilegedMfaPercent }}% with MFA vs objective 100% · {{ privilegedOnTarget ? 'On target' : 'Off target' }}. Emergency access exceptions require explicit review.</v-card-text>
  </v-card>
  <v-row>
    <v-col cols="12" md="8">
      <v-card variant="outlined">
        <v-card-title>Staff MFA and passkey adoption</v-card-title>
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
