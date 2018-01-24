import * as types from 'rootApp/actionTypes';

const initialState = {};

export default (state = initialState, action) => {

  switch (action.type){
    case types.USER_LOGOUT:
    case types.USER_LOGGED:
      return initialState;

    case types.PANEL_TOGGLE:
      return Object.assign({}, state, {[action.id]: !state[action.id]});

    default:
      return state;
  }
}