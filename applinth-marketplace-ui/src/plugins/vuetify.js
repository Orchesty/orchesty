import Vue from 'vue'
import Vuetify from 'vuetify/lib/framework'
import { Ripple } from 'vuetify/lib/directives'

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
})
