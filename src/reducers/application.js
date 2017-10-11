import * as types from 'rootApp/actionTypes';
import objectEquals from 'utils/objectEquals';
import config from 'rootApp/config';


const initialState = {
  mainMenu: config.mainMenu,
  selectedPage: {
    key: 'dashboard',
    args: null,
    data: null
  },
  showMenu: true,
  modal: null,
  modalData: null
};

export default (state = initialState, action) => {
  switch (action.type){
    case types.SELECT_PAGE:
      if (state.selectedPage.key != action.key || !objectEquals(state.selectedPage.args, action.args) || !objectEquals(state.selectedPage.data, action.data)) {
        return Object.assign({}, state, {
          selectedPage: {
            key: action.key,
            args: action.args,
            data: action.data
          }
        });
      } else {
        return state;
      }

    case types.SET_PAGE_DATA:
      return Object.assign({}, state, {
        selectedPage: Object.assign({}, state.selectedPage, {
          data: Object.assign({}, state.selectedPage.data, action.data)
        })
      });
      
    case types.TOGGLE_MAIN_SUB_MENU:
      return Object.assign({}, state, {
        mainMenu: state.mainMenu.map(
          item => item.open === true || (item.open !== true && item.id == action.id) ? Object.assign({}, item, {open: !item.open}) : item
        )
      });
    
    case types.TOGGLE_MAIN_MENU:
      return Object.assign({}, state, {
        showMenu: !state.showMenu
      });
    
    case types.MODAL_OPEN:
      return Object.assign({}, state, {
        modal: action.id,
        modalData: action.data
      });
    
    case types.MODAL_CLOSE:
      return Object.assign({}, state, {
        modal: null
      });

    case types.USER_LOGOUT:
    case types.USER_LOGGED:
      return Object.assign({}, state, {
        modal: null,
        modalData: null
      });
    
    default:
      return state;
  }
}