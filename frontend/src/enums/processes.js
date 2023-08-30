export default {
  serverApiGatewayChange: hash => `server-api-gateway-change-${hash}`,
  authLogin: hash => `auth-login-${hash}`,
  authLogout: hash => `auth-logout-${hash}`,
  authRegister: hash => `auth-register-${hash}`,
  authActivate: hash => `auth-activate-${hash}`,
  authResetPassword: hash => `auth-reset-pswd-${hash}`,
  authSetPassword: hash => `auth-set-pswd-${hash}`,
  topologyLoad: id => `topology-load-${id}`,
  topologyCreate: hash => `topology-create-${hash}`,
  topologyUpdate: id => `topology-update-${id}`,
  topologyDelete: id => `topology-delete-${id}`,
  topologyPublish: id => `topology-publish-${id}`,
  topologyClone: id => `topology-clone-${id}`,
  topologyTest: id => `topology-test-${id}`,
  topologySaveScheme: id => `topology-save-scheme-${id}`,
  nodeUpdate: id => `node-update-${id}`,
  nodeRun: id => `node-run-${id}`,
  authorizationLoad: id => `authorization-load-${id}`,
  authorizationAuthorize: id => `authorization-authorize-${id}`,
  authorizationSaveSettings: id => `authorization-settings-save-${id}`,
  authorizationLoadSettings: id => `authorization-settings-load-${id}`,
  categoryCreate: hash => `category-create-${hash}`,
  categoryUpdate: id => `category-update-${id}`,
  categoryDelete: id => `category-delete-${id}`,
  notificationSettingsUpdate: () => 'notification-settings-update',
};