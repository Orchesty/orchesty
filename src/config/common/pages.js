export default {
  login: {
    id: 'login',
    caption: 'Login',
    needAuth: false,
    simpleRoute: '/login'
  },
  registration: {
    id: 'registration',
    caption: 'Registration',
    needAuth: false,
    simpleRoute: '/registration'
  },
  reset_password: {
    id: 'reset_password',
    caption: 'Reset password',
    needAuth: false,
    simpleRoute: '/reset_password'
  },
  set_password: {
    id: 'set_password',
    caption: 'Set new password',
    needAuth: false,
    acceptUrl: (path, query) => path == '/set_password' && query.token ? {args: {token: query.token}} : false,
    createUrl: page => ({path: '/set_password', query: {token: page.args.token}})
  },
  user_activation: {
    id: 'activation',
    caption: 'Activate user',
    needAuth: false,
    acceptUrl: (path, query) => path == '/activation' ? {args: {token: query.token}} : false,
    createUrl: page => ({path: '/activation', query: {token: page.args.token}})
  },
  // dashboard: {
  //   id: 'dasboard',
  //   caption: 'dashboard',
  //   needAuth: true,
  //   simpleRoute: '/'
  // },
  topology_list: {
    id: 'topology_list',
    caption: 'Topology list',
    needAuth: true,
    acceptUrl: path => path == '/topologies' || path == '/',
    createUrl: page => '/'
  },
  topology_list_all: {
    id: 'topology_list_all',
    caption: 'Topology list',
    needAuth: true,
    simpleRoute: '/topologies/all'
  },
  topology_schema: {
    id: 'topology_scheme',
    caption: 'Topology schema',
    needAuth: true,
    acceptUrl: path => {
      const match = /\/topology\/(\w+)\/schema/g.exec(path);
      return match && match[1] ? {args: {schemaId: match[1]}} : false;
    },
    createUrl: page => `/topology/${page.args.schemaId}/schema`
  },
  topology_detail: {
    id: 'topology_detail',
    caption: 'Topology detail',
    needAuth: true,
    acceptUrl: (path, query) => {
      const match = /\/topology\/(\w+)\/detail/g.exec(path);
      return match && match[1] ? {args: {topologyId: match[1], activeTab: query.active_tab}} : false;
    },
    createUrl: page => ({path: `/topology/${page.args.topologyId}/detail`, query: {active_tab: page.args.activeTab}})
  },
  authorization_list: {
    id: 'authorization_list',
    caption: 'Authorization list',
    needAuth: true,
    simpleRoute: '/authorizations'
  },
  notification_settings: {
    id: 'notification_settings',
    caption: 'Notification setting',
    needAuth: true,
    simpleRoute: '/notification_settings'
  },
}