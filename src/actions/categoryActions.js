import * as types from 'rootApp/actionTypes';
import serverRequest from 'services/apiGatewayServer';
import listFactory from './factories/listFactory';
import {stateType} from 'rootApp/types';
import * as processActions from './processActions';
import processes from 'enums/processes';

const {createCompleteList, listLoading, listReceive, listError, invalidateLists} = listFactory('CATEGORY/LIST/');

function invalidateTrees(){
  return {
    type: types.CATEGORY_TREE_INVALIDATE
  }
}

function receive(data){
  return {
    type: types.CATEGORY_RECEIVE,
    data: data.parent === '' ? Object.assign(data, {parent: null}) : data
  }
}

function remove(id) {
  return {
    type: types.CATEGORY_REMOVE,
    id
  }
}

function receiveItems(items){
  return {
    type: types.CATEGORY_RECEIVE_ITEMS,
    items: items.map(item => item.parent === '' ? Object.assign(item, {parent: null}) : item)
  }
}

function createCompleteTree(treeId, selectedId = undefined){
  return {
    type: types.CATEGORY_TREE_CREATE,
    id: treeId,
    selectedId: selectedId
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

export function treeToggle(treeId, itemId){
  return {
    type: types.CATEGORY_TREE_TOGGLE,
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

function buildTreeFromList(elements, list, id, open = true){
  return {
    id: id,
    open,
    items: list.items.filter(childId => elements[childId].parent === id).map(childId => buildTreeFromList(elements, list, childId, open))
  }
}

function loadTree(treeId, force = false, open = true) {
  return (dispatch, getState) => {
    dispatch(treeLoading(treeId));
    return dispatch(needCategoryList('complete', force)).then(ok => {
      if (ok) {
        const category = getState().category;
        const list = category.lists['complete'];
        dispatch(treeReceive(treeId, buildTreeFromList(category.elements, list, null, open)));
      } else {
        dispatch(treeError(treeId));
      }
      return ok;
    });
  }
}

export function needCategoryTree(treeId, forced = false, selectedId = undefined, open = true) {
  return (dispatch, getState) => {
    const tree = getState().category.trees[treeId];
    if (!tree) {
      dispatch(createCompleteTree(treeId, selectedId));
    } else if (selectedId !== undefined) {
      dispatch(treeSelect(treeId, selectedId));
    }
    if (forced || !tree || tree.state == stateType.NOT_LOADED || tree.state == stateType.ERROR){
      return dispatch(loadTree(treeId, forced, open));
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

export function createCategory(data, processHash = 'new') {
  return dispatch => {
    dispatch(processActions.startProcess(processes.categoryCreate(processHash)));
    return serverRequest(dispatch, 'POST', `/categories`, null, data).then(
      response => {
        if (response){
          dispatch(receive(response));
          dispatch(invalidateLists());
          dispatch(invalidateTrees());
        }
        dispatch(processActions.finishProcess(processes.categoryCreate(processHash), response));
        return response;
      }
    )
  }
}

export function updateCategory(id, data) {
  return dispatch => {
    dispatch(processActions.startProcess(processes.categoryUpdate(id)));
    return serverRequest(dispatch, 'PATCH', `/categories/${id}`, null, data).then(
      response => {
        if (response){
          dispatch(receive(response));
        }
        dispatch(processActions.finishProcess(processes.categoryUpdate(id), response));
        return response;
      }
    )
  }
}

export function deleteCategory(id){
  return dispatch => {
    dispatch(processActions.startProcess(processes.categoryDelete(id)));
    return serverRequest(dispatch, 'DELETE', `/categories/${id}`).then(
      response => {
        if (response) {
          dispatch(invalidateLists());
          dispatch(invalidateTrees());
          dispatch(remove(id));
        }
        dispatch(processActions.finishProcess(processes.categoryDelete(id), response));
        return response;
      }
    )
  }
}