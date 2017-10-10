import {stateType} from 'rootApp/types';

export default states => {
  if (states.length > 0) {
    if (states.every(state => state == stateType.SUCCESS)) {
      return stateType.SUCCESS;
    }
    if (states.some(state => state == stateType.ERROR)) {
      return stateType.ERROR;
    }
    if (states.some(state => state == stateType.NOT_LOADED)) {
      return stateType.NOT_LOADED;
    }
    if (states.some(state => !state)) {
      return undefined;
    }
    return stateType.LOADING;
  } else {
    return undefined;
  }
}