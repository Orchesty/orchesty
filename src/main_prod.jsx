import React from 'react';
import ReactDOM from 'react-dom';
import { Provider } from 'react-redux';

import App from './views/App.jsx';
import getApplication from './application';

import configureStore from './configureStore';


configureStore({}).then(store => {
  getApplication(store);

  window.store = store;

  const render = Component => {
    ReactDOM.render(
      <Provider store={store}>
        <Component />
      </Provider>,
      document.getElementById('app')
    );
  };

  render(App);
});


