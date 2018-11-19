import * as types from 'rootApp/actionTypes';
import { stateType } from 'rootApp/types';
import serverRequest from 'services/apiGatewayServer';
import * as processActions from 'rootApp/actions/processActions';
import processes from 'enums/processes';

function receive(data) {
  return {
    type: types.NOTIFICATION_SETTINGS_RECEIVE,
    data,
  };
}

function loading() {
  return {
    type: types.NOTIFICATION_SETTINGS_LOADING,
  };
}

function error() {
  return {
    type: types.NOTIFICATION_SETTINGS_ERROR,
  };
}

function loadNotificationSettings() {
  return (dispatch) => {
    dispatch(loading());

    return serverRequest(dispatch, 'GET', '/notification_settings').then((response) => {
      dispatch(response ? receive(response) : error());
      return response;
    });
  };
}

export function needNotificationSettings(forced = false) {
  return (dispatch, getState) => {
    const state = getState().notificationSettings.state;
    if (forced || !state || state === stateType.NOT_LOADED || state === stateType.ERROR) {
      return dispatch(loadNotificationSettings());
    }
    return Promise.resolve(true);
  };
}

export function updateNotificationSettings(data) {
  return (dispatch) => {
    dispatch(processActions.startProcess(processes.notificationSettingsUpdate()));
    serverRequest(dispatch, 'PUT', '/notification_settings', null, data).then((response) => {
      if (response) {
        dispatch(receive(response));
      }
      dispatch(processActions.finishProcess(processes.notificationSettingsUpdate(), response));
    });
  };
}

