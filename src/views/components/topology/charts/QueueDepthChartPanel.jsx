import React from 'react'
import PropTypes from 'prop-types';
import Panel from 'wrappers/Panel';
import QueueDepthChart from './QueueDepthChart';

export default Panel(QueueDepthChart, {title: 'Queue depth'});
