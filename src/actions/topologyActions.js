import * as types from 'rootApp/actionTypes';
import listFactory from './factories/listFactory';
import serverRequest, {sortToQuery, rawRequest, rawRequestJSONReceive} from 'services/apiGatewayServer';
import objectEquals from 'utils/objectEquals';

import processes from 'enums/processes';
import config from 'rootApp/config';
import sortCompare from 'utils/sortCompare';
import * as notificationActions from './notificationActions';
import * as processActions from './processActions';
import * as nodeActions from './nodeActions';
import * as applicationActions from './applicationActions';
import * as topologyGroupActions from './topologyGroupActions';
import {listType, stateType} from 'rootApp/types';
import filterCallback from 'rootApp/utils/filterCallback';
import nestedValue from 'rootApp/utils/nestedValue';

const {createPaginationList, createCompleteList, listLoading, listError, listReceive, listDelete, listChangeSort, listChangePage, listChangeFilter, invalidateLists} = listFactory('TOPOLOGY/LIST/');

export const topologyInvalidateLists = invalidateLists;

function receive(data){
  return {
    type: types.TOPOLOGY_RECEIVE,
    data
  }
}

function receiveItems(items){
  return {
    type: types.TOPOLOGY_RECEIVE_ITEMS,
    items
  }
}

function remove(id) {
  return {
    type: types.TOPOLOGY_REMOVE,
    id
  }
}

function receiveSchema(id, data){
  return {
    type: types.TOPOLOGY_RECEIVE_SCHEMA,
    id,
    data
  }
}

function receiveTest(data){
  return {
    type: types.TOPOLOGY_RECEIVE_TEST,
    data
  }
}

function resetTest(id, nodes){
  return {
    type: types.TOPOLOGY_RESET_TEST,
    id,
    nodes
  }
}

function loadList(id, loadingState = true){
  return (dispatch, getState) => {
    if (loadingState) {
      dispatch(listLoading(id));
    }
    const list = getState().topology.lists[id];
    let query = null;
    if (!list.local){
      query = sortToQuery(
        list.sort,
        list.type = listType.PAGINATION ? {
          offset: list.page ? list.page * list.pageSize : 0,
          limit: list.pageSize
        } : {}
      );
    }
    let promise =  serverRequest(dispatch, 'GET', '/topologies', query).then(response => {
      if (response){
        dispatch(receiveItems(response.items));
        dispatch(topologyGroupActions.recalculateAllTopologyGroups());
      }
      return response;
    });
    if (list.local){
      promise = promise.then(response => response ? prepareLocalList(list, response.items, getState) : response);
    }
    return promise.then(response => {
      dispatch(response ? listReceive(id, response) : listError(id));
      return response;
    });
  }
}

function createGetStoredValue(getState){
  const state = getState();
  return key => nestedValue(state, key);
}

function prepareLocalList(list, elements, getState){
  const elementArray = Object.values(elements);
  let res = {
    items: elementArray
  };
  if (list.filter){
    const getStoredValue = createGetStoredValue(getState);
    Object.keys(list.filter).forEach(key => {
      const filterItem = list.filter[key];
      if (filterItem !== undefined && filterItem !== null){
        res.items = res.items.filter(filterCallback(filterItem, getStoredValue));
      }
    });
  }
  res.total = res.items.length;
  if (list.sort){
    res.items.sort(sortCompare(list.sort));
  }
  if (list.type == listType.PAGINATION){
    res.offset = list.page ? list.page * list.pageSize : 0;
    res.limit = list.pageSize;
    res.items = res.items.slice(res.offset, res.limit ? res.offset + res.limit : undefined);
  }
  res.count = res.items.length;
  return res;
}

export function refreshList(listId, loadingState = true){
  return (dispatch, getState) => {
    const list = getState().topology.lists[listId];
    if (list.local){
      dispatch(listReceive(listId, prepareLocalList(list, getState().topology.elements, getState)));
    } else {
      return dispatch(loadList(listId, loadingState));
    }
  }
}

export function needTopologyList(listId, filter) {
  return (dispatch, getState) => {
    const list = getState().topology.lists[listId];
    if (!list) {
      const create = config.params.preferPaging ?
        local => createPaginationList(listId, config.params.defaultPageSize, local, null, filter) : local => createCompleteList(listId, local, null, filter);
      dispatch(create(true));
    }
    if (!list || list.state == stateType.NOT_LOADED || list.state == stateType.ERROR) {
      return dispatch(loadList(listId));
    } else {
      return Promise.resolve(true);
    }
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
      return dispatch(refreshList(topologyListId, false));
    }
    else {
      return Promise.resolve(true);
    }
  }
}

export function topologyListChangePage(topologyListId, page) {
  return (dispatch, getState) => {
    const list = getState().topology.lists[topologyListId];
    if (list.type == listType.PAGINATION){
      if (!objectEquals(list.page, page)){
        dispatch(listChangePage(topologyListId, page));
        return dispatch(refreshList(topologyListId));
      } else {
        return Promise.resolve(true);
      }
    } else {
      return Promise.reject('Change page failed! List is not pagination.');
    }
  }
}

export function topologyListChangeFilter(topologyListId, filter) {
  return (dispatch, getState) => {
    const list = getState().topology.lists[topologyListId];
    if (!objectEquals(list.filter, filter)){
      dispatch(listChangeFilter(topologyListId, filter));
      return dispatch(refreshList(topologyListId));
    } else {
      return Promise.resolve(true);
    }
  }
}

export function needTopology(id, force = false){
  return (dispatch, getState) => {
    const topology = getState().topology.elements[id];
    if (!topology || force){
      dispatch(processActions.startProcess(processes.topologyLoad(id)));
      return serverRequest(dispatch, 'GET', `/topologies/${id}`).then(
        response => {
          if (response) {
            const oldTopology = getState().topology.elements[id];
            dispatch(receive(response));
            dispatch(topologyGroupActions.recalculateTopologyGroup(response.name, oldTopology));
          }
          dispatch(processActions.finishProcess(processes.topologyLoad(id), response));
          return response;
        }
      );
    } else {
      dispatch(processActions.finishProcess(processes.topologyLoad(id), true));
      return Promise.resolve(topology);
    }
  }
}

export function topologyUpdate(id, data){
  return (dispatch, getState) => {
    dispatch(processActions.startProcess(processes.topologyUpdate(id)));
    return serverRequest(dispatch, 'PATCH', `/topologies/${id}`, null, data).then(
      response => {
        if (response) {
          const oldTopology = getState().topology.elements[id];
          dispatch(receive(response));
          dispatch(topologyGroupActions.recalculateTopologyGroup(response.name, oldTopology));
        }
        dispatch(processActions.finishProcess(processes.topologyUpdate(id), response));
        return response;
      }
    )
  }
}

export function topologyCreate(data, processHash = 'new'){
  return dispatch => {
    dispatch(processActions.startProcess(processes.topologyCreate(processHash)));
    return serverRequest(dispatch, 'POST', `/topologies`, null, data).then(
      response => {
        if (response){
          dispatch(receive(response));
          dispatch(topologyGroupActions.recalculateTopologyGroup(response.name));
          dispatch(invalidateLists());
        }
        dispatch(processActions.finishProcess(processes.topologyCreate(processHash), response));
        return response;
      }
    )
  }
}

export function topologyDelete(id, redirectToList = false){
  return dispatch => {
    dispatch(processActions.startProcess(processes.topologyDelete(id)));
    return serverRequest(dispatch, 'DELETE', `/topologies/${id}`).then(
      response => {
        if (response) {
          dispatch(invalidateLists());
          if (redirectToList){
            dispatch(applicationActions.selectPage('topology_list'));
          }
          dispatch(remove(id));
        }
        dispatch(processActions.finishProcess(processes.topologyDelete(id), response));
        return response;
      }
    )
  }
}

export function cloneTopology(id, silent = false){
  return dispatch => {
    dispatch(processActions.startProcess(processes.topologyClone(id)));
    return serverRequest(dispatch, 'POST', `/topologies/${id}/clone`).then(
      response => {
        if (response){
          if (!silent){
            dispatch(notificationActions.addSuccess('Topology was cloned successfully.'));
          }
          dispatch(receive(response));
          dispatch(topologyGroupActions.recalculateTopologyGroup(response.name));
          dispatch(invalidateLists());
        }
        dispatch(processActions.finishProcess(processes.topologyClone(id), response));
        return response;
      }
    )
  }
}

export function publishTopology(id, silent = false){
  return (dispatch, getState) => {
    dispatch(processActions.startProcess(processes.topologyPublish(id)));
    return serverRequest(dispatch, 'POST', `/topologies/${id}/publish`).then(
      response => {
        if (response){
          if (!silent){
            dispatch(notificationActions.addSuccess('Topology was published successfully.'));
          }
          const oldTopology = getState().topology.elements[id];
          dispatch(receive(response));
          dispatch(topologyGroupActions.recalculateTopologyGroup(response.name, oldTopology));
        }
        dispatch(processActions.finishProcess(processes.topologyPublish(id), response));
        return response;
      }
    )
  }
}

export function loadTopologySchema(id, force = false){
  return (dispatch, getState) => {
    if (force || !getState().topology.schemas[id]){
      return rawRequest(dispatch, 'GET', `/topologies/${id}/schema.bpmn`).then( response => {
        if (response !== undefined){
          dispatch(receiveSchema(id, response === true ? null : response));
        }
        return response;
      })
    } else {
      return Promise.resolve(true);
    }
  }
}

export function saveTopologySchema(id, schema, silent = false){
  return dispatch => {
    dispatch(processActions.startProcess(processes.topologySaveScheme(id)));
    return rawRequestJSONReceive(dispatch, 'PUT', `/topologies/${id}/schema.bpmn`, null, {
      headers: {
        'Content-Type': 'application/xml'
      },
      body: schema
    }).then(response => {
      if (response) {
        if (!silent){
          dispatch(notificationActions.addNotification('success', 'Schema was saved successfully.'));
        }
        dispatch(receiveSchema(response._id, schema));
        dispatch(receive(response));
        dispatch(topologyGroupActions.recalculateTopologyGroup(response.name));
        if (response._id != id){
          dispatch(invalidateLists());
          if (!silent) {
            dispatch(notificationActions.addNotification('warning', 'New topology was created.'));
          }
        }
        dispatch(nodeActions.nodeInvalidateLists('topology', response._id));
      }
      dispatch(processActions.finishProcess(processes.topologySaveScheme(id), response));
      return response;
    });
  }
}

export function testTopology(id, silent = false){
  return (dispatch, getState) => {
    const tests = getState().topology.tests;
    if (tests[id]){
      dispatch(resetTest(id, tests[id].nodes));
    }
    dispatch(processActions.startProcess(processes.topologyTest(id)));
    return serverRequest(dispatch, 'GET', `/topologies/${id}/test`).then(response => {
      if (response){
        dispatch(receiveTest(response));
      }
      dispatch(processActions.finishProcess(processes.topologyTest(id), response));
      return response;
    })
  }
}