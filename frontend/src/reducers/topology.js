import * as types from '../actionTypes';
import {listType, stateType} from '../types';

const initialState = {
  elements: {},
  listNewId: 0,
  lists: {},
  schemas: {}
};

function addElements(oldElements, newElements){
  const result = Object.assign({}, oldElements);
  newElements.forEach(item => {
    result[item.id] = item;
  });
  return result;
}

function addElement(oldElements, element){
  const result = Object.assign({}, oldElements, {
    [element.id]: element
  });
  return result;
}

function listReducer(state, action){
  switch (action.type) {

    case types.TOPOLOGY_LIST_LOAD:
      return Object.assign({}, state, {
        state: stateType.LOADING,
        items: null
      });

    case types.TOPOLOGY_LIST_RECEIVE:
      const {data} = action;
      return Object.assign({}, state, {
        state: stateType.SUCCESS,
        items: data.items.map(item => item.id),
        count: data.count,
        limit: data.limit,
        offset: data.offset,
        total: data.total
      });

    case types.TOPOLOGY_LIST_ERROR:
      return Object.assign({}, state, {
        state: stateType.ERROR
      });

    case types.TOPOLOGY_LIST_CHANGE_SORT:
      return Object.assign({}, state, {
        sort: action.sort
      });

    case types.TOPOLOGY_LIST_CHANGE_PAGE:
      return Object.assign({}, state, {
        page: action.page
      });

    default:
      return state;
  }
}

function listsReducer(state, action) {
  switch (action.type) {

    case types.TOPOLOGY_LIST_LOAD:
    case types.TOPOLOGY_LIST_RECEIVE:
    case types.TOPOLOGY_LIST_ERROR:
    case types.TOPOLOGY_LIST_CHANGE_SORT:
    case types.TOPOLOGY_LIST_CHANGE_PAGE:
      return Object.assign({}, state, {
        [action.listId]: listReducer(state[action.listId], action)
      });

    case types.TOPOLOGY_LIST_DELETE:
      const newState = Object.assign({}, state);
      delete newState[action.listId];
      return newState;

    default:
      return state;
  }
}

export default (state = initialState, action) => {
  switch (action.type){

    case types.TOPOLOGY_LIST_CREATE:
      const newId = state.listNewId + 1;
      let list = {
        id: newId,
        type: action.listType,
        state: stateType.NOT_LOADED,
        sort: action.sort,
        items: null
      };
      if (action.listType = listType.PAGINATION){
        list['pageSize'] = action.pageSize;
        list['page'] = 1;
      }
      return Object.assign({}, state, {
        listNewId: newId,
        lists: Object.assign({}, state.lists, {[newId]: list})
      });
    
    case types.TOPOLOGY_LIST_LOAD:
    case types.TOPOLOGY_LIST_ERROR:
    case types.TOPOLOGY_LIST_CHANGE_SORT:
    case types.TOPOLOGY_LIST_CHANGE_PAGE:
    case types.TOPOLOGY_LIST_DELETE:
      return Object.assign({}, state, {
        lists: listsReducer(state.lists, action)
      });

    case types.TOPOLOGY_LIST_RECEIVE: 
      return Object.assign({}, state, {
        elements: addElements(state.elements, action.data.items),
        lists: listsReducer(state.lists, action)
      });
    
    case types.TOPOLOGY_RECEIVE:
      return Object.assign({}, state, {
        elements: addElement(state.elements, action.data)
      });

    case types.TOPOLOGY_RECEIVE_SCHEMA:
      return Object.assign({}, state, {
        schemas: Object.assign({}, state.schemas, {
          [action.id]: action.data
        })
      });

    default:
      return state;
  }
}