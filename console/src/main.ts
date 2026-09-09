import { createApp } from 'vue'
import { createPinia } from 'pinia'
import { createVuetify } from 'vuetify'
import * as components from 'vuetify/components'
import * as directives from 'vuetify/directives'
import 'vuetify/styles'
import '@fontsource-variable/source-sans-3'
import App from './App.vue'
import { router } from './router'
import { acceleratorTheme } from './theme'

const vuetify = createVuetify({
  components,
  directives,
  theme: {
    defaultTheme: 'accelerator',
    themes: {
      accelerator: acceleratorTheme,
    },
  },
})

createApp(App).use(createPinia()).use(router).use(vuetify).mount('#app')
