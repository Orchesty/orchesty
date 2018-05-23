import React from 'react'
import PropTypes from 'prop-types';
import {connect} from 'react-redux';
import Panel from 'rootApp/views/wrappers/Panel';
import MetricsDateRangeHeader from '../metrics/MetricsDateRangeHeader';
import TopologySchema from './TopologySchema';
import getTopologyState from 'rootApp/utils/getTopologyState';
import * as nodeActions from 'rootApp/actions/nodeActions';
import * as applicationActions from 'rootApp/actions/applicationActions';
import * as metricsActions from 'rootApp/actions/metricsActions';
import StateComponent from 'rootApp/views/wrappers/StateComponent';
import {stateType} from 'rootApp/types';

function mapStateToProps(state, ownProps){
  const {topology} = state;
  let key = ownProps.topologyId;
  key = ownProps.interval ? `${key}[${ownProps.interval}]` : key;
  key = ownProps.metricsRange ? `${key}[${ownProps.metricsRange.since}-${ownProps.metricsRange.till}]` : key;
  const topologyElement = topology.elements[ownProps.topologyId];
  const topologyState = getTopologyState(topologyElement);

  return {
    title: `${topologyElement.name}.v${topologyElement.version}`,
    middleHeader: <div className="middle-label"><span className={'label label-' + topologyState.label}>{topologyState.title}</span></div>,
    topology: topologyElement,
    state: topologyElement ? stateType.SUCCESS : stateType.LOADING
  }
}

function mapActionsToProps(dispatch, ownProps){
 // const needNodeList = forced => dispatch(nodeActions.needNodesForTopology(ownProps.topologyId, forced));
 // const needMetricsList = forced => dispatch(metricsActions.needTopologyMetrics(ownProps.topologyId, ownProps.metricsRange, forced));
  return {
    // needNodeList,
    // needMetricsList,
    // notLoadedCallback: () => {
    //   needNodeList(false);
    //   needMetricsList(false);
    // },
    changeMetricsRange: (since, till) => dispatch(applicationActions.setPageArgs(ownProps.pageId, {metricsRange: {since, till}}))
  }
}

export default connect(mapStateToProps, mapActionsToProps)(StateComponent(Panel(TopologySchema, {
  noActions: true,
  noHide: true,
  HeaderComponent: MetricsDateRangeHeader
})));

