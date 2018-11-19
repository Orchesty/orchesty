import * as types from 'rootApp/actionTypes';

const initialState = {};

export default (state = initialState, action) => {
  switch (action.type) {
    case types.PROCESS_SET_STATE:
      return Object.assign({}, state, { [action.id]: action.stateType });

    case types.PROCESS_CLEAR:
      if (state.hasOwnProperty(action.id)) {
        const newState = Object.assign({}, state);
        delete newState[action.id];
        return newState;
      }

      return state;


    default:
      return state;
  }
};
