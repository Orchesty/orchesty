import * as types from 'rootApp/actionTypes';
import { stateType } from 'rootApp/types';
import serverRequest from 'services/apiGatewayServer';

function setTopologyState(topologyId, key, state) {
  return {
    type: types.METRICS_TOPOLOGY_SET_STATE,
    id: topologyId,
    key,
    state,
  };
}

function receiveItems(items, suffix) {
  return {
    type: types.METRICS_RECEIVE_ITEMS,
    items,
    suffix,
  };
}

function topologyReceive(topologyId, key, items) {
  return {
    type: types.METRICS_TOPOLOGY_RECEIVE,
    id: topologyId,
    key,
    items,
  };
}

function setMetricsState(nodeId, state) {
  return {
    type: types.METRICS_SET_STATE,
    id: nodeId,
    state,
  };
}

function receive(nodeId, data) {
  return {
    type: types.METRICS_RECEIVE,
    id: nodeId,
    data,
  };
}

export function invalidateTopologyMetrics(topologyId) {
  return {
    type: types.METRICS_TOPOLOGY_INVALIDATE,
    id: topologyId,
  };
}

function loadTopologyMetrics(topologyId, range) {
  return (dispatch) => {
    const suffix = range ? `[${range.since}-${range.till}]` : '';
    const key = `${topologyId}${suffix}`;
    dispatch(setTopologyState(topologyId, key, stateType.LOADING));
    const queries = range ? { from: range.since, to: range.till } : null;
    return serverRequest(dispatch, 'GET', `/metrics/topology/${topologyId}`, queries).then((response) => {
      if (response) {
        dispatch(receiveItems(response, suffix));
      }
      dispatch(response ? topologyReceive(topologyId, key, response) : setTopologyState(topologyId, key, stateType.ERROR));
      return response;
    });
  };
}

export function needTopologyMetrics(topologyId, range, force = false) {
  return (dispatch, getState) => {
    const key = range ? `${topologyId}[${range.since}-${range.till}]` : topologyId;
    const list = getState().metrics.topologies[key];
    if (force || !list || list.state === stateType.NOT_LOADED || list.state === stateType.ERROR) {
      return dispatch(loadTopologyMetrics(topologyId, range));
    }
    return Promise.resolve(true);
  };
}

export function loadTopologyMetricsWithRequest(topologyId, interval, range) {
  return (dispatch) => {
    const suffix = range ? `[${interval}][${range.since}-${range.till}]` : `[${interval}]`;
    const key = `${topologyId}${suffix}`;
    dispatch(setTopologyState(topologyId, key, stateType.LOADING));
    const queries = range ? { from: range.since, to: range.till, interval } : { interval };
    return serverRequest(dispatch, 'GET', `/metrics/topology/${topologyId}/requests`, queries).then((response) => {
      if (response) {
        dispatch(receiveItems(response, suffix));
      }
      dispatch(response ? topologyReceive(topologyId, key, response) : setTopologyState(topologyId, key, stateType.ERROR));
      return response;
    });
  };
}

export function needTopologyMetricsWithRequests(topologyId, interval, range, force = false) {
  return (dispatch, getState) => {
    const key = `${topologyId}[${interval}]${range ? `[${range.since}-${range.till}]` : ''}`;
    const list = getState().metrics.topologies[key];
    if (force || !list || list.state === stateType.NOT_LOADED || list.state === stateType.ERROR) {
      return dispatch(loadTopologyMetricsWithRequest(topologyId, interval, range));
    }
    return Promise.resolve(true);
  };
}

function loadMetrics(topologyId, nodeId) {
  return (dispatch) => {
    dispatch(setMetricsState(nodeId, stateType.LOADING));
    return serverRequest(dispatch, 'GET', `/metrics/topology/${topologyId}/node/${nodeId}`).then((response) => {
      dispatch(response ? receive(nodeId, response) : setMetricsState(nodeId, stateType.ERROR));
      return response;
    });
  };
}

export function needMetrics(topologyId, nodeId, force) {
  return (dispatch, getState) => {
    const item = getState().metrics.elements[nodeId];
    if (force || !item || item.state === stateType.NOT_LOADED || item.state === stateType.ERROR) {
      return dispatch(loadMetrics(topologyId, nodeId));
    }
    return Promise.resolve(true);
  };
}
