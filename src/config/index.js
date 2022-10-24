import vuetify from "./vuetify"
import "./veeValidate"
import { ability } from "./ability"

const config =
  process.env.NODE_ENV === "production"
    ? require("./config.prod").default
    : require("./config.dev").default

export { config, vuetify, ability }
