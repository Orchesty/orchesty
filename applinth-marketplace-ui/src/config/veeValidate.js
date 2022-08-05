import { localize, extend } from 'vee-validate'
import VeeValidateEN from 'vee-validate/dist/locale/en.json'
import VeeValidateCS from 'vee-validate/dist/locale/cs.json'
import { required, email, oneOf, numeric, max } from 'vee-validate/dist/rules'
import url from 'validator/es/lib/isURL'

// Add rules
extend('max', max)
extend('url', {
  validate: (value, args) => {
    const options = Object.assign({}, args)
    return url(value, {
      ...options,
      require_tld: false,
    })
  },
})
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
