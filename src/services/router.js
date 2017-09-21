import * as qs from 'query-string'

import config from 'rootApp/config';
import * as applicationActions from 'actions/applicationActions';
import objectEquals from 'utils/objectEquals';

var unsubscribe = null;
var prevSelectedPage = null;
var historyIndex = 0;
var routerAction = false;

function processUrl(store, path, query){
  let pageKey = '';
  let pageData = null;
  for (const key in config.pages) {
    if (config.pages.hasOwnProperty(key)) {
      let test = config.pages[key].acceptUrl || config.pages[key].simpleRoute;
      if (typeof test == 'string') {
        if (test == path) {
          pageKey = key;
          break;
        }
      } else if (typeof test == 'function') {
        const result = test(path, query);
        if (result) {
          pageKey = key;
          pageData = result;
          break;
        }
      }
    }
  }

  try {
    routerAction = true;
    store.dispatch(applicationActions.selectPage(
      pageKey,
      pageData && pageData.hasOwnProperty('args') ? pageData.args : null,
      pageData && pageData.hasOwnProperty('data') ? pageData.data : null
    ));
  } finally {
    routerAction = false;
  }
}

function refreshUrlHistory(selectedPage){
  if (!routerAction) {
    if (selectedPage && selectedPage.key && config.pages[selectedPage.key]) {
      const page = config.pages[selectedPage.key];
      let url = null;
      let create = page.createUrl || page.simpleRoute;
      if (typeof create == 'string') {
        url = create;
      } else if (typeof create == 'function') {
        url = create(selectedPage);
      }

      if (url) {
        let title = page.caption;
        if (typeof url == 'object') {
          url = url.path + '?' + qs.stringify(url.query);
          title = url.title || title;
        }
        if (typeof url != 'string') {
          throw new Error('router.refreshUrlHistory: Url must be string.');
        }
        historyIndex++;
        history.pushState(historyIndex, title, url);
      }
    }
  }
}

export function init (store){
  const path = window.location.pathname;
  processUrl(store, path, qs.parse(window.location.search));

  prevSelectedPage = store.getState().application.selectedPage;

  unsubscribe = store.subscribe(() => {
    const state = store.getState();
    const selectedPage = state.application.selectedPage;
    if (!objectEquals(selectedPage, prevSelectedPage)){
      refreshUrlHistory(selectedPage);
      prevSelectedPage = selectedPage;
    }
  });

  window.onpopstate = e => {
    historyIndex = e.state || 0;
    processUrl(store, window.location.pathname, qs.parse(window.location.search));
  }
}
