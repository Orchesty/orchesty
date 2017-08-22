import * as types from '../action_types';
import serverRequest, {sortToQuery, rawRequest} from '../middleware/api_gateway_server';
import * as applicationActions from './application_actions';
import {listType} from '../types';
import objectEquals from '../utils/object_equals';

import params from '../config/params';

function createList(type, pageSize, sort){
  return {
    type: types.TOPOLOGY_LIST_CREATE,
    listType: type,
    pageSize,
    sort
  }
}

function loading(id){
  return {
    type: types.TOPOLOGY_LIST_LOAD,
    listId: id
  }
}

function receiveListData(id, response) {
  return {
    type: types.TOPOLOGY_LIST_RECEIVE,
    listId: id,
    data: response
  }
}

function listError(id) {
  return {
    type: types.TOPOLOGY_LIST_ERROR,
    listId: id
  }
}

function deleteList(id){
  return {
    type: types.TOPOLOGY_LIST_DELETE,
    listId: id
  }
}

function changeSort(id, sort){
  return {
    type: types.TOPOLOGY_LIST_CHANGE_SORT,
    listId: id,
    sort
  }
}

function changePage(id, page){
  return {
    type: types.TOPOLOGY_LIST_CHANGE_PAGE,
    listId: id,
    page
  }
}

function receive(data){
  return {
    type: types.TOPOLOGY_RECEIVE,
    data
  }
}

function receiveSchema(id, data){
  return {
    type: types.TOPOLOGY_RECEIVE_SCHEMA,
    id,
    data
  }
}

function load(id){
  return (dispatch, getState) => {
    dispatch(loading(id));
    const list = getState().topology.lists[id];
    const offset = list.page ? (list.page - 1) * list.pageSize : 0;
    return serverRequest(dispatch, 'GET', '/topologies', sortToQuery(list.sort, {
      offset,
      limit: list.pageSize
    })).then(response => {
      dispatch(response ? receiveListData(id, response) : listError(id));
      return response;
    })
  }
}

export function openTopologyList(topologyListId, pageSize = params.defaultPageSize) {
  return (dispatch, getState) => {
    if (!topologyListId) {
      dispatch(createList(listType.PAGINATION, pageSize));
      topologyListId = getState().topology.listNewId;
    }
    dispatch(applicationActions.setPageData({topologyListId}));
    return dispatch(load(topologyListId));
  }
}

export function closeTopologyList(topologyListId) {
  return (dispatch) => {
    dispatch(deleteList(topologyListId));
    dispatch(applicationActions.setPageData(null));
  }
}

export function topologyListChangeSort(topologyListId, sort) {
  return (dispatch, getState) => {
    const oldSort = getState().topology.lists[topologyListId].sort;
    if (!objectEquals(oldSort, sort)) {
      dispatch(changeSort(topologyListId, sort));
      return dispatch(load(topologyListId));
    }
    else {
      return Promise.resolve(true);
    }
  }
}

export function topologyListChangePage(topologyListId, page) {
  return (dispatch, getState) => {
    const oldPage = getState().topology.lists[topologyListId].page;
    if (!objectEquals(oldPage, page)){
      dispatch(changePage(topologyListId, page));
      dispatch(load(topologyListId));
    }
  }
}

export function topologyUpdate(id, data){
  return dispatch => {
    return serverRequest(dispatch, 'PATCH', `/topologies/${id}`, null, data).then(
      response => {
        if (response) {
          dispatch(receive(response));
        }
        return response;
      }
    )
  }
}

export function loadTopologySchema(id, force = false){
  return (dispatch, getState) => {
    if (force || !getState().topology.schemas[id]){
      return rawRequest(dispatch, 'GET', `/topologies/${id}/schema.bpmn`).then(
        response => {
          if (response){
            dispatch(receiveSchema(id, response));
          }
          return response;
        }
      )
    } else {
      return Promise.resolve(true);
    }
  }
}
