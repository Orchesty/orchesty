import "@mdi/font/css/materialdesignicons.css"
import Vue from "vue"
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
} from "vuetify/lib"
import { Ripple } from "vuetify/lib/directives"

Vue.use(Vuetify)

export default new Vuetify({
  icons: {
    iconfont: "mdi",
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
      light: {
        primary: "#03233A",
        gray: "#757575",
        white: "#FFFFFF",
        success: "#4CAF50",
        error: "#BB2124",
        secondary: "#339CB4",
        black: "#212121",
      },
    },
  },
})
