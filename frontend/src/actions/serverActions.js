import * as types from 'rootApp/actionTypes';
import * as processActions from './processActions';
import * as authActions from './authActions';

function changeApiGateway(server){
  return {
    type: types.SERVER_API_GATEWAY_CHANGE,
    server
  }
}

export function changeApiGatewayServer(server, processId){
  return (dispatch, getState) => {
    if (getState().server.apiGateway !== server){
      processId && dispatch(processActions.startProcess(processId));
      return dispatch(authActions.logout()).then(response => {
        dispatch(changeApiGateway(server));
        processId && dispatch(processActions.finishProcess(processId, response));

        return Boolean(response);
      });
    } else {
      processId && dispatch(processActions.finishProcess(processId, true));
      return Promise.resolve(true);
    }
  }
}