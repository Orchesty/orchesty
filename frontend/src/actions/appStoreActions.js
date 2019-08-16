import * as types from './../actionTypes';
import listFactory from './factories/listFactory';
import serverRequest, { makeUrl } from './../services/apiGatewayServer';
import * as notificationActions from './notificationActions';

const { listLoading, listError, listReceive } = listFactory('APP_STORE/LIST/');

function receiveItems(items) {
  return {
    type: types.APP_STORE_RECEIVE_ITEMS,
    items,
  };
}

function loadList(id, loadingState = true) {
  return (dispatch) => {
    if (loadingState) {
      dispatch(listLoading(id));
    }

    return serverRequest(dispatch, 'GET', '/applications').then((response) => {
      return serverRequest(dispatch, 'GET', `/applications/users/${window.store.getState().auth.user.id}`).then((innerResponse) => {
        let items = response.items.map(item => {
          item = processItem(item);
          const innerItem = innerResponse.items.filter(({ key }) => key === item.key);

          return innerItem.length ? { ...item, ...innerItem[0] } : item;
        }).sort(({ authorized: authorizedOne, updated: updatedOne, name: nameOne }, { authorized: authorizedTwo, updated: updatedTwo, name: nameTwo }) => {
          if (authorizedOne !== undefined && authorizedTwo !== undefined) {
            if (authorizedOne === authorizedTwo) {
              return updatedOne < updatedTwo; // 2nd: Last Updated
            }

            return authorizedOne ? 1 : -1; // 1st: Not Authorized
          } else if (authorizedOne === undefined && authorizedTwo === undefined) {
            return nameOne > nameTwo; // 4th: Alphabetically
          } else {
            return authorizedOne === undefined ? 1 : -1; // 3rd: Installed
          }
        });

        items = items.map((item, key) => {
          item.realName = item.name;
          item.name = `${key}_${item.name}`;

          return item;
        });

        dispatch(receiveItems(items));
        dispatch(response ? listReceive(id, { items }) : listError(id));

        return response;
      });
    });
  };
}

export function needAppStoreList(listId) {
  return (dispatch) => {
    return dispatch(loadList(listId));
  };
}

export function appStoreInitialize() {
  return (dispatch) => {
    dispatch({ type: types.APP_STORE_INITIALIZE });

    return Promise.resolve(true);
  }
}

export const getApplication = (application, user) => dispatch => {
  processApplication(dispatch, 'GET', application, user);

  return serverRequest(dispatch, 'GET', `/applications/${application}`).then(item => {
    item = processItem(item);

    dispatch({ type: types.APP_STORE_RECEIVE_APPLICATION, application, data: item });
  })
};

export const installApplication = (application, user) => dispatch => processApplication(dispatch, 'POST', application, user, {}, 'Application installed');

export const changeApplication = (application, user, data) => dispatch => processApplication(dispatch, 'PUT', application, user, data, 'Settings saved');

export const uninstallApplication = (application, user) => dispatch => processApplication(dispatch, 'DELETE', application, user, {}, 'Application uninstalled');

export const subscribeApplication = (application, user, data) => dispatch => serverRequest(dispatch, 'POST', `/webhook/applications/${application}/users/${user}/subscribe`, {}, data).then(() =>
  processApplication(dispatch, 'GET', application, user)
);

export const unsubscribeApplication = (application, user, data) => dispatch => serverRequest(dispatch, 'POST', `/webhook/applications/${application}/users/${user}/unsubscribe`, {}, data).then(() => {
  processApplication(dispatch, 'GET', application, user)
});

export const authorizeApplication = (application, user, redirect) => dispatch => window.location.href = makeUrl(`/applications/${application}/users/${user}/authorize?redirect_url=${redirect}`);

const processApplication = (dispatch, method, application, user, data, message) => {
  return serverRequest(dispatch, method, `/applications/${application}/users/${user}`, {}, data).then(data => {
    dispatch({ type: types.APP_STORE_RECEIVE_APPLICATION, application, data });
    if (message) {
      dispatch(notificationActions.addNotification('success', message));
    }
  })
};

const processItem = item => {
  item.applicationType = item.application_type;
  item.authorizationType = item.authorization_type;

  delete item.application_type;
  delete item.authorization_type;

  return item;
};
