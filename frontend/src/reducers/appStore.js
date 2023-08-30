import listsReducer from './list';
import * as types from '../actionTypes';

const listPrefix = 'APP_STORE/LIST/';
const listPrefixLength = listPrefix.length;

const initialState = {
  elements: {},
  lists: {},
  initialize: false,
  applications: {},
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
    case types.APP_STORE_RECEIVE_ITEMS:
      return Object.assign({}, state, {
        elements: addElements(state.elements, action.items),
      });

    case types.APP_STORE_RECEIVE:
      return Object.assign({}, state, {
        elements: addElement(state.elements, action.data),
      });

    case types.APP_STORE_INITIALIZE:
      return Object.assign({}, state, {
        initialize: true
      });

    case types.APP_STORE_RECEIVE_APPLICATION:
      const { application, data } = action;
      const innerApplication = state.applications[application] || {};

      return Object.assign({}, state, {
        applications: Object.assign({}, state.applications, { [application]: { ...innerApplication, ...data } }),
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