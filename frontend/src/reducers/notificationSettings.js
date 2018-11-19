import * as types from 'rootApp/actionTypes';
import { stateType } from 'rootApp/types';

const initialState = {
  state: stateType.NOT_LOADED,
  data: null,
};

export default (state = initialState, action) => {
  switch (action.type) {
    case types.USER_LOGOUT:
    case types.USER_LOGGED:
      return initialState;

    case types.NOTIFICATION_SETTINGS_RECEIVE:
      return {
        state: stateType.SUCCESS,
        data: action.data,
      };

    case types.NOTIFICATION_SETTINGS_ERROR:
      return {
        state: stateType.ERROR,
        data: null,
      };

    case types.NOTIFICATION_SETTINGS_LOADING:
      return {
        state: stateType.LOADING,
        data: null,
      };

    default:
      return state;
  }
};
