import React from 'react'
import FilterCase from 'rootApp/views/wrappers/FilterCase';
import {FilterSelect} from 'rootApp/views/elements/filterInputs';
import {filterType} from 'rootApp/types';

const SeverityFilter = FilterCase(FilterSelect, {
  type: filterType.EXACT,
  property: 'severity',
  options: [
    {value: '', label: 'All'},
    {value: 'ALERT', label: 'Alert'},
    {value: 'WARNING', label: 'Warning'},
    {value: 'ERROR', label: 'Error'},
    {value: 'CRITICAL', label: 'Critical'},
  ],
  label: "Severity",
  icon: "fa fa-eye"
});

export default SeverityFilter;