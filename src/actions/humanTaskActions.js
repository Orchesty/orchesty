import * as types from 'rootApp/actionTypes';
import listFactory from './factories/listFactory';
import serverRequest, { sortToQuery } from 'services/apiGatewayServer';

import config from 'rootApp/config';
import objectEquals from 'utils/objectEquals';

const { createPaginationList, listLoading, listError, listReceive, listChangePage, listChangeSort, listChangeFilter } = listFactory('HUMAN_TASK/LIST/');

function receiveItems(items) {
  return {
    type: types.HUMAN_TASKS_RECEIVE_ITEMS,
    items
  }
}

function receiveNodes(items) {
  return {
    type: types.HUMAN_TASKS_RECEIVE_NODES,
    items
  }
}

function loadList(id, loadingState = true) {
  return (dispatch, getState) => {
    if (loadingState) {
      dispatch(listLoading(id));
    }
    const list = getState().humanTask.lists[id];
    const orderby = sortToQuery(list.sort).order_by;
    const { filter: { topologyId, topologyName, nodeName, auditLogs } = {} } = list;

    const headers = {
      page: list.page + 1,
      limit: list.pageSize,
      orderby: orderby ? orderby : 'created-',
      filter: JSON.stringify(auditLogs ? { search: auditLogs } : {}),
    };

    return serverRequest(dispatch, 'GET', `/longRunning/topology/${topologyName}/${nodeName ? `node/${nodeName}/` : ''}getTasks`, null, null, headers).then(response => {
      if (response) {
        const topologies = response.items.map(topology => {
          topology.name = topology._id;

          return topology;
        });

        dispatch(receiveItems(topologies));

        serverRequest(dispatch, 'GET', `/topologies/${topologyId}/nodes?limit=1000`).then(response => {
          if (response) {
            const nodes = response.items.filter(item => item.type === 'user').map(node => {
              node.customName = node.name;
              node.name = node._id;

              return node;
            });

            dispatch(receiveNodes(nodes))
          }
        });

      }
      dispatch(response ? listReceive(id, response) : listError(id));
      return response;
    })
  }
}

export function needHumanTaskList(listId, pageSize = config.params.defaultPageSize) {
  return (dispatch, getState) => {
    const list = getState().humanTask.lists[listId];
    if (!list) {
      dispatch(createPaginationList(listId, pageSize));
    }
    return dispatch(loadList(listId));
  }
}

export function humanTaskListChangePage(listId, page) {
  return (dispatch, getState) => {
    const oldPage = getState().humanTask.lists[listId].page;
    if (!objectEquals(oldPage, page)) {
      dispatch(listChangePage(listId, page));
      dispatch(loadList(listId));
    }
  }
}

export function humanTaskChangeSort(listId, sort) {
  return (dispatch, getState) => {
    const oldSort = getState().humanTask.lists[listId].sort;
    if (!objectEquals(oldSort, sort)) {
      dispatch(listChangeSort(listId, sort));
      return dispatch(loadList(listId, false));
    }
    else {
      return Promise.resolve(true);
    }
  }
}

export function humanTaskListChangeFilter(listId, filter) {
  return (dispatch, getState) => {
    const list = getState().humanTask.lists[listId];
    if (!objectEquals(list.filter, filter)) {
      dispatch(listChangeFilter(listId, filter));
      if (filter.apply) {
        delete filter.apply;
        return dispatch(loadList(listId));
      } else {
        return Promise.resolve(true);
      }
    } else {
      return Promise.resolve(true);
    }
  }
}

export function humanTaskProcess(listId, topology, node, token, approve) {
  return dispatch => {
    serverRequest(dispatch, 'GET', `/longRunning/${approve ? 'run' : 'stop'}/topology/${topology}/node/${node}/token/${token}`).then(response => {
      if (response) {
        return dispatch(loadList(listId));
      } else {
        return response;
      }
    });
  };
}