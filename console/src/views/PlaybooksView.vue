<script setup lang="ts">
import { playbooks } from '../fixtures/playbooks'
import type { Sector } from '../fixtures/types'

const sectorLabel: Record<Sector, string> = {
  hospitality: 'Hospitality',
  ecommerce: 'Ecommerce',
}
</script>

<template>
  <h1 class="text-h4 mb-6">Playbooks</h1>

  <v-row>
    <v-col v-for="playbook in playbooks" :key="playbook.sector" cols="12" md="6">
      <v-card variant="outlined">
        <v-card-title>{{ sectorLabel[playbook.sector] }}</v-card-title>
        <v-card-subtitle>{{ playbook.productLabel }}</v-card-subtitle>
        <v-card-text>
          <div v-for="item in playbook.items" :key="item.id" class="mb-6">
            <div class="text-subtitle-1">{{ item.title }}</div>
            <div class="text-caption text-medium-emphasis mb-2">{{ item.surfaces.join(' · ') }}</div>
            <ol class="pl-4 mb-0">
              <li v-for="step in item.steps" :key="step" class="mb-1">{{ step }}</li>
            </ol>
          </div>
        </v-card-text>
      </v-card>
    </v-col>
  </v-row>
</template>
