import React from 'react';
import ReactDOM from 'react-dom';
import { Provider } from 'react-redux';
import { AppContainer } from 'react-hot-loader';
import { composeWithDevTools } from 'redux-devtools-extension';

import App from './views/App.jsx';
import getApplication from './application';

import initialState from './initialState.json';

import configureStore from './configureStore';


configureStore(initialState, composeWithDevTools).then(store => {

  getApplication(store);

  window.store = store;


  const render = Component => {
    ReactDOM.render(
      <AppContainer>
        <Provider store={store}>
          <Component />
        </Provider>
      </AppContainer>,
      document.getElementById('app')
    );
  };

  render(App);
}).catch(err => {console.error(err)});


