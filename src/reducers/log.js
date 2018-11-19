import * as types from 'rootApp/actionTypes';
import listsReducer from './list';

const listPrefix = 'LOG/LIST/';
const listPrefixLength = listPrefix.length;

const initialState = {
  elements: {},
  lists: {},
};

function getElementId(element) {
  return element.id;
}

function addElements(oldElements, newElements) {
  const result = Object.assign({}, oldElements);
  newElements.forEach((item) => {
    item.time = new Date(item.timestamp);
    result[item.id] = item;
  });
  return result;
}

function reducer(state = initialState, action) {
  switch (action.type) {
    case types.LOG_RECEIVE_ITEMS:
      return Object.assign({}, state, {
        elements: addElements(state.elements, action.items),
      });

    default:
      return state;
  }
}

export default (state = initialState, action) => {
  if (action.type === types.USER_LOGOUT || action.type === types.USER_LOGGED) {
    return initialState;
  }

  let newState = reducer(state, action);
  if (action.type.startsWith(listPrefix)) {
    const lists = listsReducer(state.lists, Object.assign({}, action, { type: action.type.substring(listPrefixLength) }), getElementId);
    if (newState === state && lists !== state.lists) {
      newState = Object.assign({}, newState);
    }
    newState.lists = lists;
  }
  return newState;
};
