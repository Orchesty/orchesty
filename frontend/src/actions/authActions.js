import * as types from '../actionTypes';
import serverRequest from '../middleware/apiGatewayServer';


function userLogged(data){
  return {
    type: types.USER_LOGGED,
    data
  }
}

function userLogout() {
  return {
    type: types.USER_LOGOUT
  }
}

export function login(data) {
  return dispatch => {
    return serverRequest(dispatch, 'POST', '/user/login', null, data).then(response => {
      if (response){
        dispatch(userLogged(response))
      }
      
      return response;
    });
  }
}

export function logout() {
  return dispatch => {
    return serverRequest(dispatch, 'POST', '/user/logout').then(response => {
      if (response){
        dispatch(userLogout())
      }

      return response;
    });
  }
}