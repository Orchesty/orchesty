import React from 'react'
import FilterCase from 'rootApp/views/wrappers/FilterCase';
import {FilterSelect} from 'rootApp/views/elements/filterInputs';
import {filterType} from 'rootApp/types';

const EnabledFilter = FilterCase(FilterSelect, {
  type: filterType.EXACT,
  property: 'enabled',
  valueProcess: value => value ? value == 'true' : undefined,
  options: [
    {value: '', label: 'All'},
    {value: true, label: 'Enabled'},
    {value: false, label: 'Disabled'}
  ],
  label: "Enabled",
  icon: "fa fa-eye"
});

export default EnabledFilter;