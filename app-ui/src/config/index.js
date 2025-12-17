import vuetify from "./vuetify"
import "./veeValidate"
import { ability } from "./ability"

import configProd from "./config.prod"
import configDev from "./config.dev"

const config = process.env.NODE_ENV === "production" ? configProd : configDev

export { config, vuetify, ability }
