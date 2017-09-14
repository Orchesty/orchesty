export default {
  login: {
    id: 'login',
    caption: 'Login',
    needAuth: false,
    simpleRoute: '/login',
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
    acceptUrl: url => {
      const match = /\/topology\/(\w+)\/schema/g.exec(url);
      return match && match[1] ? {args: {schemaId: match[1]}} : false;
    },
    createUrl: page => `/topology/${page.args.schemaId}/schema`
  }
}