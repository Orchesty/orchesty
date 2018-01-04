import * as types from 'rootApp/actionTypes';
import topologyGroup from 'reducers/topologyGroup';
import * as topologyActions from 'rootApp/actions/topologyActions';
import * as categoryActions from 'rootApp/actions/categoryActions';

function startSearch(search){
  return {
    type: types.GENERAL_SEARCH_START_SEARCH,
    search
  }
}

function finish(items){
  return {
    type: types.GENERAL_SEARCH_FINISH,
    items
  }
}

export function clear(){
  return {
    type: types.GENERAL_SEARCH_CLEAR
  }
}

export function search(searchStr){
  return (dispatch, getState) => {
    const oldSearchStr = getState().generalSearch.search;
    if (oldSearchStr !== searchStr){
      if (searchStr) {
        dispatch(startSearch(searchStr));
        return Promise.all([
          dispatch(topologyActions.needTopologyList('complete')),
          dispatch(categoryActions.needCategoryList('complete'))
        ]).then(() => {
          const topologyGroupElements = getState().topologyGroup.elements;
          const items = Object.keys(topologyGroupElements)
            .filter(key => key.indexOf(searchStr) >= 0)
            .map(key => ({id: key, objectType: 'topologyGroup'}));
          return dispatch(finish(items));
        });
      } else {
        return dispatch(clear());
      }
    } else {
      return Promise.resolve(true);
    }
  }
}