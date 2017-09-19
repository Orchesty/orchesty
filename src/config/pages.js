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
  dashboard: {
    id: 'dasboard',
    caption: 'dashboard',
    needAuth: true,
    simpleRoute: '/'
  }, 
  topology_list: {
    id: 'topology_list',
    caption: 'Topology list',
    needAuth: true,
    simpleRoute: '/topologies'
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
  authorization_list: {
    id: 'authorization_list',
    caption: 'Authorization list',
    needAuth: true,
    simpleRoute: '/authorizations'
  },
}