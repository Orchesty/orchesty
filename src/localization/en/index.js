import navigation from "./navigation.json"
import apiErrors from "./apiErrors.json"
import button from "./button.json"
import acl from "./acl.json"
import flashMessages from "./flashMessages.json"
import vuetify from "vuetify/es5/locale/cs"
import validation from "./validation.json"
import form from "./form.json"
import grid from "./grid.json"
import page from "./page.json"
import modal from "./modal.json"
import contextMenu from "./contextMenu.json"

export default Object.assign(
  navigation,
  apiErrors,
  { $vuetify: vuetify },
  button,
  acl,
  flashMessages,
  validation,
  form,
  page,
  grid,
  modal,
  contextMenu
)
