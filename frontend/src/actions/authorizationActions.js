import * as types from '../actionTypes';
import listFactory from './factories/listFactory';
import {stateType} from '../types';
import serverRequest ,{sortToQuery, makeUrl} from '../services/apiGatewayServer';
import * as processActions from './processActions';
import * as notificationActions from './notificationActions';

import params from '../config/params';
import objectEquals from '../utils/objectEquals';

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
    const offset = list.page ? (list.page - 1) * list.pageSize : 0;
    return serverRequest(dispatch, 'GET', '/authorizations', sortToQuery(list.sort, {
      offset,
      limit: list.pageSize
    })).then(response => {
      dispatch(response ? listReceive(id, response) : listError(id));
      return response;
    })
  }
}

function loadSettings(authorizationId, processId){
  return dispatch => {
    processId && dispatch(processActions.startProcess(processId));
    return serverRequest(dispatch, 'GET', `/authorizations/${authorizationId}/settings`).then(response => {
      processId && dispatch(processActions.finishProcess(processId, response));
      if (response){
        dispatch(receiveSettings(authorizationId, response));
      }

      return response;
    });
  }
}

export function needAuthorizationList(listId, pageSize = params.defaultPageSize) {
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

export function needAuthorization(id, force = false, processId){
  return (dispatch, getState) => {
    const authorization = getState().authorization.elements[id];
    if (!authorization || force){
      processId && dispatch(processActions.startProcess(processId));
      return serverRequest(dispatch, 'GET', `/authorizations/${id}`).then(
        response => {
          processId && dispatch(processActions.finishProcess(processId, response));
          if (response) {
            dispatch(receive(response));
          }
          return response;
        }
      );
    } else {
      return Promise.resolve(node);
    }
  }
}

export function needSettings(authorizationId, forced = false, processId){
  return (dispatch, getState) => {
    if (forced || !getState().authorization.settings[authorizationId]){
      return dispatch(loadSettings(authorizationId, processId));
    } else {
      return Promise.resolve(true);
    }
  }
}

export function saveSettings(authorizationId, data, processId, silent = false){
  return dispatch => {
    processId && dispatch(processActions.startProcess(processId));
    return serverRequest(dispatch, 'PUT', `/authorizations/${authorizationId}/settings`, null, data).then(response => {
      if (response){
        processId && dispatch(processActions.finishProcess(processId, response));
        if (!silent){
          dispatch(notificationActions.addSuccess('Authorization setting was saved'));
        }
        dispatch(receiveSettings(authorizationId, response));
        dispatch(needAuthorization(authorizationId, true));
      }
      return response;
    });
  }
}

export function authorize(authorizationId, processId){
  return dispatch => {
    processId && dispatch(processActions.startProcess(processId));
    const win = window.open(makeUrl(`/authorizations/${authorizationId}/authorize`), '_blank');
    win.focus();

    return new Promise((resolve) => {
      var intervalId = null;
      intervalId = setInterval(() => {
        if (win.closed) {
          clearInterval(intervalId);
          dispatch(needAuthorization(authorizationId, true)).then(response => {
            processId && dispatch(processActions.finishProcess(processId, response && response.is_authorized));
            return response;
          })
            .then(resolve);
        }
      }, 333);
    });
  }
}