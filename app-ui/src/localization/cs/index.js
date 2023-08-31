import navigation from './navigation.json'
import apiErrors from './apiErrors.json'
import button from './button.json'
import notFound from './notFound.json'
import acl from './acl.json'
import forgotPassword from './forgotPassword.json'
import flashMessages from './flashMessages.json'
import setNewPassword from './setNewPassword.json'
import registrationConfirm from './registrationConfirm.json'
import users from './users.json'
import dataGrid from './dataGrid.json'
import profile from './profile.json'
import vuetify from 'vuetify/es5/locale/cs'
import topologies from './topologies.json'
import implementations from './implementations.json'
import scheduledTask from './scheduledTask.json'
import userTask from './userTask.json'
import notifications from './notifications.json'
import amqpSender from './amqpSender.json'
import curlSender from './curlSender.json'
import emailSender from './emailSender.json'
import form from './form.json'
import appStore from './appStore.json'
import enums from './enums.json'
import pages from './pages.json'
import healthcheck from './healthcheck.json'
import auth from './auth.json'
import logs from './logs.json'
import sidebar from './sidebar.json'
import validation from './validation.json'

export default Object.assign(
  navigation,
  apiErrors,
  { $vuetify: vuetify },
  button,
  notFound,
  acl,
  forgotPassword,
  flashMessages,
  setNewPassword,
  registrationConfirm,
  users,
  dataGrid,
  profile,
  topologies,
  implementations,
  scheduledTask,
  userTask,
  notifications,
  amqpSender,
  emailSender,
  curlSender,
  form,
  appStore,
  enums,
  pages,
  logs,
  auth,
  sidebar,
  { healthcheck: healthcheck },
  validation
)
