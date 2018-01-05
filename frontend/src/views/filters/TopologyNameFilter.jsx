import React from 'react'
import {connect} from 'react-redux';

import FilterCase from 'rootApp/views/wrappers/FilterCase';
import {filterType} from 'rootApp/types';
import {FilterTextInput} from 'rootApp/views/elements/filterInputs';

const TopologyNameFilter = FilterCase(FilterTextInput, {
  type: filterType.SEARCH,
  property: 'name',
  label: 'Name',
  icon: 'fa fa-pencil',
  size: 'md'
});

export default TopologyNameFilter;