import {combineReducers} from 'redux';

import {reducer as form} from 'redux-form'

import application from './application';
import notification from './notification';
import topology from './topology';
import node from './node';
import authorization from './authorization';
import auth from './auth';
import process from './process';

const rootReducer = combineReducers({
  application,
  auth,
  notification,
  topology,
  node,
  authorization,
  process,
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