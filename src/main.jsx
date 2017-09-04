import React from 'react';
import ReactDOM from 'react-dom';
import { Provider } from 'react-redux';

import App from './views/app.jsx';
import getApplication from './application';

import initialState from './initialState.json';

import configureStore from './configureStore';

var store = configureStore(initialState);

getApplication(store);

window.store = store;

ReactDOM.render(
  <Provider store={store}>
    <App />
  </Provider>,
  document.getElementById('app')
);

