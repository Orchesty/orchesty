import {filterType} from 'rootApp/types';

function shouldFilterText(value){
  return value !== null && value !== undefined && value !== '';
}

export default filter => {
  const {value, property} = filter;
  switch (filter.type){
    case filterType.EXACT:
      return shouldFilterText(value) ? item => item[property] === value : () => true;

    case filterType.SEARCH:
      return shouldFilterText(value) ? item => item[property].indexOf(value) >= 0 : () => true;

    default:
      return () => false;
  }
}