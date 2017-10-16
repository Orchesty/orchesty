import * as types from 'rootApp/baseActionTypes';
import {stateType, listType} from 'rootApp/types';

function listReducer(state, action, getElementId) {
  switch (action.type) {
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
      const newData = {
        sort: action.sort
      };
      if (state.type == listType.PAGINATION && state.page != 0){
        newData['page'] = 0;
      }
      return Object.assign({}, state, newData);

    case types.LIST_CHANGE_PAGE:
      return Object.assign({}, state, {
        page: action.page
      });

    case types.LIST_CHANGE_FILTER:
      const newDataF = {
        filter: action.filter
      };
      if (state.type == listType.PAGINATION && state.page != 0){
        newDataF['page'] = 0;
      }
      return Object.assign({}, state, newDataF);

    case types.LIST_INVALIDATE:
      if (state.state != stateType.NOT_LOADED && (state.type == listType.PAGINATION ||
          (state.type == listType.RELATION && state.objectType === action.objectType && state.objectId === action.objectId))) {
        return Object.assign({}, state, {state: stateType.NOT_LOADED});
      } else {
        return state;
      }

    default:
      return state;
  }
}

export default (state = {}, action, getElementId) => {
  let newState;
  switch (action.type) {
    case types.LIST_CREATE:
      let list = {
        id: action.id,
        type: action.listType,
        state: stateType.NOT_LOADED,
        local: Boolean(action.local),
        items: null
      };

      switch (action.listType) {
        case listType.COMPLETE:
          list['sort'] = action.sort;
          list['filter'] = action.filter;
          break;

        case listType.PAGINATION:
          list['pageSize'] = action.pageSize;
          list['page'] = action.page;
          list['sort'] = action.sort;
          list['filter'] = action.filter;
          break;

        case listType.RELATION:
          list['objectType'] = action.objectType;
          list['objectId'] = action.objectId;
          break;
      }

      return Object.assign({}, state, {[action.id]: list});

    case types.LIST_DELETE:
      newState = Object.assign({}, state);
      delete newState[action.id];
      return newState;

    case types.LIST_INVALIDATE:
      newState = {};
      let changed = false;
      Object.getOwnPropertyNames(state).forEach(id => {
        let res = listReducer(state[id], action, getElementId);
        newState[id] = res;
        changed = changed || newState !== res;
      });
      return changed ? newState : state;

    case types.LIST_LOADING:
    case types.LIST_RECEIVE:
    case types.LIST_ERROR:
    case types.LIST_CHANGE_PAGE:
    case types.LIST_CHANGE_SORT:
    case types.LIST_CHANGE_FILTER:
      return Object.assign({}, state, {
        [action.id]: listReducer(state[action.id], action, getElementId)
      });

    default:
      return state;
  }
}