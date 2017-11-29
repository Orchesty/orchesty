import * as types from 'rootApp/actionTypes';
import serverRequest from 'services/apiGatewayServer';
import listFactory from './factories/listFactory';
import {stateType} from 'rootApp/types';

const {createCompleteList, listLoading, listReceive, listError} = listFactory('CATEGORY/LIST/');

function receiveItems(items){
  return {
    type: types.CATEGORY_RECEIVE_ITEMS,
    items
  }
}

function createCompleteTree(treeId){
  return {
    type: types.CATEGORY_TREE_CREATE,
    id: treeId,
  }
}

function treeLoading(treeId){
  return {
    type: types.CATEGORY_TREE_LOADING,
    id: treeId
  }
}

function treeError(treeId){
  return {
    type: types.CATEGORY_TREE_ERROR,
    id: treeId
  }
}

function treeReceive(treeId, root){
  return {
    type: types.CATEGORY_TREE_RECEIVE,
    id: treeId,
    root
  }
}

function loadList(listId){
  return (dispatch, getState) => {
    dispatch(listLoading(listId));
    return serverRequest(dispatch, 'GET', '/categories').then(response => {
      if (response){
        dispatch(receiveItems(response.items));
      }
      dispatch(response ? listReceive(listId, response) : listError(listId));
      return response;
    });
  }
}

export function needCategoryList(listId, forced = false) {
  return (dispatch, getState) => {
    const list = getState().category.lists[listId];
    if (!list) {
      dispatch(createCompleteList(listId));
    }

    if (forced || !list || list.state == stateType.NOT_LOADED || list.state == stateType.ERROR){
      return dispatch(loadList(listId));
    } else {
      return Promise.resolve(true);
    }
  }
}

function buildTreeFromList(list, id){
  return {
    id: id,
    items: list.items.filter(item => item.parent === id).map(item => buildTreeFromList(list, item.id))
  }
}

function loadTree(treeId, force = false) {
  return (dispatch, getState) => {
    dispatch(treeLoading(treeId));
    return dispatch(needCategoryList('complete', force)).then(ok => {
      if (ok) {
        const list = getState().category.lists['complete'];
        dispatch(treeReceive(treeId, buildTreeFromList(list, null)));
      } else {
        dispatch(treeError(treeId));
      }
      return ok;
    });
  }
}

export function needCategoryTree(treeId, forced = false) {
  return (dispatch, getState) => {
    const tree = getState().category.trees[treeId];
    if (!tree) {
      dispatch(createCompleteTree(treeId));
    }
    if (forced || !tree || tree.state == stateType.NOT_LOADED || tree.state == stateType.ERROR){
      return dispatch(loadTree(treeId, forced));
    } else {
      return Promise.resolve(true);
    }
  }
}