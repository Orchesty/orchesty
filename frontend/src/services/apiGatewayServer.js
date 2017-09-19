import config from '../config/params';

import * as notificationActions from '../actions/notificationActions';

function check(dispatch, response) {
  if (response.ok){
    return response
  } else {
    response.json()
      .then(errorData => {
        dispatch(notificationActions.addNotification('error', `Error in server request: ${errorData.error_code} - ${errorData.message}`));
      })
      .catch(parserError => {
        dispatch(notificationActions.addNotification('error', `Error in server request: ${response.status} - ${response.statusText}`));
      });
  }
}

export function sortToQuery(sort, queries = {}){
  if (sort && sort.key){
    queries['order_by'] = sort.key + (typeof sort.type == 'string' && sort.type.toLowerCase() == 'desc' ? '-' : '+')
  }
  return queries;
}

export function makeUrl(relUrl, queries){
  let queryUrl = '';
  if (queries) {
    queryUrl = '?' + Object.keys(queries)
        .map(k => encodeURIComponent(k) + '=' + encodeURIComponent(queries[k]))
        .join('&');
  }
  
  return config.apiUrl + relUrl + queryUrl;
}

export function rawRequest(dispatch, method, relUrl, queries, options){
  options = Object.assign({credentials: 'same-origin'}, options);
  return fetch(makeUrl(relUrl, queries), Object.assign({method}, options))
    .then(check.bind(null, dispatch))
    .then(response => response ? response.text() : undefined)
    .catch(error => {
      dispatch(notificationActions.addNotification('error', `Error in server request: ${error}`));
      return undefined;
    });
}

export function rawRequestJSONReceive(dispatch, method, relUrl, queries, options){
  const opt = Object.assign({method, credentials: 'same-origin'}, options);
  opt.headers = Object.assign({Accept: 'application/json'}, opt.headers);
  
  return fetch(makeUrl(relUrl, queries), opt)
    .then(check.bind(null, dispatch))
    .then(response => response ? response.json() : undefined)
    .catch(error => {
      dispatch(notificationActions.addNotification('error', `Error in server request: ${error}`));
      return undefined;
    });
}

export default (dispatch, method, relUrl, queries, data) => {
  let headers = {
    Accept: 'application/json'
  };
  let options = {
    method: method,
    headers: headers,
    credentials: 'same-origin'
  };
  if (data) {
    headers['Content-Type'] = 'application/json';
    options['body'] = JSON.stringify(data);
  }

  return fetch(makeUrl(relUrl, queries), options)
    .then(check.bind(null, dispatch))
    .then(response => response ? response.text() : undefined)
    .then(textResponse => textResponse === undefined ? textResponse : (textResponse ? JSON.parse(textResponse) : true))
    .catch(error => {
      dispatch(notificationActions.addNotification('error', `Error in server request: ${error}`));
      return undefined;
    });
}
