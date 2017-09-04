import {combineReducers} from 'redux';

import application from './application';
import notification from './notification';
import topology from './topology';

const rootReducer = combineReducers({
  application,
  notification,
  topology
});

export default (state, action) => {
  if (action.type == 'SET_STATE'){
    return action.state;
  } else {
    return rootReducer(state, action);
  }
}