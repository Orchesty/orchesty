import * as types from 'rootApp/actionTypes';
import * as md5 from 'md5';

import config from 'rootApp/config';

function incrementId(message) {
  const hash = md5(message);
  return {
    hash: hash ,
    type: types.NOTIFICATION_INCREMENT_ID,
  };
}

function create(id, type, message) {
  const hash = md5(message);
  return {
    type: types.NOTIFICATION_ADD,
    notification: { id, type, message, hash },
  };
}

function notificationTimeout(id) {
  return {
    type: types.NOTIFICATION_TIMEOUT,
    id,
  };
}

function setNotificationTimeout(id, timeout) {
  return dispatch => setTimeout(() => { dispatch(notificationTimeout(id)); }, timeout);
}

export function addNotification(type, message, timeout = config.params.notificationTimeout) {
  return (dispatch, getState) => {
    dispatch(incrementId(message));
    const { notification } = getState();
    const id = notification.newId;
    dispatch(create(id, type, message));
    dispatch(setNotificationTimeout(id, timeout));
  };
}

export function closeNotification(id) {
  return {
    type: types.NOTIFICATION_CLOSE,
    id,
  };
}

export function addSuccess(message, timeout) {
  return dispatch => dispatch(addNotification('success', message, timeout));
}
