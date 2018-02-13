import * as types from 'rootApp/actionTypes';
import config from 'rootApp/config';
import * as applicationActions from './applicationActions';
import * as notificationActions from './notificationActions';
import * as processActions from './processActions';
import serverRequest from 'services/apiGatewayServer';
import processes from "rootApp/enums/processes";


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

export function login(data, processHash = 'default') {
  return dispatch => {
    dispatch(processActions.startProcess(processes.authLogin(processHash)));
    return serverRequest(dispatch, 'POST', '/user/login', null, data).then(response => {
      if (response){
        dispatch(userLogged(response));
        dispatch(applicationActions.openPage(config.params.mainPage));
      }
      dispatch(processActions.finishProcess(processes.authLogin(processHash), response));
      return response;
    });
  }
}

export function afterLogout() {
  return dispatch => {
    dispatch(applicationActions.openPage('login'));
    dispatch(userLogout());
  }
}

export function logout(processHash = 'default') {
  return (dispatch, getState) => {
    if (getState().auth.user) {
      dispatch(processActions.startProcess(processes.authLogout(processHash)));
      return serverRequest(dispatch, 'POST', '/user/logout').then(response => {
        dispatch(processActions.finishProcess(processes.authLogout(processHash), response));
        dispatch(afterLogout());
        return response;
      });
    } else {
      dispatch(processActions.finishProcess(processes.authLogout(processHash), true));
      return Promise.resolve(true);
    }
  }
}

export function register(email, processHash = 'default') {
  return dispatch => {
    dispatch(processActions.startProcess(processes.authRegister(processHash)));
    return serverRequest(dispatch, 'POST', '/user/register', null, {email}).then(response => {
      dispatch(processActions.finishProcess(processes.authRegister(processHash), response));
      
      return response;
    })
  }
}

export function activate(token, processHash = 'default'){
  return dispatch => {
    dispatch(processActions.startProcess(processes.authActivate(processHash)));
    return serverRequest(dispatch, 'POST', `/user/${token}/activate`).then(response => {
      if (response){
        dispatch(applicationActions.openPage('set_password', {token}));
        dispatch(notificationActions.addSuccess('You account was activated'));
      }
      dispatch(processActions.finishProcess(processes.authActivate(processHash), response));
      return response;
    })
  }
}

export function resetPassword(email, processHash = 'default') {
  return dispatch => {
    dispatch(processActions.startProcess(processes.authResetPassword(processHash)));
    return serverRequest(dispatch, 'POST', '/user/reset_password', null, {email}).then(response => {
      dispatch(processActions.finishProcess(processes.authResetPassword(processHash), response));

      return response;
    })
  }
}

export function setPassword(token, password, processHash = 'default') {
  return dispatch => {
    dispatch(processActions.startProcess(processes.authSetPassword(processHash)));
    return serverRequest(dispatch, 'POST', `/user/${token}/set_password`, null, {password}).then(response => {
      if (response){
        dispatch(applicationActions.openPage('login'));
        dispatch(notificationActions.addSuccess('Password was set'));
      }
      dispatch(processActions.finishProcess(processes.authSetPassword(processHash), response));
      return response;
    })
  }
}
