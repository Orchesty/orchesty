import Vue from 'vue'
import Vuetify from 'vuetify/lib/framework'
import { Ripple } from 'vuetify/lib/directives'
import en from 'vuetify/es5/locale/en'
import cs from 'vuetify/es5/locale/cs'
import { LOCALE } from './../localization/index'

Vue.use(Vuetify, {
  directives: {
    Ripple,
  },
})

export default new Vuetify({
  theme: {
    options: {
      customProperties: true,
    },
    themes: {
      light: {
        primary: '#03233A',
        gray: '#bbbbbb',
        white: '#FFFFFF',
        success: '#4CAF50',
        error: '#BB2124',
        secondary: '#339CB4',
        black: '#212121',
      },
    },
  },
  lang: {
    locales: { en, cs },
    current: LOCALE,
  },
})
