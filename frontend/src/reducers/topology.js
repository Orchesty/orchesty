import * as types from 'rootApp/actionTypes';
import listsReducer from './list';

const listPrefix = 'TOPOLOGY/LIST/';
const listPrefixLength = listPrefix.length;

const initialState = {
  elements: {},
  lists: {},
  schemas: {},
  tests: {}
};

function getElementId(element){
  return element._id;
}

function addElements(oldElements, newElements){
  const result = Object.assign({}, oldElements);
  newElements.forEach(item => {
    result[item._id] = item;
  });
  return result;
}

function addElement(oldElements, element){
  return Object.assign({}, oldElements, {
    [element._id]: element
  });
}

function reducer(state = initialState, action){
  switch (action.type){
    case types.TOPOLOGY_LIST_RECEIVE: 
      return Object.assign({}, state, {
        elements: addElements(state.elements, action.data.items)
      });
    
    case types.TOPOLOGY_RECEIVE:
      return Object.assign({}, state, {
        elements: addElement(state.elements, action.data)
      });

    case types.TOPOLOGY_REMOVE:
      const newElements = Object.assign({}, state.elements);
      delete newElements[action.id];
      return Object.assign({}, state, {elements: newElements});

    case types.TOPOLOGY_RECEIVE_SCHEMA:
      return Object.assign({}, state, {
        schemas: Object.assign({}, state.schemas, {
          [action.id]: action.data
        })
      });

    case types.TOPOLOGY_RECEIVE_TEST:
      return Object.assign({}, state, {
        tests: Object.assign({}, state.tests, {
          [action.data.id]: Object.assign({}, action.data, {
            nodes: action.data.nodes.map(item => item.node_id)
          })
        })
      });

    case types.TOPOLOGY_RESET_TEST:
      const newTests = Object.assign({}, state.tests);
      delete newTests[action.id];
      return Object.assign({}, state, {
        tests: newTests
      });

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