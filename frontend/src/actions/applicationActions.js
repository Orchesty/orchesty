import * as types from '../actionTypes';

export function toggleMainSubMenu(id){
  return {
    type: types.TOGGLE_MAIN_SUB_MENU,
    id
  }
}

export function toggleMainMenu() {
  return {
    type: types.TOGGLE_MAIN_MENU
  }
}

export function setPageData(data){
  return {
    type: types.SET_PAGE_DATA,
    data
  }
}

export function selectPage(key, args){
  return {
    type: types.SELECT_PAGE,
    key,
    args
  }
}

export function openModal(id, data){
  return {
    type: types.OPEN_MODAL,
    id,
    data
  }
}

export function closeModal(){
  return {
    type: types.CLOSE_MODAL
  }
}

export function changePageArgs(args) {
  return (dispatch, getState) => {
    return dispatch(selectPage(getState().application.selectedPage.key, args));
  }
}
