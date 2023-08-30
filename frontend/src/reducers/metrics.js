import * as types from 'rootApp/actionTypes';
import { stateType } from 'rootApp/types';

const initialState = {
  elements: {},
  topologies: {},
};

function addElements(oldElements, newElements, suffix = '') {
  const result = Object.assign({}, oldElements);
  Object.keys(newElements).forEach((id) => {
    if (id !== 'topology' || id !== 'requests') {
      result[id + suffix] = {
        state: stateType.SUCCESS,
        data: newElements[id],
      };
    }
  });
  return result;
}

export default (state = initialState, action) => {
  switch (action.type) {
    case types.USER_LOGOUT:
    case types.USER_LOGGED:
      return initialState;

    case types.METRICS_TOPOLOGY_SET_STATE:
      return Object.assign({}, state, {
        topologies: Object.assign({}, state.topologies, {
          [action.key]: Object.assign({}, state.topologies[action.id], {
            id: action.id,
            state: action.state,
          }),
        }),
      });

    case types.METRICS_RECEIVE_ITEMS:
      return Object.assign({}, state, {
        elements: addElements(state.elements, action.items, action.suffix),
      });

    case types.METRICS_TOPOLOGY_RECEIVE:
      return Object.assign({}, state, {
        topologies: Object.assign({}, state.topologies, {
          [action.key]: {
            id: action.id,
            state: stateType.SUCCESS,
            items: Object.keys(action.items).filter(key => key !== 'topology' && key !== 'requests'),
            data: Object.assign({}, action.items.topology, { requests: action.items.requests }),
          },
        }),
      });

    case types.METRICS_TOPOLOGY_INVALIDATE:
      return Object.assign({}, state, {
        topologies: Object.assign({}, state.topologies, Object.keys(state.topologies).reduce((acc, curr) => {
          if (state.topologies[curr].id === action.id) {
            acc[curr] = Object.assign({}, state.topologies[curr], { state: stateType.NOT_LOADED });
          }
          return acc;
        }, {})),
      });

    case types.METRICS_SET_STATE:
      return Object.assign({}, state, {
        elements: Object.assign({}, state.elements, {
          [action.id]: Object.assign({}, state.elements[action.id], {
            state: action.state,
          }),
        }),
      });

    case types.METRICS_RECEIVE:
      return Object.assign({}, state, {
        elements: Object.assign({}, state.elements, {
          [action.id]: {
            state: stateType.SUCCESS,
            data: action.data,
          },
        }),
      });

    default:
      return state;
  }
};
