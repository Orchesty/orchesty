import * as types from 'rootApp/actionTypes';

export function switchEdit(id) {
  return {
    type: types.EDITABLE_SWITCH_EDIT,
    id,
  };
}

export function switchView(id) {
  return {
    type: types.EDITABLE_SWITCH_VIEW,
    id,
  };
}

export function change(id, value) {
  return {
    type: types.EDITABLE_CHANGE,
    id,
    value,
  };
}
