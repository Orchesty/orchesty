import * as types from '../actionTypes';
import * as applicationActions from './applicationActions';
import * as notificationActions from './notificationActions';
import * as processActions from './processActions';
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

export function login(data, processId) {
  return dispatch => {
    processId && dispatch(processActions.startProcess(processId));
    return serverRequest(dispatch, 'POST', '/user/login', null, data).then(response => {
      processId && dispatch(processActions.finishProcess(processId, response));
      if (response){
        dispatch(userLogged(response));
        dispatch(applicationActions.selectPage('dashboard'));
      }
      
      return response;
    });
  }
}

export function logout(processId) {
  return dispatch => {
    processId && dispatch(processActions.startProcess(processId));
    return serverRequest(dispatch, 'POST', '/user/logout').then(response => {
      processId && dispatch(processActions.finishProcess(processId, response));
      if (response){
        dispatch(applicationActions.selectPage('login'));
        dispatch(userLogout());
      }

      return response;
    });
  }
}

export function register(email, processId) {
  return dispatch => {
    processId && dispatch(processActions.startProcess(processId));
    return serverRequest(dispatch, 'POST', '/user/register', null, {email}).then(response => {
      processId && dispatch(processActions.finishProcess(processId, response));
      
      return response;
    })
  }
}

export function activate(token, processId){
  return dispatch => {
    processId && dispatch(processActions.startProcess(processId));
    return serverRequest(dispatch, 'POST', `/user/${token}/activate`).then(response => {
      processId && dispatch(processActions.finishProcess(processId, response));
      if (response){
        dispatch(applicationActions.selectPage('set_password', {token}));
        dispatch(notificationActions.addSuccess('You account was activated'));
      }
      return response;
    })
  }
}

export function resetPassword(email, processId) {
  return dispatch => {
    processId && dispatch(processActions.startProcess(processId));
    return serverRequest(dispatch, 'POST', '/user/reset_password', null, {email}).then(response => {
      processId && dispatch(processActions.finishProcess(processId, response));

      return response;
    })
  }
}

export function setPassword(token, password, processId) {
  return dispatch => {
    processId && dispatch(processActions.startProcess(processId));
    return serverRequest(dispatch, 'POST', `/user/${token}/set_password`, null, {password}).then(response => {
      processId && dispatch(processActions.finishProcess(processId, response));
      if (response){
        dispatch(applicationActions.selectPage('login'));
        dispatch(notificationActions.addSuccess('Password was set'));
      }

      return response;
    })
  }
}
