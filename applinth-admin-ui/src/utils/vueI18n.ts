import VueI18n from "vue-i18n"
import Vue from "vue"
import { Locale } from "../enums"
import translations from "../translations"

Vue.use(VueI18n)

export const i18n = new VueI18n({
  locale: Locale.En,
  fallbackLocale: Locale.En,
  messages: {
    ...translations,
  },
})
