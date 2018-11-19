import * as qs from 'query-string';

import config from 'rootApp/config';
import * as applicationActions from 'actions/applicationActions';
import objectEquals from 'utils/objectEquals';

let unsubscribe = null;
let prevSelectedPage = null;
let historyIndex = 0;
let routerAction = false;

function processUrl(store, path, query) {
  let pageKey = '';
  let pageData = null;
  for (const key in config.pages) {
    if (config.pages.hasOwnProperty(key)) {
      const test = config.pages[key].acceptUrl || config.pages[key].simpleRoute;
      if (typeof test === 'string') {
        if (test === path) {
          pageKey = key;
          break;
        }
      } else if (typeof test === 'function') {
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
    store.dispatch(applicationActions.openPage(
      pageKey,
      pageData && pageData.hasOwnProperty('args') ? pageData.args : null,
    ));
  } finally {
    routerAction = false;
  }
}

function refreshUrlHistory(selectedPage) {
  if (!routerAction) {
    if (selectedPage && selectedPage.key && config.pages[selectedPage.key]) {
      const page = config.pages[selectedPage.key];
      let url = null;
      const create = page.createUrl || page.simpleRoute;
      if (typeof create === 'string') {
        url = create;
      } else if (typeof create === 'function') {
        url = create(selectedPage);
      }

      if (url) {
        let title = page.caption;
        if (typeof url === 'object') {
          url = `${url.path}?${qs.stringify(url.query)}`;
          title = url.title || title;
        }
        if (typeof url !== 'string') {
          throw new Error('router.refreshUrlHistory: Url must be string.');
        }
        if (config.params.urlPrefix) {
          url = config.params.urlPrefix + url;
        }
        historyIndex++;
        history.pushState(historyIndex, title, url);
      }
    }
  }
}

function getClearUrl(rawUrl) {
  if (config.params.urlPrefix) {
    if (rawUrl.startsWith(config.params.urlPrefix)) {
      return rawUrl.substring(config.params.urlPrefix.length) || '/';
    }
    return null;
  }
  return rawUrl;
}

export function init(store) {
  const path = getClearUrl(window.location.pathname);

  processUrl(store, path, qs.parse(window.location.search));

  const application = store.getState().application;
  prevSelectedPage = application.pages[application.selectedPage];

  unsubscribe = store.subscribe(() => {
    const state = store.getState();
    const selectedPage = state.application.pages[state.application.selectedPage];
    if (!objectEquals(selectedPage, prevSelectedPage)) {
      refreshUrlHistory(selectedPage);
      prevSelectedPage = selectedPage;
    }
  });

  window.onpopstate = (e) => {
    historyIndex = e.state || 0;
    processUrl(store, getClearUrl(window.location.pathname), qs.parse(window.location.search));
  };
}
