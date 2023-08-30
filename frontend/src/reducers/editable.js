import * as types from 'rootApp/actionTypes';

const initialState = {};

export default (state = initialState, action) => {
  switch (action.type) {
    case types.USER_LOGOUT:
    case types.USER_LOGGED:
      return initialState;

    case types.EDITABLE_SWITCH_EDIT:
      return Object.assign({}, state, {
        [action.id]: {
          editMode: true,
        },
      });

    case types.EDITABLE_SWITCH_VIEW:
      const newState = Object.assign({}, state);
      delete newState[action.id];
      return newState;

    case types.EDITABLE_CHANGE:
      return Object.assign({}, state, {
        [action.id]: Object.assign({}, state[action.id], { value: action.value }),
      });

    default:
      return state;
  }
};
