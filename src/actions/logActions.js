import * as types from 'rootApp/actionTypes';
import listFactory from './factories/listFactory';
import serverRequest, { sortToQuery } from 'services/apiGatewayServer';
import config from 'rootApp/config';
import {stateType} from 'rootApp/types';
import objectEquals from 'utils/objectEquals';

const { createPaginationList, listLoading, listError, listReceive, listDelete, listChangePage, listChangeSort, listChangeFilter } = listFactory('LOG/LIST/');

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
    const orderby = sortToQuery(list.sort).order_by;
    const filter = list.filter || {};

    const sendFilter = {};
    if (filter && filter.severity && filter.severity.value) {
      sendFilter.severity = filter.severity.value;
    }
    if (filter && filter.search && filter.search.value) {
      sendFilter.search = filter.search.value;
    }

    const headers = {
      page: list.page + 1,
      limit: list.pageSize,
      orderby: orderby ? orderby : 'timestamp-',
      filter: JSON.stringify(sendFilter),
    };

    return serverRequest(dispatch, 'GET', '/logs', null, null, headers).then(response => {
      if (response) {
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

export function logListChangeSort(listId, sort) {
  return (dispatch, getState) => {
    const oldSort = getState().log.lists[listId].sort;
    if (!objectEquals(oldSort, sort)) {
      dispatch(listChangeSort(listId, sort));
      return dispatch(loadList(listId, false));
    }
    else {
      return Promise.resolve(true);
    }
  }
}

export function logListChangeFilter(listId, filter) {
  return (dispatch, getState) => {
    const list = getState().log.lists[listId];
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