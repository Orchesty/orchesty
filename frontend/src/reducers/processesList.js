import * as types from 'rootApp/actionTypes';

const initialState = {
  elements: [],
};

function addElements(oldElements, newElements) {
  const result = [];
  newElements.forEach((item) => {
    result.push(item);
  });
  return result;
}

function reducer(state = initialState, action) {
  switch (action.type) {
    case types.TOPOLOGY_RECEIVE_PROCESSES:
      return Object.assign({}, state, {
        elements: addElements(state.elements, action.data.items),
      });

    default:
      return state;
  }
}

export default (state = initialState, action) => {
  if (action.type === types.USER_LOGOUT || action.type === types.USER_LOGGED) {
    return initialState;
  }

  return reducer(state, action);
};
