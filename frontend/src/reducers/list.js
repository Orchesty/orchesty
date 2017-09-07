import * as types from '../baseActionTypes';
import {stateType, listType} from '../types';

function listReducer(state, action, getElementId){
  switch (action.type){
    case types.LIST_LOADING:
      return Object.assign({}, state, {
        state: stateType.LOADING,
        items: null
      });

    case types.LIST_RECEIVE:
      const {data} = action;
      return Object.assign({}, state, {
        state: stateType.SUCCESS,
        items: data.items.map(getElementId),
        count: data.count,
        limit: data.limit,
        offset: data.offset,
        total: data.total
      });

    case types.LIST_ERROR:
      return Object.assign({}, state, {
        state: stateType.ERROR,
        items: null
      });

    case types.LIST_CHANGE_SORT:
      return Object.assign({}, state, {
        sort: action.sort
      });

    case types.LIST_CHANGE_PAGE:
      return Object.assign({}, state, {
        page: action.page
      });
    
    default:
      return state;
  }
}

export default (state = {}, action, getElementId) => {
  switch (action.type){
    case types.LIST_CREATE:
      let list = {
        id: action.id,
        type: action.listType,
        state: stateType.NOT_LOADED,
        items: null
      };
      if (action.listType == listType.PAGINATION){
        list['pageSize'] = action.pageSize;
        list['page'] = action.page;
        list['sort'] = action.sort;
      }
      return Object.assign({}, state, {[action.id]: list});

    case types.LIST_DELETE:
      const newState = Object.assign({}, state);
      delete newState[action.id];
      return newState;

    case types.LIST_LOADING:
    case types.LIST_RECEIVE:
    case types.LIST_ERROR:
    case types.LIST_CHANGE_PAGE:
    case types.LIST_CHANGE_SORT:
      return Object.assign({}, state, {
        [action.id]: listReducer(state[action.id], action, getElementId)
      });

    default:
      return state;
  }
}