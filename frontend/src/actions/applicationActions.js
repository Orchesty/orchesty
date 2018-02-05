import * as types from 'rootApp/actionTypes';
import config from 'rootApp/config';

export function leftSidebarToggle() {
  return {
    type: types.LEFT_SIDEBAR_TOGGLE
  }
}

export function setPageData(data){
  return {
    type: types.SET_PAGE_DATA,
    data
  }
}

export function selectPage(key, args = null, data = null){
  const page = config.pages[key];
  if (page && page.defaultArgs){
    args = Object.assign({}, page.defaultArgs, args);
  }
  return {
    type: types.SELECT_PAGE,
    key,
    args,
    data
  }
}

export function openModal(id, data){
  return {
    type: types.MODAL_OPEN,
    id,
    data
  }
}

export function closeModal(){
  return {
    type: types.MODAL_CLOSE
  }
}

export function changePageArgs(args) {
  return (dispatch, getState) => {
    return dispatch(selectPage(getState().application.selectedPage.key, args));
  }
}

export function setPageArgs(args) {
  return (dispatch, getState) => {
    const page = getState().application.selectedPage;
    return dispatch(selectPage(page.key, Object.assign({}, page.args, args)));
  }
}

export function openContextMenu(menuKey, args, componentKey, x, y){
  return {
    type: types.CONTEXT_MENU_OPEN,
    menuKey,
    args,
    componentKey,
    x,
    y
  }
}

export function closeContextMenu() {
  return {
    type: types.CONTEXT_MENU_CLOSE
  }
}
