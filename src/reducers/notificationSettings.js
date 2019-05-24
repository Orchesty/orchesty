import * as types from 'rootApp/actionTypes';
import listsReducer from './list';

const listPrefix = 'NOTIFICATION_SETTING/LIST/';
const listPrefixLength = listPrefix.length;

const initialState = {
  elements: {},
  lists: {},
  events: {},
  initialize: false,
};

function getElementId(element) {
  return element.name;
}

function addElement(oldElements, element) {
  return Object.assign({}, oldElements, {
    [element.name]: element,
  });
}

function addElements(oldElements, newElements) {
  const result = Object.assign({}, oldElements);
  newElements.forEach((item) => {
    result[item.name] = item;
  });
  return result;
}

function reducer(state, action) {
  switch (action.type) {
    case types.NOTIFICATION_SETTINGS_RECEIVE_ITEMS:
      return Object.assign({}, state, {
        elements: addElements(state.elements, action.items),
      });

    case types.NOTIFICATION_SETTINGS_RECEIVE_EVENTS:
      return Object.assign({}, state, {
        events: action.events,
      });

    case types.NOTIFICATION_SETTINGS_RECEIVE:
      return Object.assign({}, state, {
        elements: addElement(state.elements, action.data),
      });

    case types.NOTIFICATION_SETTINGS_INITIALIZE:
      return Object.assign({}, state, {
        initialize: true
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
