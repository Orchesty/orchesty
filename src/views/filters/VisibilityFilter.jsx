import React from 'react'
import FilterCase from 'rootApp/views/wrappers/FilterCase';
import {FilterSelect} from 'rootApp/views/elements/filterInputs';
import {filterType} from 'rootApp/types';

const VisibilityFilter = FilterCase(FilterSelect, {
  type: filterType.EXACT,
  property: 'visibility',
  options: [
    {value: '', label: 'All'},
    {value: 'public', label: 'Public'},
    {value: 'draft', label: 'Draft'}
  ],
  label: "Visibility",
  icon: "fa fa-eye"
});

export default VisibilityFilter;