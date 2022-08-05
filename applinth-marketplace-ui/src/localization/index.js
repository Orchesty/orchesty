import Vue from 'vue'
import VueI18n from 'vue-i18n'
import en from './en'
import cs from './cs'

Vue.use(VueI18n)

export const LOCALE = 'en'

export const i18n = new VueI18n({
  locale: LOCALE,
  fallbackLocale: LOCALE,
  messages: {
    en,
    cs,
  },
})
