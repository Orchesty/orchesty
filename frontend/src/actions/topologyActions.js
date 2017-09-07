import * as types from '../actionTypes';
import listFactory from './factories/listFactory';
import serverRequest, {sortToQuery, rawRequest, rawRequestJSONReceive} from '../middleware/apiGatewayServer';
import {listType} from '../types';
import objectEquals from '../utils/objectEquals';

import params from '../config/params';

const {createPaginationList, listLoading, listError, listReceive, listDelete, listChangeSort, listChangePage} = listFactory('TOPOLOGY/LIST/');

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

function loadList(id, loadingState = true){
  return (dispatch, getState) => {
    if (loadingState) {
      dispatch(listLoading(id));
    }
    const list = getState().topology.lists[id];
    const offset = list.page ? (list.page - 1) * list.pageSize : 0;
    return serverRequest(dispatch, 'GET', '/topologies', sortToQuery(list.sort, {
      offset,
      limit: list.pageSize
    })).then(response => {
      dispatch(response ? listReceive(id, response) : listError(id));
      return response;
    })
  }
}

export function openTopologyList(listId, pageSize = params.defaultPageSize) {
  return (dispatch, getState) => {
    const list = getState().topology.lists[listId];
    if (!list) {
      dispatch(createPaginationList(listId, pageSize));
    }
    return dispatch(loadList(listId));
  }
}

export function closeTopologyList(topologyListId) {
  return (dispatch) => {
    dispatch(listDelete(topologyListId));
  }
}

export function topologyListChangeSort(topologyListId, sort) {
  return (dispatch, getState) => {
    const oldSort = getState().topology.lists[topologyListId].sort;
    if (!objectEquals(oldSort, sort)) {
      dispatch(listChangeSort(topologyListId, sort));
      return dispatch(loadList(topologyListId, false));
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
      dispatch(listChangePage(topologyListId, page));
      dispatch(loadList(topologyListId));
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
      return rawRequest(dispatch, 'GET', `/topologies/${id}/schema.bpmn`).then( response => {
        if (response){
          dispatch(receiveSchema(id, response));
        }
        return response;
      })
    } else {
      return Promise.resolve(true);
    }
  }
}

export function saveTopologySchema(id, schema){
  return dispatch => {
    return rawRequestJSONReceive(dispatch, 'PUT', `/topologies/${id}/schema.bpmn`, null, {
      headers: {
        'Content-Type': 'application/bpmn+xml'
      },
      body: schema
    }).then(response => {
        if (response) {
          dispatch(receiveSchema(response._id, schema));
          dispatch(receive(response));
        }
        return response;
    })
  }
}
