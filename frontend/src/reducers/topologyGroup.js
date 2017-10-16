import * as types from 'rootApp/actionTypes';
import listsReducer from './list';

const listPrefix = 'TOPOLOGY_GROUP/LIST/';
const listPrefixLength = listPrefix.length;

const initialState = {
  elements: {},
  lists: {}
};

function getElementId(element){
  return element.id;
}

function addElements(newElements){
  const result = {};
  newElements.forEach(item => {
    result[item.id] = item;
  });
  return result;
}

function addElement(oldElements, element){
  return Object.assign({}, oldElements, {
    [element.id]: element
  });
}

function reducer(state = initialState, action){
  switch (action.type){
    case types.TOPOLOGY_GROUP_RECEIVE_ITEMS:
      return Object.assign({}, state, {
        elements: addElements(action.items)
      });

    case types.TOPOLOGY_GROUP_RECEIVE:
      return Object.assign({}, state, {
        elements: addElement(state.elements, action.topologyGroup)
      });

    case types.TOPOLOGY_GROUP_REMOVE:
      const newElements = Object.assign({}, state.elements);
      delete newElements[action.id];
      return Object.assign({}, state, {elements: newElements});

    default:
      return state;
  }
}

export default (state = initialState, action) => {
  if (action.type == types.USER_LOGOUT || action.type == types.USER_LOGGED){
    return initialState;
  }

  let newState = reducer(state, action);
  if (action.type.startsWith(listPrefix)){
    const lists = listsReducer(state.lists, Object.assign({}, action, {type: action.type.substring(listPrefixLength)}), getElementId);
    if (newState == state && lists != state.lists) {
      newState = Object.assign({}, newState);
    }
    newState.lists = lists;
  }
  return newState;
}