import { combineReducers } from 'redux';

import { reducer as form } from 'redux-form';

import application from './application';
import notification from './notification';
import topology from './topology';
import topologyGroup from './topologyGroup';
import node from './node';
import category from './category';
import authorization from './authorization';
import humanTask from './humanTask';
import cronTask from './cronTasks';
import appStore from './appStore';
import auth from './auth';
import process from './process';
import server from './server';
import editable from './editable';
import metrics from './metrics';
import notificationSettings from './notificationSettings';
import generalSearch from './generalSearch';
import panel from './panel';
import log from './log';

const rootReducer = combineReducers({
  application,
  auth,
  notification,
  topology,
  topologyGroup,
  node,
  category,
  authorization,
  humanTask,
  cronTask,
  appStore,
  process,
  server,
  form,
  editable,
  metrics,
  notificationSettings,
  generalSearch,
  panel,
  log,
});

export default rootReducer;

// export default (state, action) => {
//   if (action.type == 'SET_STATE'){
//     return action.state;
//   } else {
//     return rootReducer(state, action);
//   }
// }
