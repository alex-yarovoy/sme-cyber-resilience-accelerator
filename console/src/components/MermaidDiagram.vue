<script lang="ts">
import mermaid from 'mermaid'
import { navy, surface, teal, text } from '../theme'

let seq = 0

function nextMermaidId(): string {
  seq += 1
  return `mermaid-${seq}`
}

mermaid.initialize({
  startOnLoad: false,
  securityLevel: 'strict',
  theme: 'base',
  flowchart: { htmlLabels: false },
  themeVariables: {
    fontFamily: 'Arial, sans-serif',
    primaryColor: teal,
    primaryTextColor: text,
    primaryBorderColor: navy,
    lineColor: navy,
    secondaryColor: surface,
    tertiaryColor: surface,
  },
})
</script>

<script setup lang="ts">
import { onMounted, onUnmounted, ref, watch } from 'vue'
import mermaid from 'mermaid'

const props = defineProps<{
  chart: string
}>()

const host = ref<HTMLElement | null>(null)
let generation = 0

async function draw() {
  if (!host.value) return
  await document.fonts.ready
  if (!host.value) return
  const id = nextMermaidId()
  const token = ++generation
  const { svg, bindFunctions } = await mermaid.render(id, props.chart)
  if (!host.value || token !== generation) return
  host.value.innerHTML = svg
  bindFunctions?.(host.value)
}

onMounted(draw)
watch(() => props.chart, draw)
onUnmounted(() => {
  generation += 1
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
.diagram :deep(.nodeLabel) {
  letter-spacing: 0;
}
.diagram :deep(foreignObject) {
  overflow: visible;
}
</style>
