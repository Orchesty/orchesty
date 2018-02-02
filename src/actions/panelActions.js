import * as types from 'rootApp/actionTypes';

export function togglePanel(id) {
  return {
    type: types.PANEL_TOGGLE,
    id
  }
}