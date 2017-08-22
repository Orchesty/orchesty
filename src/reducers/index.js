import {combineReducers} from 'redux';

import application from './application';
import notification from './notification';
import topology from './topology';

const rootReducer = combineReducers({
  application,
  notification,
  topology
});

export default rootReducer;