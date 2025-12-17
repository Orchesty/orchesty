import "@mdi/font/css/materialdesignicons.css"
import Vue from "vue"
import Vuetify from "vuetify"
import "vuetify/dist/vuetify.min.css"

Vue.use(Vuetify)

export default new Vuetify({
  icons: {
    iconfont: "mdi",
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
