import moment from 'moment';
import {intervalType} from 'rootApp/types';

export default {
  login: {
    key: 'login',
    caption: 'Login',
    needAuth: false,
    simpleRoute: '/login'
  },
  registration: {
    key: 'registration',
    caption: 'Registration',
    needAuth: false,
    simpleRoute: '/registration'
  },
  reset_password: {
    key: 'reset_password',
    caption: 'Reset password',
    needAuth: false,
    simpleRoute: '/reset_password'
  },
  set_password: {
    key: 'set_password',
    caption: 'Set new password',
    needAuth: false,
    acceptUrl: (path, query) => path == '/set_password' && query.token ? {args: {token: query.token}} : false,
    createUrl: page => ({path: '/set_password', query: {token: page.args.token}})
  },
  user_activation: {
    key: 'activation',
    caption: 'Activate user',
    needAuth: false,
    acceptUrl: (path, query) => path == '/activation' ? {args: {token: query.token}} : false,
    createUrl: page => ({path: '/activation', query: {token: page.args.token}})
  },
  topology_list_all: {
    key: 'topology_list_all',
    caption: 'Topology list',
    needAuth: true,
    acceptUrl: path => path == '/topologies' || path == '/',
    createUrl: page => '/'
  },
  topology_detail: {
    key: 'topology_detail',
    id: args => `topology_detail-${args.topologyId}`,
    caption: 'Topology detail',
    tab: true,
    defaultArgs: {
      activeTab: 'nodes',
      metricsRange: {
        since: moment().subtract(1, 'minutes').format(),
        till: moment().format(),
      },
      interval: intervalType.FOUR_WEEK.value
    },
    needAuth: true,
    acceptUrl: (path, query) => {
      const match = /\/topology\/(\w+)\/detail/g.exec(path);
      return match && match[1] ? {args: {topologyId: match[1], activeTab: query.active_tab}} : false;
    },
    createUrl: page => ({path: `/topology/${page.args.topologyId}/detail`, query: {active_tab: page.args.activeTab}})
  },
  authorization_list: {
    key: 'authorization_list',
    caption: 'Authorization list',
    needAuth: true,
    simpleRoute: '/authorizations'
  },
  notification_settings: {
    key: 'notification_settings',
    caption: 'Notification setting',
    needAuth: true,
    simpleRoute: '/notification_settings'
  },
}