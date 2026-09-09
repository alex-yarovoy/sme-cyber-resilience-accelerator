import type { ThemeDefinition } from 'vuetify'

export const navy = '#1B365D'
export const teal = '#0D7377'
export const surface = '#F7F8FA'
export const text = '#1A1A1A'

export const acceleratorTheme: ThemeDefinition = {
  dark: false,
  colors: {
    primary: navy,
    secondary: teal,
    surface,
    background: surface,
    'on-surface': text,
    'on-background': text,
    'on-primary': '#FFFFFF',
    'on-secondary': '#FFFFFF',
  },
}
