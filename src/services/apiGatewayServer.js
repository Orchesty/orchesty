import config from 'rootApp/config';
import * as notificationActions from 'actions/notificationActions';
import * as authActions from 'actions/authActions';

let unsubscribe = null;
let apiGatewayServer = null;
let apiGatewayServerKey = null;

function check(dispatch, response) {
  if (response.ok) {
    return response;
  }
  if (response.status === 401) {
    dispatch(authActions.afterLogout());
    dispatch(notificationActions.addNotification('error', 'You are not logged. Log in again.'));
  } else {
    response.json()
      .then((errorData) => {
        if (typeof errorData === 'object') {
          dispatch(notificationActions.addNotification('error', `Error in server request: ${errorData.error_code} - ${errorData.message}`));
        } else {
          dispatch(notificationActions.addNotification('error', `Error in server request: ${response.status} - ${response.statusText}`));
        }
      })
      .catch((parserError) => {
        dispatch(notificationActions.addNotification('error', `Error in server request: ${response.status} - ${response.statusText}`));
      });
  }
}

function refreshFromStore(store) {
  const state = store.getState();
  if (state.server.apiGateway !== apiGatewayServerKey) {
    apiGatewayServerKey = state.server.apiGateway;
    apiGatewayServer = config.servers.apiGateway.servers[apiGatewayServerKey];
  }
}

function getCredentialsOption() {
  return apiGatewayServer.noCredentials ? 'omit' : 'include';
}

export function init(store) {
  refreshFromStore(store);
  unsubscribe = store.subscribe(() => {
    refreshFromStore(store);
  });
}

export function sortToQuery(sort, queries = {}) {
  if (sort && sort.key) {
    queries.order_by = sort.key + (typeof sort.type === 'string' && sort.type.toLowerCase() === 'desc' ? '-' : '+');
  }
  return queries;
}

export function makeUrl(relUrl, queries) {
  let queryUrl = '';
  if (queries) {
    queryUrl = `?${Object.keys(queries)
      .map(k => `${encodeURIComponent(k)}=${encodeURIComponent(queries[k])}`)
      .join('&')}`;
  }

  return apiGatewayServer.url + relUrl + queryUrl;
}

export function rawRequest(dispatch, method, relUrl, queries, options) {
  options = Object.assign({ credentials: getCredentialsOption() }, options);
  return fetch(makeUrl(relUrl, queries), Object.assign({ method }, options))
    .then(check.bind(null, dispatch))
    .then(response => (response ? response.text() : undefined))
    .catch((error) => {
      dispatch(notificationActions.addNotification('error', `Error in server request: ${error}`));
      return undefined;
    });
}

export function rawRequestJSONReceive(dispatch, method, relUrl, queries, options) {
  const opt = Object.assign({ method, credentials: getCredentialsOption() }, options);
  opt.headers = Object.assign({ Accept: 'application/json' }, opt.headers);

  return fetch(makeUrl(relUrl, queries), opt)
    .then(check.bind(null, dispatch))
    .then(response => (response ? response.json() : undefined))
    .catch((error) => {
      dispatch(notificationActions.addNotification('error', `Error in server request: ${error}`));
      return undefined;
    });
}

export default (dispatch, method, relUrl, queries, data, headers = {}) => {
  headers.Accept = 'application/json';

  const options = {
    method,
    headers,
    credentials: getCredentialsOption(),
  };
  if (data) {
    headers['Content-Type'] = 'application/json';
    options.body = JSON.stringify(data);
  }

  return fetch(makeUrl(relUrl, queries), options)
    .then(check.bind(null, dispatch))
    .then(response => (response ? response.text() : undefined))
    .then(textResponse => (textResponse === undefined ? textResponse : (textResponse ? JSON.parse(textResponse) : true)))
    .catch((error) => {
      console.log(error);
      dispatch(notificationActions.addNotification('error', `Error in server request: ${error}`));
      return undefined;
    });
};
