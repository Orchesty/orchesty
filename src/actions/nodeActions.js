import * as types from '../actionTypes';
import listFactory from './factories/listFactory';
import {stateType} from '../types';
import serverRequest from '../services/apiGatewayServer';
import * as notificationActions from './notificationActions';

const {createRelationList, listLoading, listError, listReceive} = listFactory('NODE/LIST/');

function receive(data){
  return {
    type: types.NODE_RECEIVE,
    data
  }
}

function loadListForTopology(listId, topologyId, loadingState = true) {
  return dispatch => {
    if (loadingState){
      dispatch(listLoading(listId));
    }
    return serverRequest(dispatch, 'GET', `/topologies/${topologyId}/nodes`).then(response => {
      dispatch(response ? listReceive(listId, response) : listError(listId));
      return response;
    });
  }
}

export function needNodesForTopology(topologyId, forced = false, loadingState = true){
  return (dispatch, getState) => {
    const listId = '@topology-' + topologyId;
    const list = getState().node.lists[listId];
    if (!list){
      dispatch(createRelationList(listId, 'topology', topologyId));
    }
    if (forced || !list || list.state == stateType.NOT_LOADED || list.state == stateType.ERROR){
      return dispatch(loadListForTopology(listId, topologyId, loadingState));
    } else {
      return Promise.resolve(true);
    }
  }
}

export function needNode(id, force = false){
  return (dispatch, getState) => {
    const node = getState().node.elements[id];
    if (!node || force){
      return serverRequest(dispatch, 'GET', `/nodes/${id}`).then(
        response => {
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

export function nodeUpdate(id, data, silent = false){
  return dispatch => {
    return serverRequest(dispatch, 'PATCH', `/nodes/${id}`, null, data).then(
      response => {
        if (response) {
          if (!silent){
            dispatch(notificationActions.addSuccess('Node was updated'));
          }
          dispatch(receive(response));
        }
        return response;
      }
    )
  }
}

export function nodeRun(id, silent = false){
  return dispatch => {
    return new Promise((resolve, reject) => {
      dispatch(needNode(id)).then(node => {
        if (node) {
          serverRequest(dispatch, 'POST', `/topologies/${node.topology_id}/nodes/${node._id}/run`).then(
            response => {
              if (response) {
                if (!silent){
                  dispatch(notificationActions.addSuccess('Node was started successfully.'));
                }
                resolve(true);
              } else {
                reject('Node starting failed.');
              }
            }
          )
        } else {
          reject('Node does not exists.');
        }
      })
    })
  }
}