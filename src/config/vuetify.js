import '@mdi/font/css/materialdesignicons.css'
import Vue from 'vue'
import Vuetify, {
  VApp,
  VCard,
  VInput,
  VLabel,
  VMessages,
  VTextField,
  VProgressLinear,
  VCheckbox,
  VChip,
  VDatePicker,
  VAutocomplete,
  VSimpleCheckbox,
  VTreeview,
  VDialog,
} from 'vuetify/lib'
import { Ripple } from 'vuetify/lib/directives'

Vue.use(Vuetify)

export default new Vuetify({
  icons: {
    iconfont: 'mdi',
  },
  components: {
    VApp,
    VCard,
    VInput,
    VLabel,
    VMessages,
    VTextField,
    VProgressLinear,
    VCheckbox,
    VChip,
    VDatePicker,
    VAutocomplete,
    VSimpleCheckbox,
    VTreeview,
    VDialog,
  },
  directives: {
    Ripple,
  },
  theme: {
    options: {
      customProperties: true,
    },
    dark: false,
    themes: {
      dark: {
        primary: '#00B8A9',
        accent: '#F6416C',
        primaryButton: '#1c2849',
        secondary: '#FFDE7D',
        success: '#42A8E0',
        info: '#66C600',
        warning: '#F7B500',
        error: '#D11818',
      },
      light: {
        text: '#1a1a1a',
        primary: '#1c2849',
        placeholder: '#D9C5B2',
        white: '#F3F3F4',
        secondary: '#339cb4',
        disabled: '#7E7F83',

        accent: '#F6416C',
        success: '#22bb33',
        info: '#aaaaaa',
        warning: '#F7B500',
        error: '#bb2124',
      },
    },
  },
})
