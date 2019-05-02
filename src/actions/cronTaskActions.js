import * as types from 'rootApp/actionTypes';
import listFactory from './factories/listFactory';
import serverRequest from 'services/apiGatewayServer';

const { listLoading, listError, listReceive } = listFactory('CRON_TASK/LIST/');

function receiveItems(items) {
  return {
    type: types.CRON_TASKS_RECEIVE_ITEMS,
    items,
  };
}

function loadList(id, loadingState = true) {
  return (dispatch) => {
    if (loadingState) {
      dispatch(listLoading(id));
    }

    return serverRequest(dispatch, 'GET', '/topologies/cron').then((response) => {
      dispatch(receiveItems(response.items.map(item => {
        item.name = item.topology.name + item.topology.version;

        return item;
      })));
      dispatch(response ? listReceive(id, response) : listError(id));
      return response;
    });
  };
}

export function needCronTaskList(listId) {
  return (dispatch) => {
    return dispatch(loadList(listId));
  };
}

export function cronTaskInitialize() {
  return (dispatch) => {
    dispatch({ type: types.CRON_TASKS_INITIALIZE });

    return Promise.resolve(true);
  }
}
