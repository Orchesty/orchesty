import * as types from 'rootApp/actionTypes';
import listFactory from './factories/listFactory';
import serverRequest from 'services/apiGatewayServer';

const { listLoading, listError, listReceive } = listFactory('NOTIFICATION_SETTING/LIST/');

function receiveItems(items) {
  return {
    type: types.NOTIFICATION_SETTINGS_RECEIVE_ITEMS,
    items,
  };
}

function receiveEvents(events) {
  return {
    type: types.NOTIFICATION_SETTINGS_RECEIVE_EVENTS,
    events,
  };
}

function loadList(id, loadingState = true) {
  return (dispatch) => {
    if (loadingState) {
      dispatch(listLoading(id));
    }

    return serverRequest(dispatch, 'GET', '/notification_settings').then((response) => {
      dispatch(receiveItems(response.items.map(item => {
        item.customName = item.name;
        item.name = item.id;

        return item;
      })));

      serverRequest(dispatch, 'GET', '/notification_settings/events').then((response) => {
        dispatch(receiveEvents(response));
      });

      dispatch(response ? listReceive(id, response) : listError(id));
      return response;
    });
  };
}

export function needNotificationSettingList(listId) {
  return (dispatch) => {
    return dispatch(loadList(listId));
  };
}

export function notificationSettingsChange(listId, id, data) {
  const { events, ...settings } = data;

  if (settings.emails) {
    settings.emails = settings.emails.split(/\r\n|\r|\n/g);
  }

  return dispatch => new Promise((resolve, reject) => {
    serverRequest(dispatch, 'PUT', `/notification_settings/${id}`, null, { events, settings }).then((response) => {
      if (response) {
        dispatch(loadList(listId));

        resolve(true);
      }

      reject('Something gone wrong.')
    });
  });
}

export function notificationSettingInitialize() {
  return (dispatch) => {
    dispatch({ type: types.NOTIFICATION_SETTINGS_INITIALIZE });

    return Promise.resolve(true);
  }
}
