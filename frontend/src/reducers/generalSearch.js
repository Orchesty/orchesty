import * as types from 'rootApp/actionTypes';
import { stateType } from 'rootApp/types';

const initialState = {
  state: stateType.NOT_LOADED,
  search: '',
  items: [],
};

export default (state = initialState, action) => {
  switch (action.type) {
    case types.USER_LOGOUT:
    case types.USER_LOGGED:
      return initialState;

    case types.GENERAL_SEARCH_START_SEARCH:
      return {
        state: stateType.LOADING,
        search: action.search,
        items: [],
      };

    case types.GENERAL_SEARCH_FINISH:
      return Object.assign({}, state, {
        state: stateType.SUCCESS,
        items: action.items,
      });

    case types.GENERAL_SEARCH_CLEAR:
      return initialState;

    default:
      return state;
  }
};
