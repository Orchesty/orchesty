import React from 'react'

import FilterCase from 'rootApp/views/wrappers/FilterCase';
import {filterType} from 'rootApp/types';
import {FilterTextInput} from 'rootApp/views/elements/filterInputs';

const LogMessageFilter = FilterCase(FilterTextInput, {
  type: filterType.SEARCH,
  property: 'search',
  label: 'Search',
  icon: 'fa fa-pencil',
});

export default LogMessageFilter;