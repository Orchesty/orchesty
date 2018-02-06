import React from 'react'
import PropTypes from 'prop-types';
import Panel from 'wrappers/Panel';
import WaitingTimeChart from './WaitingTimeChart';

export default Panel(WaitingTimeChart, {title: 'Waiting time'});