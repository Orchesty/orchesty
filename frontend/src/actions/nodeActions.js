import * as types from 'rootApp/actionTypes';
import listFactory from './factories/listFactory';
import { stateType } from 'rootApp/types';
import serverRequest, { startingPointRequest } from 'services/apiGatewayServer';
import processes from 'enums/processes';
import * as notificationActions from './notificationActions';
import * as processActions from './processActions';

const {
  createRelationList, listLoading, listError, listReceive, invalidateLists,
} = listFactory('NODE/LIST/');

export const nodeInvalidateLists = invalidateLists;

function receive(data) {
  return {
    type: types.NODE_RECEIVE,
    data,
  };
}

function receiveItems(items) {
  return {
    type: types.NODE_RECEIVE_ITEMS,
    items,
  };
}

function loadListForTopology(listId, topologyId, loadingState = true) {
  return (dispatch) => {
    if (loadingState) {
      dispatch(listLoading(listId));
    }
    return serverRequest(dispatch, 'GET', `/topologies/${topologyId}/nodes`).then((response) => {
      if (response) {
        dispatch(receiveItems(response.items));
      }
      dispatch(response ? listReceive(listId, response) : listError(listId));
      return response;
    });
  };
}

export function needNodesForTopology(topologyId, forced = false, loadingState = true) {
  return (dispatch, getState) => {
    const listId = `@topology-${topologyId}`;
    const list = getState().node.lists[listId];
    if (!list) {
      dispatch(createRelationList(listId, 'topology', topologyId));
    }
    if (forced || !list || list.state === stateType.NOT_LOADED || list.state === stateType.ERROR) {
      return dispatch(loadListForTopology(listId, topologyId, loadingState));
    }
    return Promise.resolve(true);
  };
}

export function needNode(id, force = false) {
  return (dispatch, getState) => {
    const node = getState().node.elements[id];
    if (!node || force) {
      return serverRequest(dispatch, 'GET', `/nodes/${id}`).then((response) => {
        if (response) {
          dispatch(receive(response));
        }
        return response;
      });
    }
    return Promise.resolve(node);
  };
}

export function nodeUpdate(id, data, silent = false) {
  return (dispatch) => {
    dispatch(processActions.startProcess(processes.nodeUpdate(id)));
    return serverRequest(dispatch, 'PATCH', `/nodes/${id}`, null, data).then((response) => {
      if (response) {
        if (!silent) {
          dispatch(notificationActions.addSuccess('Node was updated'));
        }
        dispatch(receive(response));
      }
      dispatch(processActions.finishProcess(processes.nodeUpdate(id), response));
      return response;
    });
  };
}

export function nodeRun(nodeId, nodeName, nodeType, topologyId, topologyName, userId, data, silent = false) {
  return dispatch => new Promise((resolve, reject) => {
    dispatch(processActions.startProcess(processes.nodeRun(nodeId)));
    startingPointRequest(dispatch, 'POST', getNodeRunUrl(nodeId, nodeName, nodeType, topologyId, topologyName, userId), null, data).then((response) => {
      dispatch(processActions.finishProcess(processes.nodeRun(nodeId), response));
      if (response) {
        if (!silent) {
          dispatch(notificationActions.addSuccess('Node was started successfully.'));
        }
        resolve(true);
      } else {
        reject('Node starting failed.');
      }
    });
  });
}

export function getNodeRunUrl(nodeId, nodeName, nodeType, topologyId, topologyName, userId, data = {}) {
  return nodeType === 'webhook' ?
    `/topologies/${topologyName}/nodes/${nodeName}/token/${data.token ? data.token : 'token'}/run` :
    `/topologies/${topologyId}/nodes/${nodeId}/user/${userId}/run`;
}