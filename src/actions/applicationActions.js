import * as types from 'rootApp/actionTypes';

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

export function selectPage(key, args = null, data = null){
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
