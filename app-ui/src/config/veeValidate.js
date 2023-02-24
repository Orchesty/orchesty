import { localize, extend } from "vee-validate"
import VeeValidateEN from "vee-validate/dist/locale/en.json"
import VeeValidateCS from "vee-validate/dist/locale/cs.json"
import { required, email, oneOf, numeric, max } from "vee-validate/dist/rules"
import isURL from "validator/es/lib/isURL"
import { i18n } from "@/localization"

// Add rules
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

// Add localization
localize("cs", VeeValidateCS)
localize("en", VeeValidateEN)
