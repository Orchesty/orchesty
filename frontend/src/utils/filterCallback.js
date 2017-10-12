import {filterType} from 'rootApp/types';

export default (key, filter) => {
  const {value} = filter;
  switch (filter.type){
    case filterType.EXACT:
      return value !== null || value !== undefined ? item => item === value : () => true;

    default:
      return () => false;
  }
}