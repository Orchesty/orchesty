import * as types from '../actionTypes';

const initialState = {};

export default (state = initialState, action) => {
  switch (action.type){
    case types.SET_PROCESS_STATE:
      return Object.assign({}, state, {[action.id]: action.stateType});
    
    case types.CLEAR_PROCESS:
      if (state.hasOwnProperty(action.id)) {
        const newState = Object.assign({}, state);
        delete newState[action.id];
        return newState;
      }
      else {
        return state;
      }
    
    default:
      return state;
  }
}