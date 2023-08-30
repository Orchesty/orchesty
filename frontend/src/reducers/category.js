import * as types from 'rootApp/actionTypes';
import listsReducer from './list';
import { stateType } from 'rootApp/types';

const listPrefix = 'CATEGORY/LIST/';
const listPrefixLength = listPrefix.length;

const initialState = {
  elements: {},
  lists: {},
  trees: {},
};

function getElementId(element) {
  return element._id;
}

function addElement(oldElements, element) {
  return Object.assign({}, oldElements, {
    [element._id]: element,
  });
}

function addElements(oldElements, newElements) {
  const result = Object.assign({}, oldElements);
  newElements.forEach((item) => {
    result[item._id] = item;
  });
  return result;
}

function treeItemReducer(state, action) {
  switch (action.type) {
    case types.CATEGORY_TREE_TOGGLE:
      if (action.itemId === state.id) {
        return Object.assign({}, state, { open: !state.open });
      }
      let changed = false;
      const newItems = state.items.map((item) => {
        const newItem = treeItemReducer(item, action);
        changed = changed || (newItem !== item);
        return newItem;
      });

      return changed ? Object.assign({}, state, { items: newItems }) : state;

    default:
      return state;
  }
}

function reducer(state, action) {
  switch (action.type) {
    case types.CATEGORY_RECEIVE_ITEMS:
      return Object.assign({}, state, {
        elements: addElements(state.elements, action.items),
      });

    case types.CATEGORY_RECEIVE:
      return Object.assign({}, state, {
        elements: addElement(state.elements, action.data),
      });

    case types.CATEGORY_REMOVE:
      const newElements = Object.assign({}, state.elements);
      delete newElements[action.id];
      return Object.assign({}, state, { elements: newElements });

    case types.CATEGORY_TREE_CREATE:
      return Object.assign({}, state, {
        trees: Object.assign({}, state.trees, {
          [action.id]: {
            id: action.id,
            state: stateType.NOT_LOADED,
            root: null,
            selectedId: action.selectedId !== undefined ? action.selectedId : null,
          },
        }),
      });

    case types.CATEGORY_TREE_LOADING:
      return Object.assign({}, state, {
        trees: Object.assign({}, state.trees, {
          [action.id]: Object.assign({}, state.trees[action.id], {
            state: stateType.LOADING,
          }),
        }),
      });

    case types.CATEGORY_TREE_ERROR:
      return Object.assign({}, state, {
        trees: Object.assign({}, state.trees, {
          [action.id]: Object.assign({}, state.trees[action.id], {
            state: stateType.ERROR,
          }),
        }),
      });

    case types.CATEGORY_TREE_RECEIVE:
      return Object.assign({}, state, {
        trees: Object.assign({}, state.trees, {
          [action.id]: Object.assign({}, state.trees[action.id], {
            state: stateType.SUCCESS,
            root: action.root,
          }),
        }),
      });

    case types.CATEGORY_TREE_SELECT:
      return Object.assign({}, state, {
        trees: Object.assign({}, state.trees, {
          [action.id]: Object.assign({}, state.trees[action.id], {
            selectedId: action.itemId,
          }),
        }),
      });

    case types.CATEGORY_TREE_TOGGLE:
      return Object.assign({}, state, {
        trees: Object.assign({}, state.trees, {
          [action.id]: Object.assign({}, state.trees[action.id], {
            root: treeItemReducer(state.trees[action.id].root, action),
          }),
        }),
      });

    case types.CATEGORY_TREE_INVALIDATE:
      const newTrees = {};
      let changed = false;
      Object.keys(state.trees).forEach((key) => {
        const tree = state.trees[key];
        if (tree.state !== stateType.NOT_LOADED) {
          newTrees[key] = Object.assign({}, tree, { state: stateType.NOT_LOADED });
          changed = true;
        } else {
          newTrees[key] = tree;
        }
      });
      if (changed) {
        return Object.assign({}, state, { trees: newTrees });
      }
      return state;


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
