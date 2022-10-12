import vuetify from 'vuetify/es5/locale/cs'
import navigation from './navigation.json'
import button from './button.json'
import grid from './grid.json'
import application from './application.json'
import overviewPage from './overviewPage.json'
import applicationsPage from './applicationsPage.json'
import trashPage from './trashPage.json'
import trashModal from './trashModal.json'
import notFoundPage from './notFoundPage.json'
import notLoggedInPage from './notLoggedInPage.json'
import settingsPage from './settingsPage.json'
import appInstalledItem from './appInstalledItem.json'
import profile from './profile.json'
import label from './label.json'
import validation from './validation.json'
import jsonEditor from './jsonEditor.json'

export default Object.assign(
  appInstalledItem,
  application,
  navigation,
  button,
  grid,
  overviewPage,
  applicationsPage,
  trashPage,
  notFoundPage,
  notLoggedInPage,
  settingsPage,
  trashModal,
  {
    $vuetify: vuetify,
  },
  profile,
  label,
  jsonEditor,
  validation
)
