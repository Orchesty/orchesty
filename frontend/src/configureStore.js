import { createStore, applyMiddleware } from 'redux';
import thunkMiddleware from 'redux-thunk';
import * as storage from 'redux-storage'
import storageFilter from 'redux-storage-decorator-filter';
import { composeWithDevTools } from 'redux-devtools-extension';
import createEngine from 'redux-storage-engine-localstorage';

import * as router from './services/router';

import rootReducer from './reducers/index';
import * as types from './actionTypes';

export default function (initialState) {
  const reducer = storage.reducer(rootReducer);
  const engine = createEngine('pipes');
  const decoratedEngine = storageFilter(engine, [
    ['auth', 'user']
  ]);
  const storageMiddleware = storage.createMiddleware(decoratedEngine, [], [
    types.USER_LOGGED,
    types.USER_LOGOUT
  ]);

  const middlewares = [thunkMiddleware, storageMiddleware];

  const createStoreWithMiddleware = composeWithDevTools(applyMiddleware(...middlewares))(createStore);

  const store = createStoreWithMiddleware(reducer, initialState);

  router.init(store);

  return new Promise((resolve, reject) => {
    storage.createLoader(engine)(store).then(
      () => {resolve(store)}
    ).catch(
      () => {reject('Loading stored data failed.')}
    );
  });
  
}