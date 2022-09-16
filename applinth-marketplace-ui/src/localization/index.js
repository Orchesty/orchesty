import Vue from 'vue'
import VueI18n from 'vue-i18n'
import en from './en'
import cs from './cs'
import validationMessagesEN from 'vee-validate/dist/locale/en.json'
import validationMessagesCS from 'vee-validate/dist/locale/cs.json'
import { configure, extend } from 'vee-validate'
import { email, max, numeric, oneOf, required } from 'vee-validate/dist/rules'

extend('max', max)
extend('numeric', numeric)
extend('required', required)
extend('email', email)
extend('oneOf', oneOf)
extend('passwordConfirm', {
  validate: (value, { other }) => value === other,
  params: [{ name: 'other', isTarget: true }],
})

Vue.use(VueI18n)

export const LOCALE = 'en'

export const i18n = new VueI18n({
  locale: LOCALE,
  fallbackLocale: LOCALE,
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
})

configure({
  defaultMessage: (field, values) => {
    values._field_ = field
    return i18n.t(`validations.messages.${values._rule_}`, values)
  },
})
