import * as types from 'rootApp/actionTypes';
import {stateType} from 'rootApp/types';
import serverRequest from 'services/apiGatewayServer';

function setTopologyState(topologyId, state){
  return {
    type: types.METRICS_TOPOLOGY_SET_STATE,
    id: topologyId,
    state
  }
}

function receiveItems(items){
  return {
    type: types.METRICS_RECEIVE_ITEMS,
    items
  }
}

function topologyReceive(topologyId, items){
  return {
    type: types.METRICS_TOPOLOGY_RECEIVE,
    id: topologyId,
    items
  }
}

function setMetricsState(nodeId, state){
  return {
    type: types.METRICS_SET_STATE,
    id: nodeId,
    state
  }
}

function receive(nodeId, data){
  return {
    type: types.METRICS_RECEIVE,
    id: nodeId,
    data
  }
}

function loadTopologyMetrics(topologyId){
  return dispatch => {
    dispatch(setTopologyState(topologyId, stateType.LOADING));
    return serverRequest(dispatch, 'GET', `/metrics/topology/${topologyId}`).then(response => {
      if (response){
        dispatch(receiveItems(response));
      }
      dispatch(response ? topologyReceive(topologyId, response) : setTopologyState(topologyId, stateType.ERROR));
      return response;
    });
  }
}

export function needTopologyMetrics(topologyId, force = false){
  return (dispatch, getState) => {
    const list = getState().metrics.topologies[topologyId];
    if (force || !list || list.state == stateType.NOT_LOADED || list.state == stateType.ERROR) {
      return dispatch(loadTopologyMetrics(topologyId));
    } else {
      return Promise.resolve(true);
    }
  }
}

function loadMetrics(topologyId, nodeId){
  return dispatch => {
    dispatch(setMetricsState(nodeId, stateType.LOADING));
    return serverRequest(dispatch, 'GET', `/metrics/topology/${topologyId}/node/${nodeId}`).then(response => {
      dispatch(response ? receive(nodeId, response) : setMetricsState(nodeId, stateType.ERROR));
      return response;
    });
  }
}

export function needMetrics(topologyId, nodeId, force){
  return (dispatch, getState) => {
    const item = getState().metrics.elements[nodeId];
    if (force || !item || item.state == stateType.NOT_LOADED || item.state == stateType.ERROR) {
      return dispatch(loadMetrics(topologyId, nodeId));
    } else {
      return Promise.resolve(true);
    }
  }
}