import * as types from 'rootApp/actionTypes';
import objectEquals from 'utils/objectEquals';
import { getPageArgs, getPageId } from 'rootApp/utils/pageUtils';
import config from 'rootApp/config/index';

const initialState = {
  selectedPage: 'dashboard',
  pages: {},
  showSideBar: true,
  modal: null,
  modalData: null,
  contextMenu: null,
  showEditorPropPanel: true,
};

export default (state = initialState, action) => {
  switch (action.type) {
    case types.OPEN_PAGE:
      const id = getPageId(action.key, action.args);
      if (id && (!state.pages[id] || !objectEquals(state.pages[id].args, action.args))) {
        return Object.assign({}, state, {
          selectedPage: id,
          pages: Object.assign({}, state.pages, {
            [id]: {
              key: action.key,
              args: getPageArgs(action.key, action.args),
            },
          }),
        });
      } else if (state.selectedPage !== id) {
        return Object.assign({}, state, { selectedPage: id });
      }
      return state;


    case types.SELECT_PAGE:
      return Object.assign({}, state, { selectedPage: action.id });

    case types.CLOSE_PAGE:
      const newState = Object.assign({}, state, { pages: Object.assign({}, state.pages) });
      delete newState.pages[action.id];
      if (newState.selectedPage === action.id) {
        if (action.newId) {
          newState.selectedPage = action.newId;
        } else {
          const pageIds = Object.keys(state.pages);
          const pageIndex = Math.max(pageIds.indexOf(action.id) - 1, 0);
          const newPageIds = Object.keys(newState.pages);
          newState.selectedPage = newPageIds.length > pageIndex ? newPageIds[pageIndex] : null;
        }
      }
      return newState;

    case types.LEFT_SIDEBAR_TOGGLE:
      return Object.assign({}, state, {
        showSideBar: !state.showSideBar,
      });

    case types.EDITOR_PROP_PANEL_TOGGLE:
      return Object.assign({}, state, {
        showEditorPropPanel: !state.showEditorPropPanel,
      });

    case types.MODAL_OPEN:
      return Object.assign({}, state, {
        modal: action.id,
        modalData: action.data,
      });

    case types.MODAL_CLOSE:
      return Object.assign({}, state, {
        modal: null,
      });

    case types.USER_LOGOUT:
    case types.USER_LOGGED:
      return Object.assign({}, state, {
        modal: null,
        modalData: null,
        pages: Object.keys(state.pages)
          .filter(id => !config.pages[state.pages[id].key].needAuth)
          .reduce((acc, id) => {
            acc[id] = state.pages[id];
            return acc;
          }, {}),
      });

    case types.CONTEXT_MENU_OPEN:
      return Object.assign({}, state, {
        contextMenu: {
          menuKey: action.menuKey,
          args: action.args,
          componentKey: action.componentKey,
          x: action.x,
          y: action.y,
        },
      });

    case types.CONTEXT_MENU_CLOSE:
      return Object.assign({}, state, { contextMenu: null });

    default:
      return state;
  }
};
