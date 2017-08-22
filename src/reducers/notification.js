import * as types from '../action_types';

const initialState = {
  elements: {},
  newId: 0,
  active: []
};

export default (state = initialState, action) => {
  switch (action.type){
    case types.NOTIFICATION_INCREMENT_ID:
      return Object.assign({}, state, {newId: state.newId + 1});

    case types.NOTIFICATION_ADD:
      const {notification} = action;
      return Object.assign({}, state, {
        elements: Object.assign({}, state.elements, {[notification.id]: notification}),
        active: [...state.active, notification.id]
      });

    case types.NOTIFICATION_CLOSE:
    case types.NOTIFICATION_TIMEOUT:
      let newState = Object.assign({}, state, {
        active: state.active.map(item => item && (item == action.id) ? null  : item)
      });

      if (newState.active.length > 0 && !newState.active.some(item => item)){
        newState.active = [];
      }
      return newState;

    default:
      return state;
  }
}