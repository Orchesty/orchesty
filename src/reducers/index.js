import {combineReducers} from 'redux';

import {reducer as form} from 'redux-form'

import application from './application';
import notification from './notification';
import topology from './topology';
import node from './node';

const rootReducer = combineReducers({
  application,
  notification,
  topology,
  node,
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