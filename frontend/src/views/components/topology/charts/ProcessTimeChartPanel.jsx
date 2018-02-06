import React from 'react'
import PropTypes from 'prop-types';
import Panel from 'wrappers/Panel';
import ProcessTimeChart from './ProcessTimeChart';

export default Panel(ProcessTimeChart, {title: 'Process time'});