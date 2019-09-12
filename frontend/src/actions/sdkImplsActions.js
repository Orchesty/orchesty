import * as types from './../actionTypes';
import listFactory from './factories/listFactory';
import serverRequest from './../services/apiGatewayServer';

const { listLoading, listError, listReceive } = listFactory('SDK_IMPLS/LIST/');

function receiveItems(items) {
  return {
    type: types.SDK_IMPLS_RECEIVE_ITEMS,
    items,
  };
}

function loadList(id, loadingState = true) {
  return (dispatch) => {
    if (loadingState) {
      dispatch(listLoading(id));
    }

    return loadInnerList(dispatch, id);
  };
}

export function needSdkImplsList(listId) {
  return (dispatch) => {
    return dispatch(loadList(listId));
  };
}

export function sdkImplsInitialize() {
  return (dispatch) => {
    dispatch({ type: types.SDK_IMPLS_INITIALIZE });

    return Promise.resolve(true);
  }
}

export const create = (data, idList) => dispatch => serverRequest(dispatch, 'POST', '/sdks', null, data).then(() => loadInnerList(dispatch, idList));

export const update = (id, data, idList) => dispatch => serverRequest(dispatch, 'PUT', `/sdks/${id}`, null, data).then(() => loadInnerList(dispatch, idList));

export const remove = (id, idList) => dispatch => serverRequest(dispatch, 'DELETE', `/sdks/${id}`, null).then(() => loadInnerList(dispatch, idList));

const loadInnerList = (dispatch, idList) => {
  serverRequest(dispatch, 'GET', '/sdks').then((response) => {
    const items = response.items.map(item => {
      item.name = item.key;

      return item;
    });

    dispatch(receiveItems(items));
    dispatch(response ? listReceive(idList, { items }) : listError(idList));
    dispatch(sdkImplementations(items.map(({ key, value }) => ({ key, value }))));

    return response;
  });
};

const sdkImplementations = data => ({
  type: types.NODE_IMPLEMENTATION,
  data,
});
