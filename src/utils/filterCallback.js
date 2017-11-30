import {filterType} from 'rootApp/types';

function shouldFilterText(value){
  return value !== null && value !== undefined && value !== '';
}

export default (filter, getStoredValue) => {
  let {value, property, storedValue} = filter;
  if (storedValue) {
    value = getStoredValue(storedValue);
  }
  switch (filter.type){
    case filterType.EXACT:
      return shouldFilterText(value) ? item => item[property] === value : () => true;

    case filterType.EXACT_NULL:
      return item => item[property] === value;

    case filterType.SEARCH:
      return shouldFilterText(value) ? item => item[property].indexOf(value) >= 0 : () => true;

    case filterType.BOOLEAN:
      return shouldFilterText(value) ? item => item[property] == value : () => true;

    default:
      return () => false;
  }
}