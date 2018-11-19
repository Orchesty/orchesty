import * as types from 'rootApp/actionTypes';
import config from 'rootApp/config';

const initialState = {
  apiGateway: config.servers.apiGateway.initDefault,
};

export default (state = initialState, action) => {
  switch (action.type) {
    case types.SERVER_API_GATEWAY_CHANGE:
      return Object.assign({}, state, { apiGateway: action.server });

    default:
      return state;
  }
};
