import Vue from "vue"
import VueI18n from "vue-i18n"
import en from "./en"
import cs from "./cs"
import validationMessagesEN from "vee-validate/dist/locale/en.json"
import validationMessagesCS from "vee-validate/dist/locale/cs.json"
import { configure, extend } from "vee-validate"
import { email, max, numeric, oneOf, required } from "vee-validate/dist/rules"
import isURL from "validator/es/lib/isURL"

extend("max", max)
extend("numeric", numeric)
extend("required", required)
extend("email", email)
extend("oneOf", oneOf)
extend("passwordConfirm", {
  validate: (value, { other }) => value === other,
  params: [{ name: "other", isTarget: true }],
})
extend("url", {
  validate: (value) => isURL(value, { require_protocol: true }),
  // https://vee-validate.logaretm.com/v3/guide/basics.html#messages
  message: (field, values) => {
    values._field_ = field
    return i18n.t(`validation.url`, values)
  },
})

Vue.use(VueI18n)

const browserLang = navigator.language || window.navigator.language || "cs"

export const LOCALE = browserLang.substring(0, 2)

export const i18n = new VueI18n({
  locale: LOCALE,
  fallbackLocale: "en",
  messages: {
    en: {
      validations: validationMessagesEN,
      ...en,
    },
    cs: {
      validations: validationMessagesCS,
      ...cs,
    },
  },
  // https://kazupon.github.io/vue-i18n/guide/pluralization.html#custom-pluralization
  pluralizationRules: {
    /**
     * @param choice {number} a choice index given by the input to $tc: `$tc('path.to.rule', choiceIndex)`
     * @param choicesLength {number} an overall amount of available choices
     * @returns a final choice index to select plural word by
     */
    cs: function (choice, choicesLength) {
      // Expecting 4 choices
      if (choicesLength < 4) {
        return choicesLength - 1
      }

      if (choice === 0) {
        return 0
      }

      if (choice === 1) {
        return 1
      }

      if (choice >= 2 && choice <= 4) {
        return 2
      }

      return 3
    },
  },
})

configure({
  defaultMessage: (field, values) => {
    values._field_ = field
    return i18n.t(`validations.messages.${values._rule_}`, values)
  },
})
