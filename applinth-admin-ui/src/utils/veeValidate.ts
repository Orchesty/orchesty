import { extend, localize } from "vee-validate"
import VeeValidateCS from "vee-validate/dist/locale/cs"
import * as rules from "vee-validate/dist/rules"
import { i18n } from "./vueI18n"
import { Locale } from "../enums"

export type Rules = { [index in keyof typeof rules]?: any }

for (const [rule, validation] of Object.entries(rules)) {
  extend(rule, {
    ...(validation as Record<string, any>),
  })
}

localize({
  cs: VeeValidateCS,
} as { [index in Locale]: any })

localize(i18n.locale)
