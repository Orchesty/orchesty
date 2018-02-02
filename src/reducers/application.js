import * as types from 'rootApp/actionTypes';
import objectEquals from 'utils/objectEquals';


const initialState = {
  selectedPage: {
    key: 'dashboard',
    args: null,
    data: null
  },
  showSideBar: true,
  modal: null,
  modalData: null,
  contextMenu: null
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

    case types.LEFT_SIDEBAR_TOGGLE:
      return Object.assign({}, state, {
        showSideBar: !state.showSideBar
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

    case types.CONTEXT_MENU_OPEN:
      return Object.assign({}, state, {
        contextMenu: {
          menuKey: action.menuKey,
          args: action.args,
          componentKey: action.componentKey,
          x: action.x,
          y: action.y
        }
      });

    case types.CONTEXT_MENU_CLOSE:
      return Object.assign({}, state, {contextMenu: null});

    default:
      return state;
  }
}