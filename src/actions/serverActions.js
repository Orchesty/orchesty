import * as types from 'rootApp/actionTypes';
import * as processActions from './processActions';
import * as authActions from './authActions';
import processes from "rootApp/enums/processes";

function changeApiGateway(server){
  return {
    type: types.SERVER_API_GATEWAY_CHANGE,
    server
  }
}

export function changeApiGatewayServer(server, processHash = 'default'){
  return (dispatch, getState) => {
    if (getState().server.apiGateway !== server){
      dispatch(processActions.startProcess(processes.serverApiGatewayChange(processHash)));
      return dispatch(authActions.logout()).then(response => {
        dispatch(changeApiGateway(server));
        dispatch(processActions.finishProcess(processes.serverApiGatewayChange(processHash), response));

        return Boolean(response);
      });
    } else {
      dispatch(processActions.finishProcess(processes.serverApiGatewayChange(processHash), true));
      return Promise.resolve(true);
    }
  }
}