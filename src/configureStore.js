import { createStore, applyMiddleware } from 'redux';
import thunkMiddleware from 'redux-thunk';
import * as storage from 'redux-storage'
import storageFilter from 'redux-storage-decorator-filter';
import createEngine from 'redux-storage-engine-localstorage';

import * as router from 'services/router';
import * as apiGatewayServer from 'services/apiGatewayServer';

import rootReducer from 'reducers/index';
import * as types from './actionTypes';

export default function (initialState, composeWithDevTools) {
  const reducer = storage.reducer(rootReducer);
  const engine = createEngine('pipes');
  const decoratedEngine = storageFilter(engine, [
    ['auth', 'user'],
    ['server', 'apiGateway'],
    ['application', 'showSideBar'],
    ['application', 'pages']
  ]);
  const storageMiddleware = storage.createMiddleware(decoratedEngine, [], [
    types.USER_LOGGED,
    types.USER_LOGOUT,
    types.SERVER_API_GATEWAY_CHANGE,
    types.LEFT_SIDEBAR_TOGGLE,
    types.OPEN_PAGE,
    types.CLOSE_PAGE
  ]);

  const middlewares = [thunkMiddleware, storageMiddleware];
  let appliedMiddlewares = applyMiddleware(...middlewares);
  if (composeWithDevTools){
    appliedMiddlewares = composeWithDevTools(appliedMiddlewares);
  }

  const createStoreWithMiddleware = appliedMiddlewares(createStore);

  const store = createStoreWithMiddleware(reducer, initialState);

  apiGatewayServer.init(store);

  return new Promise((resolve, reject) => {
    storage.createLoader(engine)(store).then(
      () => {
        router.init(store);
        resolve(store)
      }
    ).catch(
      () => {reject('Loading stored data failed.')}
    );
  });
  
}