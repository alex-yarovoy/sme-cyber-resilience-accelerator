<script setup lang="ts">
import MermaidDiagram from '../components/MermaidDiagram.vue'
import { diagrams } from '../fixtures/diagrams'
</script>

<template>
  <h1 class="text-h4 mb-6">Architecture</h1>

  <v-card v-for="diagram in diagrams" :key="diagram.id" variant="outlined" class="mb-6">
    <v-card-title>{{ diagram.title }}</v-card-title>
    <v-card-text>
      <MermaidDiagram :chart="diagram.mermaid" />
    </v-card-text>
  </v-card>
</template>
