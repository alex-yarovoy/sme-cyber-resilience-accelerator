<script setup lang="ts">
import { onMounted, onUnmounted, ref, watch } from 'vue'
import mermaid from 'mermaid'
import { navy, surface, teal, text } from '../theme'

mermaid.initialize({
  startOnLoad: false,
  securityLevel: 'strict',
  theme: 'base',
  themeVariables: {
    fontFamily: '"Source Sans 3 Variable", sans-serif',
    primaryColor: teal,
    primaryTextColor: text,
    primaryBorderColor: navy,
    lineColor: navy,
    secondaryColor: surface,
    tertiaryColor: surface,
  },
})

let seq = 0

const props = defineProps<{
  chart: string
}>()

const host = ref<HTMLElement | null>(null)

async function draw() {
  if (!host.value) return
  seq += 1
  const { svg, bindFunctions } = await mermaid.render(`mermaid-${seq}`, props.chart)
  if (!host.value) return
  host.value.innerHTML = svg
  bindFunctions?.(host.value)
}

onMounted(draw)
watch(() => props.chart, draw)
onUnmounted(() => {
  if (host.value) host.value.innerHTML = ''
})
</script>

<template>
  <div ref="host" class="diagram" />
</template>

<style scoped>
.diagram :deep(svg) {
  max-width: 100%;
  height: auto;
}
</style>
