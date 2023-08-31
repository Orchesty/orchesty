import { localize, extend } from 'vee-validate'
import VeeValidateEN from 'vee-validate/dist/locale/en.json'
import VeeValidateCS from 'vee-validate/dist/locale/cs.json'
import { required, email, oneOf, numeric, max } from 'vee-validate/dist/rules'

// Add rules
extend('max', max)
extend('numeric', numeric)
extend('required', required)
extend('email', email)
extend('oneOf', oneOf)
extend('passwordConfirm', {
  validate: (value, { other }) => value === other,
  params: [{ name: 'other', isTarget: true }],
})

// Add localization
localize('cs', VeeValidateCS)
localize('en', VeeValidateEN)
