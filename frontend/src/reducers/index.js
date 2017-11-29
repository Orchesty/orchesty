import {combineReducers} from 'redux';

import {reducer as form} from 'redux-form'

import application from './application';
import notification from './notification';
import topology from './topology';
import topologyGroup from './topologyGroup';
import node from './node';
import category from './category';
import authorization from './authorization';
import auth from './auth';
import process from './process';
import server from './server';

const rootReducer = combineReducers({
  application,
  auth,
  notification,
  topology,
  topologyGroup,
  node,
  category,
  authorization,
  process,
  server,
  form
});

export default rootReducer;

// export default (state, action) => {
//   if (action.type == 'SET_STATE'){
//     return action.state;
//   } else {
//     return rootReducer(state, action);
//   }
// }