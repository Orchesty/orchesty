import Vue from "vue"
import colors from "../assets/scss/variables.scss"
import Vuetify from "vuetify/lib"
import cs from "vuetify/src/locale/cs"
import en from "vuetify/src/locale/en"

Vue.use(Vuetify)

export const vuetify = new Vuetify({
  lang: {
    locales: { en, cs },
    current: "en",
  },
  icons: {
    iconfont: "md",
  },
  theme: {
    options: {
      variations: false,
    },
    themes: {
      light: {
        ...colors,
      },
    },
  },
})
