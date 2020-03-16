import * as types from 'rootApp/actionTypes';
import listFactory from './factories/listFactory';
import serverRequest, { sortToQuery, startingPointRequest } from 'services/apiGatewayServer';

import config from 'rootApp/config';
import objectEquals from 'utils/objectEquals';

const {
  createPaginationList, listLoading, listError, listReceive, listChangePage, listChangeSort, listChangeFilter,
} = listFactory('HUMAN_TASK/LIST/');

function receiveItems(items) {
  return {
    type: types.HUMAN_TASKS_RECEIVE_ITEMS,
    items,
  };
}

function receiveNodes(items) {
  return {
    type: types.HUMAN_TASKS_RECEIVE_NODES,
    items,
  };
}

function loadList(id, loadingState = true) {
  return (dispatch, getState) => {
    if (loadingState) {
      dispatch(listLoading(id));
    }
    const list = getState().humanTask.lists[id];
    const orderby = sortToQuery(list.sort).order_by;
    const { filter: { topologyId, nodeId, auditLogs } = {} } = list;

    const headers = {
      page: list.page + 1,
      limit: list.pageSize,
      orderby: orderby || 'created-',
      filter: JSON.stringify(auditLogs ? { search: auditLogs } : {}),
    };

    return serverRequest(dispatch, 'GET', `/longRunning/id/topology/${topologyId}/${nodeId ? `node/${nodeId}/` : ''}getTasks`, null, null, headers).then((response) => {
      if (response) {
        const topologies = response.items.map((topology) => {
          topology.name = topology.id;

          return topology;
        });

        dispatch(receiveItems(topologies));

        serverRequest(dispatch, 'GET', `/topologies/${topologyId}/nodes?limit=1000`).then((response) => {
          if (response) {
            const nodes = response.items.filter(item => item.type === 'user').map((node) => {
              node.customName = node.name;
              node.name = node._id;

              return node;
            });

            dispatch(receiveNodes(nodes));
          }
        });
      }
      dispatch(response ? listReceive(id, response) : listError(id));
      return response;
    });
  };
}

export function needHumanTaskList(listId, pageSize = config.params.defaultPageSize) {
  return (dispatch, getState) => {
    const list = getState().humanTask.lists[listId];
    if (!list) {
      dispatch(createPaginationList(listId, pageSize));
    }
    return dispatch(loadList(listId));
  };
}

export function humanTaskListChangePage(listId, page) {
  return (dispatch, getState) => {
    const oldPage = getState().humanTask.lists[listId].page;
    if (!objectEquals(oldPage, page)) {
      dispatch(listChangePage(listId, page));
      dispatch(loadList(listId));
    }
  };
}

export function humanTaskChangeSort(listId, sort) {
  return (dispatch, getState) => {
    const oldSort = getState().humanTask.lists[listId].sort;
    if (!objectEquals(oldSort, sort)) {
      dispatch(listChangeSort(listId, sort));
      return dispatch(loadList(listId, false));
    }

    return Promise.resolve(true);
  };
}

export function humanTaskListChangeFilter(listId, filter) {
  return (dispatch, getState) => {
    const list = getState().humanTask.lists[listId];
    if (!objectEquals(list.filter, filter)) {
      dispatch(listChangeFilter(listId, filter));
      if (filter.apply) {
        delete filter.apply;
        return dispatch(loadList(listId));
      }
      return Promise.resolve(true);
    }
    return Promise.resolve(true);
  };
}

export function humanTaskProcess(listId, topology, node, token, approve, body) {
  return dispatch => new Promise((resolve, reject) => {
    startingPointRequest(dispatch, 'POST', getHumanTaskRunUrl(topology, node, token, approve), null, body).then((response) => {
      if (response) {
        dispatch(loadList(listId));

        resolve(true);
      }

      reject('Something gone wrong.')
    });
  });
}

export function getHumanTaskRunUrl(topology, node, token, approve) {
  return `/human-tasks/topologies/${topology}/nodes/${node}/token/${token}/${approve ? 'run' : 'stop'}`
}

export function humanTaskChange(listId, id, body) {
  return dispatch => new Promise((resolve, reject) => {
    serverRequest(dispatch, 'PUT', `/longRunning/${id}`, null, { data: body }).then((response) => {
      if (response) {
        dispatch(loadList(listId));

        resolve(true);
      }

      reject('Something gone wrong.')
    });
  });
}

export function humanTaskInitialize() {
  return (dispatch) => {
    dispatch({ type: types.HUMAN_TASKS_INITIALIZE });

    return Promise.resolve(true);
  }
}
