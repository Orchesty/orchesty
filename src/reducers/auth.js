import * as types from '../actionTypes';

const initialState = {
  user: null
};

export default (state = initialState, action) => {
  switch (action.type){

    case types.USER_LOGGED:
      return Object.assign({}, state, {user: action.data});
    
    case types.USER_LOGOUT:
      return Object.assign({}, state, {user: null});

    default:
      return state;
  }
}