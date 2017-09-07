import * as types from '../actionTypes';
import listsReducer from './list';

const listPrefix = 'NODE/LIST/';
const listPrefixLength = listPrefix.length;

const initialState = {
  elements: {},
  lists: {}
};

function getElementId(element){
  return element._id;
}

function addElement(oldElements, element){
  return Object.assign({}, oldElements, {
    [element._id]: element
  });
}

function addElements(oldElements, newElements){
  const result = Object.assign({}, oldElements);
  newElements.forEach(item => {
    result[item._id] = item;
  });
  return result;
}

function reducer(state, action){
  switch (action.type){
    case types.NODE_LIST_RECEIVE:
      return Object.assign({}, state, {
        elements: addElements(state.elements, action.data.items)
      });

    case types.NODE_RECEIVE:
      return Object.assign({}, state, {
        elements: addElement(state.elements, action.data)
      });

    default:
      return state;
  }
}

export default (state = initialState, action) => {
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