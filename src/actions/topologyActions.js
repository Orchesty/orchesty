import * as types from 'rootApp/actionTypes';
import listFactory from './factories/listFactory';
import serverRequest, {sortToQuery, rawRequest, rawRequestJSONReceive} from 'services/apiGatewayServer';
import objectEquals from 'utils/objectEquals';

import processes from 'enums/processes';
import config from 'rootApp/config';
import * as notificationActions from './notificationActions';
import * as processActions from './processActions';
import * as nodeActions from './nodeActions';

const {createPaginationList, listLoading, listError, listReceive, listDelete, listChangeSort, listChangePage, invalidateLists} = listFactory('TOPOLOGY/LIST/');

export const topologyInvalidateLists = invalidateLists;

function receive(data){
  return {
    type: types.TOPOLOGY_RECEIVE,
    data
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
    const offset = list.page ? list.page * list.pageSize : 0;
    return serverRequest(dispatch, 'GET', '/topologies', sortToQuery(list.sort, {
      offset,
      limit: list.pageSize
    })).then(response => {
      dispatch(response ? listReceive(id, response) : listError(id));
      return response;
    })
  }
}

export function needTopologyList(listId, pageSize = config.params.defaultPageSize) {
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

export function needTopology(id, force = false){
  return (dispatch, getState) => {
    const topology = getState().topology.elements[id];
    if (!topology || force){
      dispatch(processActions.startProcess(processes.topologyLoad(id)));
      return serverRequest(dispatch, 'GET', `/topologies/${id}`).then(
        response => {
          if (response) {
            dispatch(receive(response));
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
  return dispatch => {
    dispatch(processActions.startProcess(processes.topologyUpdate(id)));
    return serverRequest(dispatch, 'PATCH', `/topologies/${id}`, null, data).then(
      response => {
        if (response) {
          dispatch(receive(response));
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
          dispatch(invalidateLists());
        }
        dispatch(processActions.finishProcess(processes.topologyCreate(processHash), response));
        return response;
      }
    )
  }
}

export function topologyDelete(id){
  return dispatch => {
    dispatch(processActions.startProcess(processes.topologyDelete(id)));
    return serverRequest(dispatch, 'DELETE', `/topologies/${id}`).then(
      response => {
        if (response) {
          dispatch(invalidateLists());
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
          dispatch(invalidateLists());
        }
        dispatch(processActions.finishProcess(processes.topologyClone(id), response));
        return response;
      }
    )
  }
}

export function publishTopology(id, silent = false){
  return dispatch => {
    dispatch(processActions.startProcess(processes.topologyPublish(id)));
    return serverRequest(dispatch, 'POST', `/topologies/${id}/publish`).then(
      response => {
        if (response){
          if (!silent){
            dispatch(notificationActions.addSuccess('Topology was published successfully.'));
          }
          dispatch(receive(response));
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