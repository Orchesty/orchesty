import * as types from '../actionTypes';
import * as applicationActions from './applicationActions';
import serverRequest from '../services/apiGatewayServer';


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
        dispatch(userLogged(response));
        dispatch(applicationActions.selectPage('dashboard'));
      }
      
      return response;
    });
  }
}

export function logout() {
  return dispatch => {
    return serverRequest(dispatch, 'POST', '/user/logout').then(response => {
      if (response){
        dispatch(applicationActions.selectPage('login'));
        dispatch(userLogout());
      }

      return response;
    });
  }
}