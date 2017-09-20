import * as types from 'rootApp/actionTypes';
import listsReducer from './list';

const listPrefix = 'AUTHORIZATION/LIST/';
const listPrefixLength = listPrefix.length;

const initialState = {
  elements: {},
  lists: {},
  settings: {}
};

function getElementId(element){
  return element.name;
}

function addElement(oldElements, element){
  return Object.assign({}, oldElements, {
    [element.name]: element
  });
}

function addElements(oldElements, newElements){
  const result = Object.assign({}, oldElements);
  newElements.forEach(item => {
    result[item.name] = item;
  });
  return result;
}

function reducer(state, action){
  switch (action.type){
    case types.AUTHORIZATION_LIST_RECEIVE:
      return Object.assign({}, state, {
        elements: addElements(state.elements, action.data.items)
      });

    case types.AUTHORIZATION_RECEIVE:
      return Object.assign({}, state, {
        elements: addElement(state.elements, action.data)
      });

    case types.AUTHORIZATION_RECEIVE_SETTINGS:
      return Object.assign({}, state, {
        settings: Object.assign({}, state.settings, {
          [action.id]: action.data
        })
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