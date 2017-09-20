import * as types from 'rootApp/actionTypes';

import config from 'rootApp/config';

function incrementId(){
  return {
    type: types.NOTIFICATION_INCREMENT_ID
  }
}

function create(id, type, message){
  return {
    type: types.NOTIFICATION_ADD,
    notification: {id, type, message}
  }
}

function notificationTimeout(id){
  return {
    type: types.NOTIFICATION_TIMEOUT,
    id
  }
}

function setNotificationTimeout(id, timeout){
  return dispatch => setTimeout(() => { dispatch(notificationTimeout(id)) }, timeout);
}

export function addNotification(type, message, timeout = config.params.notificationTimeout) {
  return (dispatch, getState) => {
    dispatch(incrementId());
    const {notification} = getState();
    const id = notification.newId;
    dispatch(create(id, type, message));
    dispatch(setNotificationTimeout(id, timeout));
  };
}

export function closeNotification(id) {
  return {
    type: types.NOTIFICATION_CLOSE,
    id
  }
}

export function addSuccess(message, timeout) {
  return dispatch => {
    return dispatch(addNotification('success', message, timeout));
  }
}