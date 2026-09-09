<script setup lang="ts">
import { computed } from 'vue'
import { useTenant } from '../composables/useTenant'
import { rolloutPhases } from '../fixtures/rollout'

const { tenant } = useTenant()

const currentPhase = computed(() => {
  const day = tenant.value.rolloutDay
  return [...rolloutPhases]
    .filter((phase) => day >= phase.dayStart && day <= phase.dayEnd)
    .sort((a, b) => b.dayStart - a.dayStart)[0]
})

const markerPct = computed(() => `${(tenant.value.rolloutDay / 90) * 100}%`)
</script>

<template>
  <h1 class="text-h4 mb-6">Rollout</h1>

  <p class="text-subtitle-1 mb-4">Day {{ tenant.rolloutDay }} of 90</p>

  <div class="track-wrap mb-8">
    <div class="track">
      <div
        v-for="phase in rolloutPhases"
        :key="phase.id"
        class="track-phase"
        :class="{ current: phase.id === currentPhase?.id }"
      >
        {{ phase.label }}
      </div>
    </div>
    <div class="marker" :style="{ left: markerPct }" />
  </div>

  <v-row>
    <v-col v-for="phase in rolloutPhases" :key="phase.id" cols="12" md="4">
      <v-card variant="outlined">
        <v-card-title class="d-flex align-center">
          <span>{{ phase.label }}</span>
          <v-chip v-if="phase.id === currentPhase?.id" class="ml-2" color="secondary" size="small" variant="flat">
            Current
          </v-chip>
        </v-card-title>
        <v-card-text>
          <div class="mb-2">Days {{ phase.dayStart }}–{{ phase.dayEnd }}</div>
          <div>{{ phase.focus }}</div>
        </v-card-text>
      </v-card>
    </v-col>
  </v-row>
</template>

<style scoped>
.track-wrap {
  position: relative;
  padding-bottom: 20px;
}

.track {
  display: flex;
  height: 40px;
  border: 1px solid #1b365d;
}

.track-phase {
  flex: 1;
  display: flex;
  align-items: center;
  justify-content: center;
  background: #f7f8fa;
  color: #1a1a1a;
  border-right: 1px solid #1b365d;
}

.track-phase:last-child {
  border-right: none;
}

.track-phase.current {
  background: #0d7377;
  color: #ffffff;
}

.marker {
  position: absolute;
  top: 0;
  width: 2px;
  height: 52px;
  background: #1b365d;
  transform: translateX(-50%);
}
</style>
