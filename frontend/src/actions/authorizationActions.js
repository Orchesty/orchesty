import * as types from 'rootApp/actionTypes';
import listFactory from './factories/listFactory';
import serverRequest ,{sortToQuery, makeUrl} from 'services/apiGatewayServer';
import processes from 'enums/processes';
import * as processActions from './processActions';
import * as notificationActions from './notificationActions';

import config from 'rootApp/config';
import objectEquals from 'utils/objectEquals';

const {createPaginationList, listLoading, listError, listReceive, listDelete, listChangePage} = listFactory('AUTHORIZATION/LIST/');

function receiveSettings(id, data){
  return {
    type: types.AUTHORIZATION_RECEIVE_SETTINGS,
    id,
    data
  }
}

function receive(data){
  return {
    type: types.AUTHORIZATION_RECEIVE,
    data
  }
}

function loadList(id, loadingState = true){
  return (dispatch, getState) => {
    if (loadingState) {
      dispatch(listLoading(id));
    }
    const list = getState().authorization.lists[id];
    const offset = list.page ? list.page * list.pageSize : 0;
    return serverRequest(dispatch, 'GET', '/authorizations', sortToQuery(list.sort, {
      offset,
      limit: list.pageSize
    })).then(response => {
      dispatch(response ? listReceive(id, response) : listError(id));
      return response;
    })
  }
}

function loadSettings(id){
  return dispatch => {
    dispatch(processActions.startProcess(processes.authorizationLoadSettings(id)));
    return serverRequest(dispatch, 'GET', `/authorizations/${id}/settings`).then(response => {
      if (response){
        dispatch(receiveSettings(id, response));
      }
      dispatch(processActions.finishProcess(processes.authorizationLoadSettings(id), response));

      return response;
    });
  }
}

export function needAuthorizationList(listId, pageSize = config.params.defaultPageSize) {
  return (dispatch, getState) => {
    const list = getState().authorization.lists[listId];
    if (!list) {
      dispatch(createPaginationList(listId, pageSize));
    }
    return dispatch(loadList(listId));
  }
}

export function authorizationsListChangePage(listId, page) {
  return (dispatch, getState) => {
    const oldPage = getState().authorization.lists[listId].page;
    if (!objectEquals(oldPage, page)){
      dispatch(listChangePage(listId, page));
      dispatch(loadList(listId));
    }
  }
}

export function needAuthorization(id, force = false){
  return (dispatch, getState) => {
    const authorization = getState().authorization.elements[id];
    if (!authorization || force){
      dispatch(processActions.startProcess(processes.authorizationLoad(id)));
      return serverRequest(dispatch, 'GET', `/authorizations/${id}`).then(
        response => {
          if (response) {
            dispatch(receive(response));
          }
          dispatch(processActions.finishProcess(processes.authorizationLoad(id), response));
          return response;
        }
      );
    } else {
      return Promise.resolve(node);
    }
  }
}

export function needSettings(id, forced = false){
  return (dispatch, getState) => {
    if (forced || !getState().authorization.settings[id]){
      return dispatch(loadSettings(id));
    } else {
      return Promise.resolve(true);
    }
  }
}

export function saveSettings(id, data, silent = false){
  return dispatch => {
    dispatch(processActions.startProcess(processes.authorizationSaveSettings(id)));
    return serverRequest(dispatch, 'PUT', `/authorizations/${id}/settings`, null, data).then(response => {
      if (response){
        if (!silent){
          dispatch(notificationActions.addSuccess('Authorization setting was saved'));
        }
        dispatch(receiveSettings(id, response));
        dispatch(needAuthorization(id, true));
        dispatch(processActions.finishProcess(processes.authorizationSaveSettings(id), response));
      }
      return response;
    });
  }
}

export function authorize(id){
  return dispatch => {
    dispatch(processActions.startProcess(processes.authorizationAuthorize(id)));
    const win = window.open(makeUrl(`/authorizations/${id}/authorize`), '_blank');
    win.focus();

    return new Promise((resolve) => {
      var intervalId = null;
      intervalId = setInterval(() => {
        if (win.closed) {
          clearInterval(intervalId);
          dispatch(needAuthorization(id, true)).then(response => {
            dispatch(processActions.finishProcess(processes.authorizationAuthorize(id), response && response.is_authorized));
            return response;
          })
            .then(resolve);
        }
      }, 333);
    });
  }
}