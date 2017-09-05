import { createStore, applyMiddleware } from 'redux';
import thunkMiddleware from 'redux-thunk';
import { composeWithDevTools } from 'redux-devtools-extension';
import rootReducer from './reducers/index';

const createStoreWithMiddleware = composeWithDevTools(applyMiddleware(thunkMiddleware))(createStore);

export default function (initialState) {
  return createStoreWithMiddleware(rootReducer, initialState);
}