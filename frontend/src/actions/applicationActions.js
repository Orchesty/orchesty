import * as types from 'rootApp/actionTypes';

export function leftSidebarToggle() {
  return {
    type: types.LEFT_SIDEBAR_TOGGLE
  }
}

export function openPage(key, args = null){
  if (!key){
    throw new Error('openPage action: Missing page key');
  }
  return {
    type: types.OPEN_PAGE,
    key,
    args
  }
}

export function closePage(id, newId){
  return {
    type: types.CLOSE_PAGE,
    id,
    newId
  }
}

export function selectPage(id){
  return {
    type: types.SELECT_PAGE,
    id
  }
}

export function setPageArgs(id, args){
  return (dispatch, getState) => {
    const page = getState().application.pages[id];
    if (!page){
      throw new Error(`Page [${id}] not found.`);
    }
    return dispatch(openPage(page.key, Object.assign({}, page.args, args)));
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
