import * as types from 'rootApp/actionTypes';
import listFactory from './factories/listFactory';
import serverRequest ,{sortToQuery, makeUrl} from 'services/apiGatewayServer';
import config from 'rootApp/config';
import {stateType} from 'rootApp/types';
import objectEquals from 'utils/objectEquals';


const {createPaginationList, listLoading, listError, listReceive, listDelete, listChangePage} = listFactory('LOG/LIST/');

function receiveItems(items){
  return {
    type: types.LOG_RECEIVE_ITEMS,
    items
  }
}

function loadList(id, loadingState = true){
  return (dispatch, getState) => {
    if (loadingState) {
      dispatch(listLoading(id));
    }
    const list = getState().log.lists[id];
    const offset = list.page ? list.page * list.pageSize : 0;
    return serverRequest(dispatch, 'GET', '/logs', sortToQuery(list.sort, {
      offset,
      limit: list.pageSize
    })).then(response => {
      if (response){
        dispatch(receiveItems(response.items));
      }
      dispatch(response ? listReceive(id, response) : listError(id));
      return response;
    })
  }
}

export function needLogList(listId, pageSize = config.params.defaultPageSize) {
  return (dispatch, getState) => {
    const list = getState().log.lists[listId];
    if (!list) {
      dispatch(createPaginationList(listId, pageSize));
    }
    if (!list || list.state == stateType.NOT_LOADED || list.state == stateType.ERROR) {
      return dispatch(loadList(listId));
    } else {
        return Promise.resolve(true);
    }
  }
}

export function logListChangePage(listId, page) {
  return (dispatch, getState) => {
    const oldPage = getState().log.lists[listId].page;
    if (!objectEquals(oldPage, page)){
      dispatch(listChangePage(listId, page));
      dispatch(loadList(listId));
    }
  }
}