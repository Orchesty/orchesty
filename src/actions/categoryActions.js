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

function treeSelect(treeId, itemId){
  return {
    type: types.CATEGORY_TREE_SELECT,
    id: treeId,
    itemId
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

function buildTreeFromList(elements, list, id){
  return {
    id: id,
    open: true,
    items: list.items.filter(childId => elements[childId].parent === id).map(childId => buildTreeFromList(elements, list, childId))
  }
}

function loadTree(treeId, force = false) {
  return (dispatch, getState) => {
    dispatch(treeLoading(treeId));
    return dispatch(needCategoryList('complete', force)).then(ok => {
      if (ok) {
        const category = getState().category;
        const list = category.lists['complete'];
        dispatch(treeReceive(treeId, buildTreeFromList(category.elements, list, null)));
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

export function treeItemClick(treeId, itemId, successCallback) {
  return(dispatch, getState) => {
    const tree = getState().category.trees[treeId];
    if (tree.selectedId !== itemId){
      const res = dispatch(treeSelect(treeId, itemId));
      if (typeof successCallback == 'function') {
        successCallback(itemId);
      }
      return res;
    }
  }
}

