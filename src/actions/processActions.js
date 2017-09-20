import * as types from 'rootApp/actionTypes';
import {stateType} from 'rootApp/types';

export function startProcess(id) {
  return {
    type: types.PROCESS_SET_STATE,
    id,
    stateType: stateType.LOADING
  }
}

export function successProcess(id){
  return {
    type: types.PROCESS_SET_STATE,
    id,
    stateType: stateType.SUCCESS
  }
}

export function errorProcess(id) {
  return {
    type: types.PROCESS_SET_STATE,
    id,
    stateType: stateType.ERROR
  }
}

export function finishProcess(id, result) {
  return result ? successProcess(id) : errorProcess(id);
}