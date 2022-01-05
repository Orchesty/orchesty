import navigation from './navigation.json'
import apiErrors from './apiErrors.json'
import login from './login'
import button from './button'
import notFound from './notFound'
import acl from './acl'
import forgotPassword from './forgotPassword'
import flashMessages from './flashMessages'
import setNewPassword from './setNewPassword'
import registrationConfirm from './registrationConfirm'
import users from './users'
import dataGrid from './dataGrid'
import profile from './profile'
import vuetify from 'vuetify/es5/locale/cs'
import topologies from './topologies'
import implementations from './implementations'
import scheduledTask from './scheduledTask'
import userTask from './userTask'
import notifications from './notifications'
import amqpSender from './amqpSender'
import curlSender from './curlSender'
import emailSender from './emailSender'
import form from './form'
import appStore from './appStore'
import enums from './enums.json'
import pages from './pages.json'

export default Object.assign(
  navigation,
  apiErrors,
  { $vuetify: vuetify },
  login,
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
  pages
)
